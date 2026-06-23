# 📑 MEMORY BANK — UPARCONTABLE
> **Propósito:** Context Bootstrap de una sola lectura. Al abrir este archivo, cualquier agente de IA o desarrollador Junior queda completamente en contexto sin revisar código. Actualizar este archivo al final de cada sesión de trabajo.
> **Última actualización:** 2026-06-23 · Hernis Mercado

---

## 1. RESUMEN EJECUTIVO

| Campo | Detalle |
|---|---|
| **Proyecto** | UparContable — MVP de Inventario y Contabilidad Básica |
| **Líder Técnico** | Hernis Mercado (Semi-Senior) |
| **Timeline** | Sprint crítico de 2 semanas — 2 clientes reales comprometidos |
| **Filosofía** | Cero sobreingeniería. Scope Creep bloqueado. Código pedagógico para Junior |
| **Idioma** | Todo el código, comentarios y documentación en **Español** |

---

## 2. ARQUITECTURA DEL WORKSPACE

```
/UparContable
  ├── /back     → API RESTful (Laravel 11 + Sanctum)
  └── /front    → SPA (React JS + plantilla CoreUI)
```

**Regla de oro:** Nunca mezclar código del `/back` en `/front` ni viceversa. Siempre especificar la ruta exacta del archivo al generar código.

---

## 3. ESQUEMA DE BASE DE DATOS (Producción)

**Motor:** MySQL · **DB:** `upar_contable` · **ORM:** Eloquent con migraciones versionadas

```
roles
  id | slug (unique: 'admin','operador') | nombre | timestamps

users  ← tabla nativa Laravel, modificada con ALTER TABLE
  id | role_id (FK→roles ON DELETE RESTRICT, nullable) | name | email
  password | remember_token | email_verified_at | timestamps

productos  ← SoftDeletes habilitado (deleted_at)
  id | sku (unique, indexed) | nombre | descripcion (nullable)
  precio_costo DECIMAL(12,2) | precio_venta DECIMAL(12,2)
  stock_actual INT default:0 | stock_minimo INT default:5 | timestamps | deleted_at

movimientos_inventario  ← Kardex / auditoría física
  id | producto_id (FK→productos ON DELETE RESTRICT)
  user_id (FK→users ON DELETE RESTRICT)
  tipo ENUM('entrada','salida') | cantidad INT | motivo VARCHAR(255) | timestamps

flujo_caja  ← Libro contable
  id | user_id (FK→users ON DELETE RESTRICT)
  movimiento_inventario_id (FK→movimientos_inventario, NULLABLE ON DELETE RESTRICT)
  tipo ENUM('ingreso','egreso') | monto DECIMAL(12,2)
  concepto VARCHAR(255) | fecha_transaccion TIMESTAMP (indexed) | timestamps

personal_access_tokens  ← Generada por Sanctum (no modificar)
```

---

## 4. REGLAS DE NEGOCIO IRROMPIBLES

### 4.1 Venta Atómica (DB::transaction)
Cuando se registra una venta (`tipo=salida`, `motivo` inicia con `'venta'`):
1. Se inserta en `movimientos_inventario` (tipo: `salida`).
2. Se decrementa `productos.stock_actual` con `decrement()` atómico SQL.
3. Se inserta en `flujo_caja` (tipo: `ingreso`, monto = `cantidad × precio_venta`).
4. **Si cualquier paso falla → rollback total. Los libros jamás se descuadran.**

### 4.2 Control de Roles (RBAC)
| Acción | Admin | Operador |
|---|:---:|:---:|
| Ver catálogo y alertas de stock | ✅ | ✅ |
| Registrar movimientos / ventas | ✅ | ✅ |
| Ver historial de caja | ✅ | ✅ |
| Crear / editar / eliminar productos | ✅ | ❌ |
| Registrar egresos manuales | ✅ | ❌ |
| Ver reporte financiero (utilidad neta) | ✅ | ❌ |

### 4.3 Alertas de Stock
`GET /api/productos/alertas` usa `whereRaw('stock_actual <= stock_minimo')` — comparación columna-a-columna, sin valor hardcodeado.

### 4.4 SoftDeletes en Productos
Nunca se borra físicamente un producto. `DELETE /api/productos/{id}` rellena `deleted_at`. Esto preserva el historial contable y del Kardex intacto.

---

## 5. MAPA DE LA API (15 endpoints activos)

**Base URL:** `http://localhost:8000/api`
**Auth:** Todas las rutas (excepto `/login`) requieren `Authorization: Bearer {token}` + `Accept: application/json`.

### Rutas Públicas
| Método | Endpoint | Descripción |
|---|---|---|
| `POST` | `/login` | Autentica y devuelve Bearer token + `rol` del usuario |

### Rutas Protegidas — Admin + Operador (`role:admin,operador`)
| Método | Endpoint | Descripción |
|---|---|---|
| `GET` | `/me` | Perfil del usuario autenticado |
| `POST` | `/logout` | Revoca el token actual |
| `GET` | `/productos` | Catálogo paginado (`?per_page=N`) |
| `GET` | `/productos/{id}` | Detalle de un producto |
| `GET` | `/productos/alertas` | Stock bajo o agotado (sin paginación) |
| `GET` | `/movimientos` | Kardex paginado (`?producto_id`, `?tipo`) |
| `POST` | `/movimientos` | Registrar movimiento manual de inventario |
| `GET` | `/caja` | Historial flujo de caja (`?tipo`, `?fecha_inicio`, `?fecha_fin`) |

