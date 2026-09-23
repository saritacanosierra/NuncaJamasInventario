# 📡 Documentación de API Endpoints
## Sistema de Inventario - Ropa Infantil

Esta documentación describe todos los endpoints de la API disponibles en el sistema.

---

## 🔐 Autenticación

Todos los endpoints requieren autenticación mediante sesión PHP, excepto el login.

**Formato de respuesta estándar:**
```json
{
  "success": true|false,
  "data": {...},
  "error": "mensaje de error"
}
```

---

## 📋 Índice de Endpoints

### 🔑 Autenticación
- [Login](#login)

### 👥 Usuarios
- [Obtener Usuario](#obtener-usuario)

### 📊 Dashboard
- [Obtener Meta Diaria](#obtener-meta-diaria)

### 📦 Productos
- [Buscar por Código](#buscar-por-código)
- [Buscar por Nombre](#buscar-por-nombre)
- [Generar Código de Barras](#generar-código-de-barras)
- [Obtener Categorías](#obtener-categorías)

### 🛒 Ventas
- [Obtener Historial](#obtener-historial-ventas)

### 👤 Clientes
- [Buscar Cliente](#buscar-cliente)
- [Obtener Cliente](#obtener-cliente)
- [Crear Cliente Rápido](#crear-cliente-rápido)

### 💰 Gastos
- [Obtener Gasto](#obtener-gasto)
- [Obtener Categorías de Gastos](#obtener-categorías-de-gastos)
- [Obtener Inversión](#obtener-inversión)
- [Obtener Historial de Gastos](#obtener-historial-de-gastos)

### 📅 Agenda
- [Obtener Tarea](#obtener-tarea)
- [Obtener Tareas por Fecha](#obtener-tareas-por-fecha)

### 🏭 Producción
- [Guardar Registro](#guardar-registro)
- [Guardar Operación](#guardar-operación)
- [Eliminar Operación](#eliminar-operación)
- [Guardar Retroceso](#guardar-retroceso)
- [Obtener Operaciones](#obtener-operaciones)
- [Obtener Operarias del Día](#obtener-operarias-del-día)
- [Obtener Retrocesos](#obtener-retrocesos)
- [Buscar Operarias](#buscar-operarias)
- [Generar Código de Operación](#generar-código-de-operación)
- [Obtener Resumen del Día](#obtener-resumen-del-día)
- [Finalizar Día](#finalizar-día)
- [Verificar Día Finalizado](#verificar-día-finalizado)
- [Obtener Historial de Operación](#obtener-historial-de-operación)

---

## 🔑 Autenticación

### Login
**Endpoint:** `POST /index.php?action=login&method=doLogin`

**Descripción:** Autentica un usuario en el sistema.

**Parámetros:**
- `email` (string, requerido): Email del usuario
- `password` (string, requerido): Contraseña del usuario

**Respuesta exitosa:**
- Redirección según el rol del usuario

**Respuesta de error:**
- Redirección a login con mensaje de error en sesión

---

## 👥 Usuarios

### Obtener Usuario
**Endpoint:** `GET /index.php?action=usuarios&method=getUsuario&id={id}`

**Permisos:** Solo administradores

**Parámetros:**
- `id` (int, requerido): ID del usuario

**Respuesta exitosa:**
```json
{
  "success": true,
  "usuario": {
    "id": 1,
    "nombre": "Admin",
    "email": "admin@inventario.com",
    "rol": "admin",
    "activo": 1
  }
}
```

**Respuesta de error:**
```json
{
  "success": false,
  "error": "Usuario no encontrado"
}
```

---

## 📊 Dashboard

### Obtener Meta Diaria
**Endpoint:** `GET /index.php?action=dashboard&method=getMetaDiaria`

**Permisos:** Solo administradores

**Respuesta exitosa:**
```json
{
  "success": true,
  "meta": 1000.00
}
```

---

## 📦 Productos

### Buscar por Código
**Endpoint:** `GET /index.php?action=productos&method=buscarPorCodigo&codigo={codigo}`

**Permisos:** Acceso a productos

**Parámetros:**
- `codigo` (string, requerido): Código de barras del producto

**Respuesta exitosa:**
```json
{
  "success": true,
  "producto": {
    "id": 1,
    "codigo_barras": "1234567890123",
    "nombre": "Camiseta Infantil",
    "precio_venta": 25000.00,
    "stock": 50
  }
}
```

### Buscar por Nombre
**Endpoint:** `GET /index.php?action=productos&method=buscarPorNombre&nombre={nombre}`

**Permisos:** Acceso a productos

**Parámetros:**
- `nombre` (string, requerido): Nombre del producto (búsqueda parcial)

**Respuesta exitosa:**
```json
{
  "success": true,
  "productos": [
    {
      "id": 1,
      "codigo_barras": "1234567890123",
      "nombre": "Camiseta Infantil",
      "precio_venta": 25000.00,
      "stock": 50
    }
  ]
}
```

### Generar Código de Barras
**Endpoint:** `GET /index.php?action=productos&method=generarCodigo`

**Permisos:** Acceso a productos

**Respuesta exitosa:**
```json
{
  "success": true,
  "codigo": "1234567890123"
}
```

### Obtener Categorías
**Endpoint:** `GET /index.php?action=productos&method=obtenerCategorias`

**Permisos:** Acceso a productos

**Respuesta exitosa:**
```json
{
  "success": true,
  "categorias": [
    {
      "id": 1,
      "nombre": "Camisetas",
      "descripcion": "Camisetas para niños",
      "cantidad_productos": 25
    }
  ]
}
```

---

## 🛒 Ventas

### Obtener Historial
**Endpoint:** `GET /index.php?action=ventas&method=obtenerHistorial`

**Permisos:** Acceso a ventas

**Parámetros opcionales:**
- `fecha_desde` (date): Fecha de inicio
- `fecha_hasta` (date): Fecha de fin
- `cliente_id` (int): ID del cliente

**Respuesta exitosa:**
```json
{
  "success": true,
  "ventas": [
    {
      "id": 1,
      "numero_factura": "FAC-001",
      "fecha": "2025-01-28",
      "cliente_nombre": "Juan Pérez",
      "total": 50000.00,
      "usuario_nombre": "Admin"
    }
  ]
}
```

---

## 👤 Clientes

### Buscar Cliente
**Endpoint:** `GET /index.php?action=clientes&method=buscar&termino={termino}`

**Permisos:** Acceso a clientes o cajero (para punto de venta)

**Parámetros:**
- `termino` (string, requerido): Término de búsqueda (nombre, cédula, email)

**Respuesta exitosa:**
```json
{
  "success": true,
  "clientes": [
    {
      "id": 1,
      "nombre": "Juan Pérez",
      "cedula_nit": "1234567890",
      "email": "juan@example.com",
      "telefono": "3001234567"
    }
  ]
}
```

### Obtener Cliente
**Endpoint:** `GET /index.php?action=clientes&method=getCliente&id={id}`

**Permisos:** Acceso a clientes o cajero (para punto de venta)

**Parámetros:**
- `id` (int, requerido): ID del cliente

**Respuesta exitosa:**
```json
{
  "success": true,
  "cliente": {
    "id": 1,
    "nombre": "Juan Pérez",
    "cedula_nit": "1234567890",
    "email": "juan@example.com",
    "telefono": "3001234567",
    "direccion": "Calle 123"
  }
}
```

### Crear Cliente Rápido
**Endpoint:** `POST /index.php?action=clientes&method=crearRapido`

**Permisos:** Acceso a clientes o cajero (para punto de venta)

**Parámetros:**
- `nombre` (string, requerido): Nombre del cliente
- `cedula_nit` (string, requerido): Cédula o NIT
- `email` (string, opcional): Email del cliente
- `telefono` (string, opcional): Teléfono del cliente

**Respuesta exitosa:**
```json
{
  "success": true,
  "cliente": {
    "id": 1,
    "nombre": "Juan Pérez",
    "cedula_nit": "1234567890"
  }
}
```

**Respuesta de error:**
```json
{
  "success": false,
  "error": "El nombre y la cédula/NIT son obligatorios"
}
```

---

## 💰 Gastos

### Obtener Gasto
**Endpoint:** `GET /index.php?action=gastos&method=getGasto&id={id}`

**Permisos:** Acceso a gastos

**Parámetros:**
- `id` (int, requerido): ID del gasto

**Respuesta exitosa:**
```json
{
  "success": true,
  "gasto": {
    "id": 1,
    "concepto": "Servicios públicos",
    "monto": 150000.00,
    "categoria": "Servicios",
    "fecha": "2025-01-28",
    "descripcion": "Pago de servicios"
  }
}
```

### Obtener Categorías de Gastos
**Endpoint:** `GET /index.php?action=gastos&method=obtenerCategorias`

**Permisos:** Acceso a gastos

**Respuesta exitosa:**
```json
{
  "success": true,
  "categorias": [
    {
      "id": 1,
      "nombre": "Servicios",
      "descripcion": "Servicios públicos"
    }
  ]
}
```

### Obtener Inversión
**Endpoint:** `GET /index.php?action=gastos&method=getInversion&id={id}`

**Permisos:** Acceso a gastos

**Parámetros:**
- `id` (int, requerido): ID de la inversión

**Respuesta exitosa:**
```json
{
  "success": true,
  "inversion": {
    "id": 1,
    "concepto": "Nueva máquina",
    "monto": 5000000.00,
    "categoria": "Equipamiento",
    "fecha": "2025-01-28"
  }
}
```

### Obtener Historial de Gastos
**Endpoint:** `GET /index.php?action=gastos&method=obtenerHistorial`

**Permisos:** Acceso a gastos

**Respuesta exitosa:**
```json
{
  "success": true,
  "gastos_por_mes": [
    {
      "mes": "2025-01",
      "mes_nombre": "Enero 2025",
      "total": 500000.00,
      "cantidad": 10
    }
  ],
  "inversiones_por_mes": [
    {
      "mes": "2025-01",
      "mes_nombre": "Enero 2025",
      "total": 5000000.00,
      "cantidad": 2
    }
  ],
  "gastos_por_anio": [
    {
      "anio": "2025",
      "total": 6000000.00,
      "cantidad": 120
    }
  ],
  "inversiones_por_anio": [
    {
      "anio": "2025",
      "total": 50000000.00,
      "cantidad": 24
    }
  ]
}
```

---

## 📅 Agenda

### Obtener Tarea
**Endpoint:** `GET /index.php?action=agenda&method=getTarea&id={id}`

**Permisos:** Acceso a agenda

**Parámetros:**
- `id` (int, requerido): ID de la tarea

**Respuesta exitosa:**
```json
{
  "success": true,
  "tarea": {
    "id": 1,
    "titulo": "Reunión de equipo",
    "descripcion": "Reunión mensual",
    "fecha": "2025-01-30",
    "hora": "10:00:00",
    "completada": 0
  }
}
```

### Obtener Tareas por Fecha
**Endpoint:** `GET /index.php?action=agenda&method=getTareasPorFecha&fecha={fecha}`

**Permisos:** Acceso a agenda

**Parámetros:**
- `fecha` (date, requerido): Fecha en formato YYYY-MM-DD

**Respuesta exitosa:**
```json
{
  "success": true,
  "tareas": [
    {
      "id": 1,
      "titulo": "Reunión de equipo",
      "fecha": "2025-01-30",
      "hora": "10:00:00",
      "completada": 0
    }
  ]
}
```

---

## 🏭 Producción

### Guardar Registro
**Endpoint:** `POST /index.php?action=produccion&method=guardarRegistro`

**Permisos:** Acceso a producción

**Parámetros:**
- `fecha` (date, requerido): Fecha del registro
- `operaria_nombre` (string, requerido): Nombre de la operaria
- `turno` (string, requerido): Turno (mañana/tarde/noche)
- `meta_dia` (int, requerido): Meta del día
- `maquina_asignada` (string, opcional): Máquina asignada

**Respuesta exitosa:**
```json
{
  "success": true,
  "registro": {
    "id": 1,
    "operaria_nombre": "María",
    "fecha": "2025-01-28"
  },
  "message": "Registro guardado exitosamente"
}
```

**Nota:** Los operarios solo pueden crear/editar registros con su propio nombre.

### Guardar Operación
**Endpoint:** `POST /index.php?action=produccion&method=guardarOperacion`

**Permisos:** Acceso a producción

**Parámetros:**
- `fecha` (date, requerido): Fecha de la operación
- `operaria_nombre` (string, requerido): Nombre de la operaria
- `codigo_operacion` (string, opcional): Código de la operación
- `nombre_operacion` (string, requerido): Nombre de la operación
- `maquina_usada` (string, requerido): Máquina usada
- `hora_inicio` (datetime, requerido): Hora de inicio
- `hora_fin` (datetime, opcional): Hora de fin
- `tiempo_total_minutos` (float, requerido): Tiempo total en minutos
- `piezas_producidas` (int, requerido): Piezas producidas
- `tiempo_estandar_por_pieza` (float, opcional): Tiempo estándar por pieza
- `eficiencia` (float, opcional): Eficiencia calculada
- `cantidad_pausas` (int, opcional): Cantidad de pausas
- `tiempo_pausas_minutos` (float, opcional): Tiempo de pausas en minutos
- `operacion_id` (int, opcional): ID de la operación (para actualizar)

**Respuesta exitosa:**
```json
{
  "success": true,
  "operacion_id": 1,
  "message": "Operación guardada exitosamente"
}
```

### Eliminar Operación
**Endpoint:** `POST /index.php?action=produccion&method=eliminarOperacion`

**Permisos:** Acceso a producción

**Parámetros:**
- `id` (int, requerido): ID de la operación

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Operación eliminada exitosamente"
}
```

**Nota:** No se pueden eliminar operaciones de días finalizados.

### Guardar Retroceso
**Endpoint:** `POST /index.php?action=produccion&method=guardarRetroceso`

**Permisos:** Acceso a producción

**Parámetros:**
- `fecha` (date, requerido): Fecha del retroceso
- `operaria_nombre` (string, requerido): Nombre de la operaria
- `operacion_id` (int, opcional): ID de la operación relacionada
- `tipo_defecto` (string, requerido): Tipo de defecto
- `maquina` (string, opcional): Máquina donde ocurrió
- `minutos_perdidos` (int, requerido): Minutos perdidos
- `accion_correctiva` (string, opcional): Acción correctiva

**Respuesta exitosa:**
```json
{
  "success": true,
  "retroceso_id": 1,
  "message": "Retroceso registrado exitosamente"
}
```

### Obtener Operaciones
**Endpoint:** `GET /index.php?action=produccion&method=getOperaciones`

**Permisos:** Acceso a producción

**Parámetros (uno de los dos):**
- `registro_id` (int): ID del registro
- `fecha` (date) + `operaria` (string): Fecha y nombre de operaria

**Respuesta exitosa:**
```json
{
  "success": true,
  "operaciones": [
    {
      "id": 1,
      "codigo_operacion": "OP-20250128-001",
      "nombre_operacion": "Corte de tela",
      "maquina_usada": "Cortadora",
      "hora_inicio": "2025-01-28 08:00:00",
      "hora_fin": "2025-01-28 10:00:00",
      "tiempo_total_minutos": 120.00,
      "piezas_producidas": 50,
      "eficiencia": 95.5
    }
  ]
}
```

### Obtener Operarias del Día
**Endpoint:** `GET /index.php?action=produccion&method=getOperariasDelDia`

**Permisos:** Acceso a producción

**Parámetros:**
- `fecha` (date, opcional): Fecha (por defecto: hoy)

**Respuesta exitosa:**
```json
{
  "success": true,
  "operarias": [
    {
      "id": 1,
      "operaria_nombre": "María",
      "fecha": "2025-01-28",
      "turno": "mañana",
      "meta_dia": 100,
      "maquina_asignada": "Máquina 1"
    }
  ]
}
```

**Nota:** Los operarios solo ven sus propios registros. Los administradores ven todos.

### Obtener Retrocesos
**Endpoint:** `GET /index.php?action=produccion&method=getRetrocesos&registro_id={id}`

**Permisos:** Acceso a producción

**Parámetros:**
- `registro_id` (int, requerido): ID del registro

**Respuesta exitosa:**
```json
{
  "success": true,
  "retrocesos": [
    {
      "id": 1,
      "tipo_defecto": "Defecto de costura",
      "maquina": "Máquina 1",
      "minutos_perdidos": 30,
      "accion_correctiva": "Ajuste de tensión"
    }
  ]
}
```

### Buscar Operarias
**Endpoint:** `GET /index.php?action=produccion&method=buscarOperarias&termino={termino}`

**Permisos:** Acceso a producción (solo administradores)

**Parámetros:**
- `termino` (string, requerido): Término de búsqueda

**Respuesta exitosa:**
```json
{
  "success": true,
  "operarias": [
    {
      "nombre": "María",
      "ultima_fecha": "2025-01-28"
    }
  ]
}
```

### Generar Código de Operación
**Endpoint:** `GET /index.php?action=produccion&method=generarCodigoOperacion&fecha={fecha}`

**Permisos:** Acceso a producción

**Parámetros:**
- `fecha` (date, opcional): Fecha (por defecto: hoy)

**Respuesta exitosa:**
```json
{
  "success": true,
  "codigo_operacion": "OP-20250128-001"
}
```

### Obtener Resumen del Día
**Endpoint:** `GET /index.php?action=produccion&method=getResumenDia&fecha={fecha}`

**Permisos:** Acceso a producción

**Parámetros:**
- `fecha` (date, opcional): Fecha (por defecto: hoy)

**Respuesta exitosa:**
```json
{
  "success": true,
  "total_operarias": 5,
  "total_operaciones": 25,
  "prendas_terminadas": 500,
  "prendas_empezadas": 100,
  "tiempo_total_minutos": 2400.00
}
```

### Finalizar Día
**Endpoint:** `POST /index.php?action=produccion&method=finalizarDia`

**Permisos:** Solo administradores

**Parámetros:**
- `fecha` (date, requerido): Fecha a finalizar
- `prendas_terminadas` (int, requerido): Prendas terminadas
- `prendas_empezadas` (int, requerido): Prendas empezadas
- `tiempo_total_minutos` (float, requerido): Tiempo total en minutos
- `observaciones` (string, opcional): Observaciones

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Día finalizado exitosamente"
}
```

### Verificar Día Finalizado
**Endpoint:** `GET /index.php?action=produccion&method=verificarDiaFinalizado&fecha={fecha}`

**Permisos:** Acceso a producción

**Parámetros:**
- `fecha` (date, opcional): Fecha (por defecto: hoy)

**Respuesta exitosa:**
```json
{
  "success": true,
  "finalizado": true
}
```

### Obtener Historial de Operación
**Endpoint:** `GET /index.php?action=produccion&method=getHistorialOperacion&id={id}`

**Permisos:** Acceso a producción

**Parámetros:**
- `id` (int, requerido): ID de la operación

**Respuesta exitosa:**
```json
{
  "success": true,
  "historial": [
    {
      "id": 1,
      "tipo_cambio": "inicio",
      "hora_inicio": "2025-01-28 08:00:00",
      "fecha_cambio": "2025-01-28 08:00:00"
    }
  ]
}
```

---

## ⚠️ Códigos de Error Comunes

| Código | Descripción |
|--------|-------------|
| `No autenticado` | Usuario no ha iniciado sesión |
| `No tiene permisos` | Usuario no tiene permisos para la acción |
| `Método no permitido` | Método HTTP incorrecto (debe ser GET o POST) |
| `Campo obligatorio faltante` | Falta un campo requerido |
| `Día finalizado` | No se puede modificar un día que ya está finalizado |

---

## 📝 Notas Importantes

1. **Autenticación:** Todos los endpoints (excepto login) requieren sesión activa
2. **Permisos:** Algunos endpoints tienen restricciones por rol
3. **Formato de Fechas:** Usar formato `YYYY-MM-DD` para fechas
4. **Formato de Datetime:** Usar formato `YYYY-MM-DD HH:MM:SS` para fechas y horas
5. **Operarios:** Los operarios solo pueden ver/modificar sus propios registros
6. **Administradores:** Tienen acceso completo a todos los endpoints

---

*Última actualización: 2025-01-28*

