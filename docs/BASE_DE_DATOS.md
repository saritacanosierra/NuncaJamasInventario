# 🗄️ Documentación de Base de Datos
## Sistema de Inventario - Ropa Infantil

Esta documentación describe la estructura completa de la base de datos del sistema.

---

## 📋 Índice

1. [Información General](#información-general)
2. [Diagrama de Relaciones](#diagrama-de-relaciones)
3. [Tablas del Sistema](#tablas-del-sistema)
4. [Índices y Claves](#índices-y-claves)
5. [Relaciones entre Tablas](#relaciones-entre-tablas)
6. [Consultas Comunes](#consultas-comunes)
7. [Mantenimiento](#mantenimiento)

---

## 📊 Información General

- **Nombre de la Base de Datos:** `inventario_ropa_infantil`
- **Motor:** MySQL 5.7+ / MariaDB 10.2+
- **Charset:** `utf8mb4`
- **Collation:** `utf8mb4_general_ci` o `utf8mb4_unicode_ci`

---

## 🔗 Diagrama de Relaciones

```
usuarios
  ├── ventas (usuario_id)
  ├── gastos (usuario_id)
  ├── inversiones (usuario_id)
  ├── registros_produccion (usuario_id)
  └── tareas (usuario_id)

categorias
  └── productos (categoria_id)

productos
  └── ventas_detalle (producto_id)

clientes
  └── ventas (cliente_id)

ventas
  ├── ventas_detalle (venta_id)
  └── usuarios (usuario_id)

categorias_gastos
  ├── gastos (categoria)
  └── inversiones (categoria)

registros_produccion
  ├── operaciones_produccion (registro_id)
  └── retrocesos_produccion (registro_id)

operaciones_produccion
  ├── historial_operaciones_produccion (operacion_id)
  └── retrocesos_produccion (operacion_id)

cierres_dia_produccion
  └── usuarios (usuario_id)
```

---

## 📑 Tablas del Sistema

### 1. `usuarios`

Almacena información de los usuarios del sistema.

| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | INT(11) | ID único del usuario | PRIMARY KEY, AUTO_INCREMENT |
| `nombre` | VARCHAR(100) | Nombre completo | NOT NULL |
| `email` | VARCHAR(100) | Email del usuario | NOT NULL, UNIQUE |
| `password` | VARCHAR(255) | Contraseña hasheada | NOT NULL |
| `rol` | ENUM | Rol del usuario | 'admin', 'cajero', 'operario', NULL |
| `activo` | TINYINT(1) | Estado del usuario | DEFAULT 1 |
| `fecha_creacion` | DATETIME | Fecha de creación | DEFAULT CURRENT_TIMESTAMP |
| `fecha_actualizacion` | DATETIME | Última actualización | ON UPDATE CURRENT_TIMESTAMP |

**Índices:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`email`)

**Relaciones:**
- `ventas.usuario_id` → `usuarios.id`
- `gastos.usuario_id` → `usuarios.id`
- `inversiones.usuario_id` → `usuarios.id`
- `registros_produccion.usuario_id` → `usuarios.id`
- `tareas.usuario_id` → `usuarios.id`

---

### 2. `categorias`

Categorías de productos.

| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | INT(11) | ID único | PRIMARY KEY, AUTO_INCREMENT |
| `nombre` | VARCHAR(100) | Nombre de la categoría | NOT NULL, UNIQUE |
| `descripcion` | TEXT | Descripción | NULL |
| `fecha_creacion` | DATETIME | Fecha de creación | DEFAULT CURRENT_TIMESTAMP |

**Índices:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`nombre`)

**Relaciones:**
- `productos.categoria_id` → `categorias.id`

---

### 3. `productos`

Productos del inventario.

| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | INT(11) | ID único | PRIMARY KEY, AUTO_INCREMENT |
| `codigo_barras` | VARCHAR(50) | Código de barras EAN-13 | NOT NULL, UNIQUE |
| `nombre` | VARCHAR(200) | Nombre del producto | NOT NULL |
| `descripcion` | TEXT | Descripción | NULL |
| `color` | VARCHAR(50) | Color | NULL |
| `talla` | VARCHAR(20) | Talla | NULL |
| `precio_costo` | DECIMAL(10,2) | Precio de costo | DEFAULT 0.00 |
| `precio_venta` | DECIMAL(10,2) | Precio de venta | DEFAULT 0.00 |
| `categoria_id` | INT(11) | ID de categoría | FOREIGN KEY |
| `stock` | INT(11) | Stock actual | DEFAULT 0 |
| `stock_minimo` | INT(11) | Stock mínimo | DEFAULT 0 |
| `estado` | ENUM | Estado | 'activo', 'inactivo', DEFAULT 'activo' |
| `foto` | VARCHAR(255) | Ruta de la foto | NULL |
| `fecha_creacion` | DATETIME | Fecha de creación | DEFAULT CURRENT_TIMESTAMP |
| `fecha_actualizacion` | DATETIME | Última actualización | ON UPDATE CURRENT_TIMESTAMP |

**Índices:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`codigo_barras`)
- INDEX (`categoria_id`)

**Relaciones:**
- `productos.categoria_id` → `categorias.id`
- `ventas_detalle.producto_id` → `productos.id`

---

### 4. `clientes`

Clientes del sistema.

| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | INT(11) | ID único | PRIMARY KEY, AUTO_INCREMENT |
| `nombre` | VARCHAR(200) | Nombre completo | NOT NULL |
| `cedula_nit` | VARCHAR(50) | Cédula o NIT | NOT NULL, UNIQUE |
| `email` | VARCHAR(100) | Email | NULL |
| `telefono` | VARCHAR(20) | Teléfono | NULL |
| `direccion` | TEXT | Dirección | NULL |
| `fecha_creacion` | DATETIME | Fecha de creación | DEFAULT CURRENT_TIMESTAMP |
| `fecha_actualizacion` | DATETIME | Última actualización | ON UPDATE CURRENT_TIMESTAMP |

**Índices:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`cedula_nit`)

**Relaciones:**
- `ventas.cliente_id` → `clientes.id`

---

### 5. `ventas`

Ventas realizadas.

| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | INT(11) | ID único | PRIMARY KEY, AUTO_INCREMENT |
| `numero_factura` | VARCHAR(50) | Número de factura | NOT NULL, UNIQUE |
| `cliente_id` | INT(11) | ID del cliente | FOREIGN KEY, NULL |
| `usuario_id` | INT(11) | ID del usuario que registró | FOREIGN KEY, NOT NULL |
| `fecha` | DATE | Fecha de la venta | NOT NULL |
| `subtotal` | DECIMAL(10,2) | Subtotal | DEFAULT 0.00 |
| `descuento` | DECIMAL(10,2) | Descuento | DEFAULT 0.00 |
| `total` | DECIMAL(10,2) | Total | DEFAULT 0.00 |
| `metodo_pago` | VARCHAR(50) | Método de pago | NULL |
| `observaciones` | TEXT | Observaciones | NULL |
| `fecha_creacion` | DATETIME | Fecha de creación | DEFAULT CURRENT_TIMESTAMP |

**Índices:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`numero_factura`)
- INDEX (`cliente_id`)
- INDEX (`usuario_id`)
- INDEX (`fecha`)

**Relaciones:**
- `ventas.cliente_id` → `clientes.id`
- `ventas.usuario_id` → `usuarios.id`
- `ventas_detalle.venta_id` → `ventas.id`

---

### 6. `ventas_detalle`

Detalle de productos en cada venta.

| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | INT(11) | ID único | PRIMARY KEY, AUTO_INCREMENT |
| `venta_id` | INT(11) | ID de la venta | FOREIGN KEY, NOT NULL |
| `producto_id` | INT(11) | ID del producto | FOREIGN KEY, NOT NULL |
| `cantidad` | INT(11) | Cantidad vendida | NOT NULL |
| `precio_unitario` | DECIMAL(10,2) | Precio unitario | NOT NULL |
| `subtotal` | DECIMAL(10,2) | Subtotal | NOT NULL |

**Índices:**
- PRIMARY KEY (`id`)
- INDEX (`venta_id`)
- INDEX (`producto_id`)

**Relaciones:**
- `ventas_detalle.venta_id` → `ventas.id`
- `ventas_detalle.producto_id` → `productos.id`

---

### 7. `categorias_gastos`

Categorías de gastos e inversiones.

| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | INT(11) | ID único | PRIMARY KEY, AUTO_INCREMENT |
| `nombre` | VARCHAR(100) | Nombre de la categoría | NOT NULL, UNIQUE |
| `descripcion` | TEXT | Descripción | NULL |
| `fecha_creacion` | DATETIME | Fecha de creación | DEFAULT CURRENT_TIMESTAMP |

**Índices:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`nombre`)

**Relaciones:**
- `gastos.categoria` → `categorias_gastos.nombre`
- `inversiones.categoria` → `categorias_gastos.nombre`

---

### 8. `gastos`

Gastos registrados.

| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | INT(11) | ID único | PRIMARY KEY, AUTO_INCREMENT |
| `concepto` | VARCHAR(200) | Concepto del gasto | NOT NULL |
| `monto` | DECIMAL(10,2) | Monto del gasto | NOT NULL |
| `categoria` | VARCHAR(100) | Categoría | NOT NULL |
| `fecha` | DATE | Fecha del gasto | NOT NULL |
| `descripcion` | TEXT | Descripción | NULL |
| `usuario_id` | INT(11) | ID del usuario | FOREIGN KEY, NOT NULL |
| `fecha_creacion` | DATETIME | Fecha de creación | DEFAULT CURRENT_TIMESTAMP |

**Índices:**
- PRIMARY KEY (`id`)
- INDEX (`categoria`)
- INDEX (`fecha`)
- INDEX (`usuario_id`)

**Relaciones:**
- `gastos.usuario_id` → `usuarios.id`
- `gastos.categoria` → `categorias_gastos.nombre`

---

### 9. `inversiones`

Inversiones registradas.

| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | INT(11) | ID único | PRIMARY KEY, AUTO_INCREMENT |
| `concepto` | VARCHAR(200) | Concepto de la inversión | NOT NULL |
| `monto` | DECIMAL(10,2) | Monto de la inversión | NOT NULL |
| `categoria` | VARCHAR(100) | Categoría | NOT NULL |
| `fecha` | DATE | Fecha de la inversión | NOT NULL |
| `descripcion` | TEXT | Descripción | NULL |
| `usuario_id` | INT(11) | ID del usuario | FOREIGN KEY, NOT NULL |
| `fecha_creacion` | DATETIME | Fecha de creación | DEFAULT CURRENT_TIMESTAMP |

**Índices:**
- PRIMARY KEY (`id`)
- INDEX (`categoria`)
- INDEX (`fecha`)
- INDEX (`usuario_id`)

**Relaciones:**
- `inversiones.usuario_id` → `usuarios.id`
- `inversiones.categoria` → `categorias_gastos.nombre`

---

### 10. `tareas`

Tareas de la agenda.

| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | INT(11) | ID único | PRIMARY KEY, AUTO_INCREMENT |
| `titulo` | VARCHAR(200) | Título de la tarea | NOT NULL |
| `descripcion` | TEXT | Descripción | NULL |
| `fecha` | DATE | Fecha de la tarea | NOT NULL |
| `hora` | TIME | Hora de la tarea | NULL |
| `completada` | TINYINT(1) | Estado de completada | DEFAULT 0 |
| `usuario_id` | INT(11) | ID del usuario | FOREIGN KEY, NOT NULL |
| `fecha_creacion` | DATETIME | Fecha de creación | DEFAULT CURRENT_TIMESTAMP |
| `fecha_actualizacion` | DATETIME | Última actualización | ON UPDATE CURRENT_TIMESTAMP |

**Índices:**
- PRIMARY KEY (`id`)
- INDEX (`fecha`)
- INDEX (`usuario_id`)

**Relaciones:**
- `tareas.usuario_id` → `usuarios.id`

---

### 11. `registros_produccion`

Registros diarios de producción por operaria.

| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | INT(11) | ID único | PRIMARY KEY, AUTO_INCREMENT |
| `operaria_nombre` | VARCHAR(100) | Nombre de la operaria | NOT NULL |
| `fecha` | DATE | Fecha del registro | NOT NULL |
| `turno` | VARCHAR(50) | Turno (mañana/tarde/noche) | NOT NULL |
| `meta_dia` | INT(11) | Meta del día | DEFAULT 0 |
| `maquina_asignada` | VARCHAR(100) | Máquina asignada | NULL |
| `tiempo_total_trabajado` | DECIMAL(10,2) | Tiempo total trabajado (minutos) | DEFAULT 0.00 |
| `tiempo_perdido_retrocesos` | DECIMAL(10,2) | Tiempo perdido en retrocesos | DEFAULT 0.00 |
| `piezas_producidas` | INT(11) | Piezas producidas | DEFAULT 0 |
| `eficiencia_promedio` | DECIMAL(5,2) | Eficiencia promedio (%) | DEFAULT 0.00 |
| `usuario_id` | INT(11) | ID del usuario | FOREIGN KEY, NOT NULL |
| `fecha_creacion` | DATETIME | Fecha de creación | DEFAULT CURRENT_TIMESTAMP |
| `fecha_actualizacion` | DATETIME | Última actualización | ON UPDATE CURRENT_TIMESTAMP |

**Índices:**
- PRIMARY KEY (`id`)
- INDEX (`fecha`)
- INDEX (`operaria_nombre`)
- INDEX (`usuario_id`)

**Relaciones:**
- `registros_produccion.usuario_id` → `usuarios.id`
- `operaciones_produccion.registro_id` → `registros_produccion.id`
- `retrocesos_produccion.registro_id` → `registros_produccion.id`

---

### 12. `operaciones_produccion`

Operaciones realizadas en producción.

| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | INT(11) | ID único | PRIMARY KEY, AUTO_INCREMENT |
| `registro_id` | INT(11) | ID del registro | FOREIGN KEY, NOT NULL |
| `codigo_operacion` | VARCHAR(50) | Código único de operación | NULL |
| `nombre_operacion` | VARCHAR(200) | Nombre de la operación | NOT NULL |
| `maquina_usada` | VARCHAR(100) | Máquina usada | NULL |
| `hora_inicio` | DATETIME | Hora de inicio | NOT NULL |
| `hora_fin` | DATETIME | Hora de fin | NULL |
| `tiempo_total_minutos` | DECIMAL(10,2) | Tiempo total (minutos) | DEFAULT 0.00 |
| `piezas_producidas` | INT(11) | Piezas producidas | DEFAULT 0 |
| `tiempo_estandar_por_pieza` | DECIMAL(10,2) | Tiempo estándar por pieza | DEFAULT 0.00 |
| `eficiencia` | DECIMAL(5,2) | Eficiencia (%) | DEFAULT 0.00 |
| `cantidad_pausas` | INT(11) | Cantidad de pausas | DEFAULT 0 |
| `tiempo_pausas_minutos` | DECIMAL(10,2) | Tiempo de pausas (minutos) | DEFAULT 0.00 |
| `fecha_creacion` | DATETIME | Fecha de creación | DEFAULT CURRENT_TIMESTAMP |
| `fecha_actualizacion` | DATETIME | Última actualización | ON UPDATE CURRENT_TIMESTAMP |

**Índices:**
- PRIMARY KEY (`id`)
- INDEX (`registro_id`)
- INDEX (`codigo_operacion`)

**Relaciones:**
- `operaciones_produccion.registro_id` → `registros_produccion.id`
- `historial_operaciones_produccion.operacion_id` → `operaciones_produccion.id`
- `retrocesos_produccion.operacion_id` → `operaciones_produccion.id`

---

### 13. `retrocesos_produccion`

Retrocesos o defectos en producción.

| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | INT(11) | ID único | PRIMARY KEY, AUTO_INCREMENT |
| `registro_id` | INT(11) | ID del registro | FOREIGN KEY, NOT NULL |
| `operacion_id` | INT(11) | ID de la operación | FOREIGN KEY, NULL |
| `tipo_defecto` | VARCHAR(200) | Tipo de defecto | NOT NULL |
| `maquina` | VARCHAR(100) | Máquina donde ocurrió | NULL |
| `minutos_perdidos` | INT(11) | Minutos perdidos | DEFAULT 0 |
| `accion_correctiva` | TEXT | Acción correctiva | NULL |
| `fecha_creacion` | DATETIME | Fecha de creación | DEFAULT CURRENT_TIMESTAMP |

**Índices:**
- PRIMARY KEY (`id`)
- INDEX (`registro_id`)
- INDEX (`operacion_id`)

**Relaciones:**
- `retrocesos_produccion.registro_id` → `registros_produccion.id`
- `retrocesos_produccion.operacion_id` → `operaciones_produccion.id`

---

### 14. `historial_operaciones_produccion`

Historial de cambios en operaciones (inicio, pausa, reanudar, fin).

| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | INT(11) | ID único | PRIMARY KEY, AUTO_INCREMENT |
| `operacion_id` | INT(11) | ID de la operación | FOREIGN KEY, NOT NULL |
| `tipo_cambio` | VARCHAR(50) | Tipo de cambio | NOT NULL |
| `hora_inicio` | DATETIME | Hora de inicio | NULL |
| `hora_fin` | DATETIME | Hora de fin | NULL |
| `fecha_cambio` | DATETIME | Fecha del cambio | DEFAULT CURRENT_TIMESTAMP |

**Índices:**
- PRIMARY KEY (`id`)
- INDEX (`operacion_id`)

**Relaciones:**
- `historial_operaciones_produccion.operacion_id` → `operaciones_produccion.id`

---

### 15. `cierres_dia_produccion`

Cierres diarios de producción (bitácora).

| Campo | Tipo | Descripción | Restricciones |
|-------|------|-------------|---------------|
| `id` | INT(11) | ID único | PRIMARY KEY, AUTO_INCREMENT |
| `fecha` | DATE | Fecha del cierre | NOT NULL |
| `usuario_id` | INT(11) | ID del usuario que cerró | FOREIGN KEY, NOT NULL |
| `prendas_terminadas` | INT(11) | Prendas terminadas | DEFAULT 0 |
| `prendas_empezadas` | INT(11) | Prendas empezadas | DEFAULT 0 |
| `tiempo_total_minutos` | DECIMAL(10,2) | Tiempo total (minutos) | DEFAULT 0.00 |
| `total_operarias` | INT(11) | Total de operarias | DEFAULT 0 |
| `total_operaciones` | INT(11) | Total de operaciones | DEFAULT 0 |
| `observaciones` | TEXT | Observaciones | NULL |
| `finalizado` | TINYINT(1) | Día finalizado | DEFAULT 1 |
| `fecha_creacion` | DATETIME | Fecha de creación | DEFAULT CURRENT_TIMESTAMP |
| `fecha_actualizacion` | DATETIME | Última actualización | ON UPDATE CURRENT_TIMESTAMP |

**Índices:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`fecha`, `usuario_id`)
- INDEX (`fecha`)
- INDEX (`usuario_id`)