### Rutas Protegidas — Solo Admin (`role:admin`)
| Método | Endpoint | Descripción |
|---|---|---|
| `POST` | `/productos` | Crear producto |
| `PUT` | `/productos/{id}` | Actualizar producto (parcial con `sometimes`) |
| `DELETE` | `/productos/{id}` | Borrado lógico (SoftDelete) |
| `POST` | `/caja/egreso` | Egreso manual directo |
| `GET` | `/reportes/financiero` | Balance por rango (`?fecha_inicio=Y-m-d&fecha_fin=Y-m-d`) |
| `POST` | `/ventas` | Venta atómica directa |

### Formato de respuesta del Login
```json
{
  "access_token": "1|xyz...",
  "token_type": "Bearer",
  "user": { "id": 1, "name": "...", "email": "...", "rol": "admin" }
}
```

---

## 6. MAPA DE ARCHIVOS DEL BACKEND (`/back`)

### Modelos (`app/Models/`)
| Archivo | Traits clave | Notas |
|---|---|---|
| `User.php` | `HasApiTokens`, `HasFactory`, `Notifiable` | Relaciones: `role`, `movimientosInventario`, `flujoCaja` |
| `Role.php` | — | Helpers: `esAdmin()`, `esOperador()` |
| `Producto.php` | `HasFactory`, `SoftDeletes` | Scope: `scopeBajoStock()` |
| `MovimientoInventario.php` | — | Relaciones: `producto` (con `withTrashed`), `usuario`, `flujoCaja` |
| `FlujoCaja.php` | — | Relaciones: `usuario`, `movimientoInventario` |

### Servicios (`app/Services/`)
| Archivo | Responsabilidad |
|---|---|
| `VentaService.php` | Transacción atómica de 4 pasos: validar stock → movimiento → decrement → flujo_caja |
| `MovimientoService.php` | Orquestador: reutiliza `VentaService` si `motivo` inicia con `'venta'`; procesa entradas y bajas por su cuenta |
| `CajaService.php` | Egresos manuales desvinculados del inventario |

### Controladores (`app/Http/Controllers/Api/`)
| Archivo | Endpoints que maneja |
|---|---|
| `AuthController.php` | `login`, `logout`, `me` |
| `ProductoController.php` | CRUD + `alertas` |
| `MovimientoController.php` | `index` (Kardex), `store` |
| `CajaController.php` | `index`, `egreso` |
| `ReporteController.php` | `financiero` |
| `VentaController.php` | `registrar` (acceso directo Admin) |

### Form Requests (`app/Http/Requests/`)
| Archivo | Valida |
|---|---|
| `ProductoStoreRequest.php` | SKU único, precios `gt:0`, campos obligatorios |
| `ProductoUpdateRequest.php` | `Rule::unique()->ignore()` para SKU propio; campos `sometimes` |
| `MovimientoStoreRequest.php` | `tipo` ENUM, `producto_id` existe, `cantidad min:1` |
| `EgresoStoreRequest.php` | `monto gt:0`, `fecha_transaccion` opcional |
| `ReporteFinancieroRequest.php` | Ambas fechas `Y-m-d`, `fecha_fin gte:fecha_inicio` |
| `RegistrarVentaRequest.php` | `producto_id` existe, `cantidad min:1`, `concepto` |

### API Resources (`app/Http/Resources/`)
| Archivo | Campos clave en salida JSON |
|---|---|
| `ProductoResource.php` | Precios como string 2 dec., `margen_ganancia_pct`, `en_alerta`, `stock_agotado` |
| `MovimientoInventarioResource.php` | `whenLoaded` para `producto` y `responsable` (anti N+1) |
| `FlujoCajaResource.php` | `monto` 2 dec. fijos, `whenLoaded` para `registrado_por` |

### Middleware (`app/Http/Middleware/`)
| Archivo | Uso |
|---|---|
| `CheckRole.php` | `->middleware('role:admin')` o `'role:admin,operador'` — 403 JSON si falla |

---

## 7. SEEDERS Y FACTORIES (`/back/database/`)

### Credenciales de prueba (siempre disponibles tras `db:seed`)
| Email | Contraseña | Rol |
|---|---|---|
| `admin@uparcontable.com` | `Admin@1234` | `admin` |
| `operador@uparcontable.com` | `Oper@1234` | `operador` |

### Orden de ejecución del `DatabaseSeeder`
```
RoleSeeder → UserSeeder → Producto::factory(20)->create()
```

### Distribución de stock en `ProductoFactory` (para pruebas de alertas)
- **~50%** stock saludable (no activa alerta)
- **~25%** stock bajo (stock ≤ stock_minimo, alerta activa)
- **~25%** stock agotado (stock = 0, alerta crítica)

