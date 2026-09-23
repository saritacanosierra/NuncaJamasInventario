<?php
/**
 * Controlador del Dashboard
 */

class DashboardController {
    private $productoModel;
    private $ventaModel;
    private $gastoModel;
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        $this->productoModel = new Producto($db);
        $this->ventaModel = new Venta($db);
        $this->gastoModel = new Gasto($db);
    }
    
    /**
     * Mostrar dashboard
     */
    public function index() {
        requireAuth();
        
        // Verificar permisos antes de mostrar el dashboard
        if (!canAccess('dashboard')) {
            $rol = $_SESSION['usuario_rol'] ?? 'cajero';
            $redirectAction = ($rol === 'cajero') ? 'ventas' : (($rol === 'operario') ? 'produccion' : 'login');
            redirect('index.php?action=' . $redirectAction);
        }
        
        try {
            // Obtener métricas
            $inventario_total = $this->getInventarioTotal();
            $productos_agotarse = $this->productoModel->getStockBajo();
            $productos_mas_vendidos = $this->productoModel->getMasVendidos(10);
            $ventas_dia = $this->ventaModel->getEstadisticas('dia');
            $ventas_semana = $this->ventaModel->getEstadisticas('semana');
            $ventas_mes = $this->ventaModel->getEstadisticas('mes');
            $ventas_por_dia = $this->ventaModel->getVentasPorDia();
            $gastos_mes = $this->getGastosMes();
            $punto_equilibrio = $this->calcularPuntoEquilibrio();
            
            require_once BASE_DIR . '/front/views/dashboard/index.php';
        } catch (Exception $e) {
            error_log("Error en Dashboard: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el dashboard: ' . $e->getMessage();
            // Valores por defecto en caso de error
            $inventario_total = 0;
            $productos_agotarse = [];
            $productos_mas_vendidos = [];
            $ventas_dia = ['total_ventas' => 0, 'total_ingresos' => 0];
            $ventas_semana = ['total_ventas' => 0, 'total_ingresos' => 0];
            $ventas_mes = ['total_ventas' => 0, 'total_ingresos' => 0, 'promedio_venta' => 0];
            $ventas_por_dia = [];
            $gastos_mes = ['total' => 0, 'cantidad' => 0];
            $punto_equilibrio = ['gastos' => 0, 'margen_promedio' => 0, 'punto_equilibrio' => 0, 'ventas_actuales' => 0];
            
            require_once BASE_DIR . '/front/views/dashboard/index.php';
        }
    }
    
    /**
     * Obtener total de inventario
     */
    private function getInventarioTotal() {
        $productos = $this->productoModel->getAll();
        $total = 0;
        foreach ($productos as $producto) {
            $total += $producto['stock'] * $producto['precio_costo'];
        }
        return $total;
    }
    
    /**
     * Obtener gastos del mes (sin incluir inversiones)
     */
    private function getGastosMes() {
        $fechaInicio = date('Y-m-01');
        $fechaFin = date('Y-m-t');
        // Solo gastos, NO inversiones (las inversiones no intervienen en el punto de equilibrio)
        return $this->gastoModel->getTotalPorPeriodo($fechaInicio, $fechaFin);
    }
    
    /**
     * Calcular margen de contribución promedio
     * Prioriza ventas reales de los últimos 3 meses, con fallback a productos disponibles
     * Limita el margen a un rango realista (30% - 50%)
     * 
     * @return float Margen de contribución (0.30 a 0.50)
     */
    private function calcularMargenContribucion() {
        try {
            // Intentar calcular margen basado en ventas reales de los últimos 3 meses
            $fechaInicio = date('Y-m-01', strtotime('-2 months'));
            $fechaFin = date('Y-m-t');
            
            $query = "SELECT 
                        SUM(dv.subtotal) as total_ventas,
                        SUM((p.precio_venta - p.precio_costo) * dv.cantidad) as total_margen_bruto
                      FROM detalle_venta dv
                      INNER JOIN productos p ON dv.producto_id = p.id
                      INNER JOIN ventas v ON dv.venta_id = v.id
                      WHERE DATE(v.fecha_venta) >= :fecha_inicio 
                        AND DATE(v.fecha_venta) <= :fecha_fin
                        AND p.precio_venta > 0
                        AND p.precio_costo >= 0
                        AND p.precio_venta > p.precio_costo";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':fecha_inicio', $fechaInicio);
            $stmt->bindParam(':fecha_fin', $fechaFin);
            $stmt->execute();
            $resultado = $stmt->fetch();
            
            if ($resultado && $resultado['total_ventas'] > 0 && $resultado['total_margen_bruto'] > 0) {
                // Margen de contribución = Total Margen Bruto / Total Ventas
                $margenCalculado = $resultado['total_margen_bruto'] / $resultado['total_ventas'];
                return $this->limitarMargen($margenCalculado);
            }
            
            // Fallback: calcular margen promedio de productos disponibles
            return $this->calcularMargenDesdeProductos();
            
        } catch (Exception $e) {
            error_log("Error calculando margen: " . $e->getMessage());
            return 0.35; // Margen conservador por defecto
        }
    }
    
    /**
     * Calcular margen promedio desde productos disponibles
     * 
     * @return float Margen de contribución (0.30 a 0.50)
     */
    private function calcularMargenDesdeProductos() {
        $productos = $this->productoModel->getAll();
        $sumaMargenes = 0;
        $productosConPrecio = 0;
        
        foreach ($productos as $producto) {
            if ($producto['precio_venta'] > 0 
                && $producto['precio_costo'] >= 0 
                && $producto['precio_venta'] > $producto['precio_costo']) {
                
                $margen = ($producto['precio_venta'] - $producto['precio_costo']) / $producto['precio_venta'];
                
                // Solo considerar márgenes razonables (30% - 50%)
                if ($margen >= 0.30 && $margen <= 0.50) {
                    $sumaMargenes += $margen;
                    $productosConPrecio++;
                }
            }
        }
        
        if ($productosConPrecio > 0) {
            return $this->limitarMargen($sumaMargenes / $productosConPrecio);
        }
        
        return 0.35; // Margen conservador por defecto
    }
    
    /**
     * Limitar margen a un rango realista (30% - 50%)
     * 
     * @param float $margen Margen calculado
     * @return float Margen limitado entre 0.30 y 0.50
     */
    private function limitarMargen($margen) {
        if ($margen < 0.30) {
            return 0.30; // Mínimo 30%
        } elseif ($margen > 0.50) {
            return 0.50; // Máximo 50%
        }
        return $margen;
    }
    
    /**
     * Calcular punto de equilibrio optimizado
     * Fórmula: Punto de Equilibrio = Costos Fijos / Margen de Contribución
     * 
     * Optimizaciones:
     * 1. Usa margen basado en ventas reales (últimos 3 meses para más precisión)
     * 2. Limita el margen mínimo al 30% para evitar valores extremos
     * 3. Limita el margen máximo al 50% para valores realistas
     * 4. Considera solo productos realmente vendidos
     */
    private function calcularPuntoEquilibrio() {
        $gastosMes = $this->getGastosMes();
        $ventasMes = $this->ventaModel->getEstadisticas('mes');
        
        // Obtener margen de contribución promedio
        $margenPromedio = $this->calcularMargenContribucion();
        
        $gastosTotal = $gastosMes['total'] ?? 0;
        $ventasActuales = $ventasMes['total_ingresos'] ?? 0;
        
        // Punto de Equilibrio = Costos Fijos / Margen de Contribución
        $puntoEquilibrio = $margenPromedio > 0 ? $gastosTotal / $margenPromedio : 0;
        
        // Calcular ganancias
        // Ganancia = (Ventas * Margen de Contribución) - Gastos
        $gananciaActual = ($ventasActuales * $margenPromedio) - $gastosTotal;
        
        // Calcular ganancia si se logra vender el punto de equilibrio completo
        // Si logras el punto de equilibrio, las ventas serían iguales al punto de equilibrio
        $ventasSiLograEquilibrio = $puntoEquilibrio;
        
        // Contribución si logras el punto de equilibrio
        $contribucionSiLograEquilibrio = $ventasSiLograEquilibrio * $margenPromedio;
        
        // Ganancia progresiva al alcanzar el punto de equilibrio:
        // - Si las ventas actuales < gastos: muestra negativo (cuánto falta para cubrir gastos)
        // - Si las ventas actuales >= gastos: muestra positivo (ganancia después de cubrir gastos)
        // Para "si logras la meta" (punto de equilibrio): Ventas del punto de equilibrio - Gastos
        // Esto siempre será positivo porque el punto de equilibrio > gastos
        $gananciaSiLograEquilibrio = $ventasSiLograEquilibrio - $gastosTotal;
        
        // Pero también calculamos la ganancia actual progresiva para mostrar el estado real
        // Ganancia actual = Ventas actuales - Gastos
        // Si es negativo, muestra cuánto falta. Si es positivo, muestra la ganancia.
        $gananciaActualProgresiva = $ventasActuales - $gastosTotal;
        
        // Sin embargo, si queremos mostrar la ganancia real (contribución - gastos):
        // La ganancia real al alcanzar el punto de equilibrio sería 0
        // Pero el usuario quiere ver la diferencia directa (ventas - gastos) para motivación
        
        // Calcular ganancia proyectada si se alcanza la meta diaria todos los días restantes (para otros usos)
        $diasHabiles = $this->getDiasHabilesMes();
        $diasHabilesRestantes = $this->getDiasHabilesRestantes();
        $metaDiaria = $diasHabiles > 0 ? $puntoEquilibrio / $diasHabiles : 0;
        
        // Ventas proyectadas = Ventas actuales + (Meta diaria * Días hábiles restantes)
        $ventasProyectadas = $ventasActuales + ($metaDiaria * $diasHabilesRestantes);
        
        // Ganancia proyectada si se alcanza la meta diaria
        $gananciaAlcanzarMeta = ($ventasProyectadas * $margenPromedio) - $gastosTotal;
        
        // Calcular contribución actual (ventas × margen)
        $contribucionActual = $ventasActuales * $margenPromedio;
        
        // Calcular contribución proyectada (para meta diaria)
        $contribucionProyectada = $ventasProyectadas * $margenPromedio;
        
        return [
            'gastos' => $gastosTotal,
            'margen_promedio' => $margenPromedio * 100,
            'punto_equilibrio' => $puntoEquilibrio,
            'ventas_actuales' => $ventasActuales,
            'ganancia_actual' => $gananciaActual,
            'ganancia_alcanzar_meta' => $gananciaSiLograEquilibrio, // Ganancia si logras el punto de equilibrio (ventas - gastos)
            'ganancia_actual_progresiva' => $gananciaActualProgresiva, // Ganancia actual progresiva (ventas actuales - gastos)
            'contribucion_actual' => $contribucionActual,
            'contribucion_proyectada' => $contribucionSiLograEquilibrio, // Contribución si logras el punto de equilibrio
            'ventas_proyectadas' => $ventasSiLograEquilibrio // Ventas si logras el punto de equilibrio
        ];
    }
    
    /**
     * Obtener datos de meta diaria para el contador flotante
     * Retorna: meta diaria en valor monetario, ventas de hoy, ventas del mes, faltante del mes
     */
    public function getMetaDiaria() {
        // Iniciar output buffering al inicio para capturar cualquier warning/notice
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();
        
        // Verificar autenticación sin redirecciones para API JSON
        if (!isset($_SESSION['usuario_id'])) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }
        
        // Limpiar cualquier output previo antes de enviar headers
        ob_clean();
        header('Content-Type: application/json');
        
        try {
            $puntoEquilibrio = $this->calcularPuntoEquilibrio();
            $puntoEquilibrioMonto = $puntoEquilibrio['punto_equilibrio'];
            
            // Calcular días hábiles del mes (lunes a sábado)
            $diasHabiles = $this->getDiasHabilesMes();
            $diasHabilesRestantes = $this->getDiasHabilesRestantes();
            
            // Meta diaria en valor monetario
            $metaDiariaMonto = $diasHabiles > 0 ? $puntoEquilibrioMonto / $diasHabiles : 0;
            
            // Ventas de hoy (valor monetario)
            $ventasHoy = $this->getVentasHoy();
            
            // Ventas del mes (valor monetario)
            $ventasMes = $this->getVentasMes();
            
            // Valor faltante para el mes
            $valorFaltanteMes = max(0, $metaDiariaMonto * $diasHabiles - $ventasMes);
            
            // Verificar si se alcanzó la meta del día
            $metaAlcanzada = $ventasHoy >= $metaDiariaMonto;
            
            // Calcular progreso del día
            $progresoDia = $metaDiariaMonto > 0 ? min(100, ($ventasHoy / $metaDiariaMonto) * 100) : 0;
            
            echo json_encode([
                'success' => true,
                'meta_diaria' => round($metaDiariaMonto, 2),
                'ventas_hoy' => round($ventasHoy, 2),
                'ventas_mes' => round($ventasMes, 2),
                'valor_faltante_mes' => round($valorFaltanteMes, 2),
                'dias_habiles_restantes' => $diasHabilesRestantes,
                'meta_alcanzada' => $metaAlcanzada,
                'progreso_dia' => round($progresoDia, 2)
            ]);
        } catch (PDOException $e) {
            ob_clean();
            error_log("Error de base de datos en getMetaDiaria: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Error de conexión a la base de datos. Verifique la configuración.'
            ]);
        } catch (Exception $e) {
            ob_clean();
            error_log("Error en getMetaDiaria: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Error al obtener la meta diaria: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Obtener valor de ventas de hoy (en dinero)
     */
    private function getVentasHoy() {
        try {
            $fechaHoy = date('Y-m-d');
            $query = "SELECT COALESCE(SUM(v.total), 0) as total_ventas
                      FROM ventas v
                      WHERE DATE(v.fecha_venta) = :fecha_hoy";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':fecha_hoy', $fechaHoy);
            $stmt->execute();
            $resultado = $stmt->fetch();
            return $resultado ? floatval($resultado['total_ventas']) : 0;
        } catch (Exception $e) {
            error_log("Error en getVentasHoy: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtener valor de ventas del mes actual (en dinero)
     */
    private function getVentasMes() {
        try {
            $fechaInicio = date('Y-m-01');
            $fechaFin = date('Y-m-t');
            $query = "SELECT COALESCE(SUM(v.total), 0) as total_ventas
                      FROM ventas v
                      WHERE DATE(v.fecha_venta) >= :fecha_inicio 
                        AND DATE(v.fecha_venta) <= :fecha_fin";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':fecha_inicio', $fechaInicio);
            $stmt->bindParam(':fecha_fin', $fechaFin);
            $stmt->execute();
            $resultado = $stmt->fetch();
            return $resultado ? floatval($resultado['total_ventas']) : 0;
        } catch (Exception $e) {
            error_log("Error en getVentasMes: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Calcular días hábiles del mes (lunes a sábado, excluyendo domingos)
     */
    private function getDiasHabilesMes() {
        $anio = date('Y');
        $mes = date('m');
        $diasEnMes = date('t', mktime(0, 0, 0, $mes, 1, $anio));
        $diasHabiles = 0;
        
        for ($dia = 1; $dia <= $diasEnMes; $dia++) {
            $timestamp = mktime(0, 0, 0, $mes, $dia, $anio);
            $diaSemana = date('w', $timestamp); // 0 = domingo, 1 = lunes, ..., 6 = sábado
            // Contar solo lunes (1) a sábado (6), excluir domingo (0)
            if ($diaSemana > 0) {
                $diasHabiles++;
            }
        }
        
        return $diasHabiles;
    }
    
    /**
     * Calcular días hábiles restantes del mes (desde hoy hasta fin de mes, lunes a sábado)
     */
    private function getDiasHabilesRestantes() {
        $anio = date('Y');
        $mes = date('m');
        $diaActual = date('d');
        $diasEnMes = date('t', mktime(0, 0, 0, $mes, 1, $anio));
        $diasHabiles = 0;
        
        for ($dia = $diaActual; $dia <= $diasEnMes; $dia++) {
            $timestamp = mktime(0, 0, 0, $mes, $dia, $anio);
            $diaSemana = date('w', $timestamp); // 0 = domingo, 1 = lunes, ..., 6 = sábado
            // Contar solo lunes (1) a sábado (6), excluir domingo (0)
            if ($diaSemana > 0) {
                $diasHabiles++;
            }
        }
        
        return $diasHabiles;
    }
}