**Relaciones:**
- `cierres_dia_produccion.usuario_id` → `usuarios.id`

---

## 🔑 Índices y Claves

### Claves Primarias
Todas las tablas tienen una clave primaria `id` de tipo `INT(11) AUTO_INCREMENT`.

### Claves Únicas
- `usuarios.email`
- `productos.codigo_barras`
- `clientes.cedula_nit`
- `ventas.numero_factura`
- `categorias.nombre`
- `categorias_gastos.nombre`
- `cierres_dia_produccion` (fecha, usuario_id) - compuesta

### Índices
Se han creado índices en:
- Campos de búsqueda frecuente (`fecha`, `nombre`, `categoria`)
- Claves foráneas para mejorar JOINs
- Campos de ordenamiento común

---

## 🔗 Relaciones entre Tablas

### Relaciones Principales

1. **Usuarios → Múltiples tablas**
   - Un usuario puede tener múltiples ventas, gastos, inversiones, registros de producción y tareas

2. **Productos → Ventas**
   - Un producto puede estar en múltiples ventas (a través de `ventas_detalle`)

3. **Clientes → Ventas**
   - Un cliente puede tener múltiples ventas

4. **Categorías → Productos**
   - Una categoría puede tener múltiples productos

5. **Registros Producción → Operaciones**
   - Un registro puede tener múltiples operaciones