### Estados adicionales de `ProductoFactory`
```php
Producto::factory()->conStock(50)->create();  // stock garantizado
Producto::factory()->sinStock()->create();     // stock = 0
Producto::factory()->enAlerta()->create();     // stock = 2, minimo = 5
```

---

## 8. CONFIGURACIÓN DEL ENTORNO

```bash
# Archivo: /back/.env (ya creado, con APP_KEY generada)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=upar_contable
DB_USERNAME=root
DB_PASSWORD=        # ← completar con contraseña local

# Comandos para levantar desde cero:
php artisan migrate:fresh --seed   # resetear BD + sembrar datos
php artisan serve --port=8000      # levantar servidor de desarrollo
```

---

## 9. PATRONES Y CONVENCIONES ESTABLECIDAS

> **Leer SIEMPRE antes de generar código nuevo.** Respetar estos patrones evita refactorizaciones.

| Patrón | Implementación |
|---|---|
| **Skinny Controller** | Cero lógica de negocio en controladores. Todo va en Services |
| **Precisión monetaria** | `bcmul()`, `bcsub()`, `number_format(x, 2, '.', '')` — nunca aritmética PHP directa |
| **Anti-overselling** | `lockForUpdate()` dentro de `DB::transaction()` antes de validar stock |
| **Stock atómico** | `$producto->decrement('stock_actual', $n)` — nunca `$producto->stock_actual -= $n; $producto->save()` |
| **Anti-N+1** | `with(['relation'])` en queries de listado; `whenLoaded()` en Resources; `loadMissing()` en Auth |
| **Validación de roles** | `motivo` que inicia con `'venta'` (case-insensitive) dispara el ingreso en `flujo_caja` |
| **SoftDelete primero** | `findOrFail()` en modelos con SoftDeletes excluye automáticamente registros eliminados |
| **Fechas en reporte** | `whereDate('fecha_transaccion', '>=', $fecha)` — inclusive en ambos extremos |
| **Parámetro de ruta** | Todos los `apiResource` usan `->parameters(['productos' => 'id'])` para consistencia |

---

## 10. ESTADO ACTUAL Y PRÓXIMOS PASOS

### ✅ BACKEND COMPLETADO (100%)
- [x] Migraciones (5 tablas + `personal_access_tokens` de Sanctum)
- [x] Modelos con relaciones, traits y scopes
- [x] Seeders + Factory con datos de prueba coherentes
- [x] Módulo Inventario: CRUD de productos + alertas de stock
- [x] Módulo Kardex: movimientos de entrada/salida con transacción atómica
- [x] Módulo Contable: flujo de caja, egresos manuales, reporte financiero por fecha
- [x] **Autenticación**: Laravel Sanctum (Personal Access Tokens) — login/logout/me
- [x] **RBAC**: Middleware `CheckRole` registrado como alias `'role'` en `bootstrap/app.php`
- [x] **Rutas blindadas**: 3 capas en `api.php` — pública / compartida / solo-admin

### 🟡 FRONTEND (`/front`) — SIGUIENTE SPRINT
- [ ] **Axios base client** con interceptores para inyectar `Authorization: Bearer {token}` automáticamente en cada request y manejar el `401` (redirigir al login) y el `403` (mostrar error de permisos).
- [ ] **AuthContext** (React Context): almacenar `{ token, user, rol }` post-login; limpiar en logout.
- [ ] **Rutas protegidas** en React Router: `<PrivateRoute>` que verifica `token` y `<AdminRoute>` que verifica `rol === 'admin'`.
- [ ] **Módulo Inventario** (vista): tabla de productos con paginación, badge de alertas de stock, formulario de creación/edición (solo visible para Admin).
- [ ] **Módulo Kardex** (vista): historial de movimientos con filtros por producto y tipo.
- [ ] **Módulo Caja** (vista): historial de transacciones con filtros de fecha y tipo.
- [ ] **Módulo Reportes** (vista, solo Admin): selector de rango de fechas, tarjetas con `total_ingresos`, `total_egresos` y `utilidad_neta`.

---

## 11. DECISIONES TÉCNICAS IMPORTANTES (No revertir sin consultar a Hernis)

1. **Sanctum sobre JWT**: Se eligió Sanctum por simplicidad en el MVP. Los tokens se almacenan en la BD (`personal_access_tokens`). Si se necesita stateless puro, migrar a `tymon/jwt-auth` en un sprint posterior.
2. **Un token activo por usuario**: El `login` ejecuta `$user->tokens()->delete()` antes de emitir el nuevo token. Si se necesita multi-dispositivo, comentar esa línea.
3. **Alertas sin paginación**: `GET /api/productos/alertas` devuelve **toda** la colección sin paginar, porque el dashboard del Admin necesita el total de alertas activas de un vistazo.
4. **`POST /api/ventas` solo para Admin**: Los Operadores registran ventas via `POST /api/movimientos` con `tipo=salida` y `motivo=venta_*`. El endpoint `/ventas` es un acceso directo para el Admin.
5. **`fecha_transaccion` en flujo_caja**: Permite registrar egresos con fecha retroactiva. El campo `created_at` no se usa para reportes — siempre usar `fecha_transaccion`.