6. **Operaciones → Retrocesos**
   - Una operación puede tener múltiples retrocesos

---

## 📊 Consultas Comunes

### Obtener ventas de un cliente
```sql
SELECT v.*, c.nombre as cliente_nombre
FROM ventas v
LEFT JOIN clientes c ON v.cliente_id = c.id
WHERE v.cliente_id = ?
ORDER BY v.fecha DESC;
```

### Obtener productos con bajo stock
```sql
SELECT * FROM productos
WHERE stock <= stock_minimo
AND estado = 'activo'
ORDER BY stock ASC;
```

### Obtener resumen de producción del día
```sql
SELECT 
    COUNT(DISTINCT r.id) as total_operarias,
    COUNT(o.id) as total_operaciones,
    SUM(r.piezas_producidas) as prendas_terminadas,
    SUM(r.tiempo_total_trabajado) as tiempo_total
FROM registros_produccion r
LEFT JOIN operaciones_produccion o ON r.id = o.registro_id
WHERE r.fecha = ?
GROUP BY r.fecha;
```

### Obtener gastos por categoría en un periodo
```sql
SELECT 
    categoria,
    COUNT(*) as cantidad,
    SUM(monto) as total
FROM gastos
WHERE fecha BETWEEN ? AND ?
GROUP BY categoria
ORDER BY total DESC;
```

---

## 🔧 Mantenimiento

### Respaldos Recomendados

**Respaldo completo:**
```bash
mysqldump -u root -p inventario_ropa_infantil > backup_$(date +%Y%m%d).sql
```

**Respaldar solo estructura:**
```bash
mysqldump -u root -p --no-data inventario_ropa_infantil > estructura.sql
```

**Respaldar solo datos:**
```bash
mysqldump -u root -p --no-create-info inventario_ropa_infantil > datos.sql
```

### Optimización

**Analizar tablas:**
```sql
ANALYZE TABLE productos, ventas, clientes;
```

**Optimizar tablas:**
```sql
OPTIMIZE TABLE productos, ventas, clientes;
```

### Limpieza de Datos Antiguos

**Eliminar ventas antiguas (ejemplo: más de 2 años):**
```sql
DELETE FROM ventas_detalle 
WHERE venta_id IN (
    SELECT id FROM ventas 
    WHERE fecha < DATE_SUB(NOW(), INTERVAL 2 YEAR)
);

DELETE FROM ventas 
WHERE fecha < DATE_SUB(NOW(), INTERVAL 2 YEAR);
```

**⚠️ IMPORTANTE:** Siempre hacer respaldo antes de eliminar datos.

---

## 📝 Notas Importantes

1. **Integridad Referencial:** Algunas relaciones usan nombres de categorías en lugar de IDs (gastos, inversiones). Esto permite flexibilidad pero requiere mantener consistencia manualmente.

2. **Códigos de Barras:** Deben ser únicos y seguir formato EAN-13 (13 dígitos).

3. **Números de Factura:** Se generan automáticamente y deben ser únicos.

4. **Roles de Usuario:** Los roles están definidos como ENUM: 'admin', 'cajero', 'operario'.

5. **Fechas:** Todas las fechas se almacenan en formato DATE o DATETIME según corresponda.

---

*Última actualización: 2025-01-28*

