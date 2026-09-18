# E-commerce API

REST API segura para un sistema de e-commerce desarrollado con **Laravel 12**, **MySQL**, **JWT** y **Stripe**. La API permite gestionar usuarios, productos, órdenes y pagos, además de ofrecer documentación interactiva mediante Swagger/OpenAPI.

## Tecnologías

- PHP 8.4+
- Laravel 12
- MySQL 8
- Composer
- JWT Authentication (`tymon/jwt-auth`)
- Stripe PHP SDK
- Stripe CLI para pruebas de webhooks
- L5-Swagger / OpenAPI
- Insomnia para pruebas de endpoints

---

## 1. Requisitos previos

Antes de ejecutar el proyecto, instalar:

- PHP >= 8.2
- Composer
- MySQL >= 8
- Node.js y npm
- Git

Verificar las instalaciones:

```bash
php -v
composer -V
mysql --version
node -v
npm -v
git --version
```

Para las pruebas de pagos locales también se necesita Stripe CLI:

```bash
stripe --version
```

---

## 2. Clonar el proyecto

Clonar el repositorio:

```bash
git clone https://github.com/river717/api-ecommerce.git
```

Entrar al proyecto:

```bash
cd api-ecommerce
```

---

## 3. Instalar dependencias

Instalar las dependencias PHP:

```bash
composer install
```

Si se necesita instalar las dependencias frontend:

```bash
npm install
```

---

## 4. Configurar el archivo `.env`

Crear el archivo `.env` a partir del archivo de ejemplo:

```bash
cp .env.example .env
```

En Windows PowerShell también se puede utilizar:

```powershell
Copy-Item .env.example .env
```

Configurar la conexión a MySQL en `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce_api
DB_USERNAME=root
DB_PASSWORD=
```

Crear previamente la base de datos `ecommerce_api` en MySQL si todavía no existe.

---

## 5. Generar la clave de Laravel

Ejecutar:

```bash
php artisan key:generate
```

Esto genera automáticamente `APP_KEY` en `.env`.

---

## 6. Configurar JWT

El proyecto utiliza JWT para proteger los endpoints privados.

La clave secreta de JWT se genera con:

```bash
php artisan jwt:secret
```

Esto configura automáticamente `JWT_SECRET` en `.env`.

---

## 7. Configurar Stripe

Agregar las credenciales de Stripe en `.env`:

```env
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

Para desarrollo se deben utilizar las claves del entorno de pruebas de Stripe.

**Nunca subir las claves reales al repositorio.**

El archivo `.env` está excluido mediante `.gitignore`. El repositorio solamente contiene `.env.example` con los valores vacíos.

---

## 8. Ejecutar las migraciones

Crear las tablas de la base de datos:

```bash
php artisan migrate
```

El proyecto incluye migraciones para:

- `users`
- `products`
- `orders`
- `order_items`
- `payments`

---

## 9. Cargar datos de ejemplo

Ejecutar los seeders:

```bash
php artisan db:seed
```

Esto crea datos de demostración, incluyendo productos de ejemplo y un usuario generado mediante el `DatabaseSeeder`.

> Para una base de datos de desarrollo completamente limpia también se puede utilizar `php artisan migrate:fresh --seed`. Este comando elimina las tablas existentes y debe utilizarse únicamente cuando sea aceptable perder los datos actuales.

---

## 10. Limpiar la caché de configuración

Después de modificar variables de `.env`, ejecutar:

```bash
php artisan optimize:clear
```

---

## 11. Ejecutar la API

Levantar el servidor Laravel:

```bash
php artisan serve
```

La API estará disponible normalmente en:

```text
http://127.0.0.1:8000
```

---

# 12. Configurar Stripe CLI para webhooks

Para desarrollo local, Stripe CLI permite recibir eventos de Stripe y reenviarlos a Laravel.

Primero iniciar sesión:

```bash
stripe login
```

Después, mantener una terminal abierta ejecutando:

```bash
stripe listen --forward-to 127.0.0.1:8000/api/stripe/webhook
```

Stripe CLI mostrará un secreto similar a:

```text
Your webhook signing secret is whsec_...
```

Copiar ese valor completo a `.env`:

```env
STRIPE_WEBHOOK_SECRET=whsec_...
```

Después de modificar `.env`:

```bash
php artisan optimize:clear
```

### Importante

La terminal donde se ejecuta `stripe listen` debe permanecer abierta mientras se prueban los webhooks localmente.

---

# 13. Probar webhooks

Stripe CLI permite generar eventos de prueba.

Por ejemplo:

```bash
stripe trigger payment_intent.succeeded
```

Para un escenario de pago fallido se puede utilizar un PaymentIntent asociado a una orden y confirmar el pago utilizando una tarjeta de prueba rechazada.

La API procesa principalmente:

```text
payment_intent.succeeded
payment_intent.payment_failed
payment_intent.canceled
```

Cuando un pago es exitoso:

```text
Payment.status = succeeded
Order.status = paid
```

Cuando el pago falla o se cancela:

```text
Payment.status = failed
Order.status = cancelled
```

Además, cuando una orden es cancelada por fallo de pago, el stock reservado durante la creación de la orden se restaura.

---

# 14. Documentación Swagger

La documentación OpenAPI se encuentra disponible en:

```text
http://127.0.0.1:8000/api/documentation
```

Si es necesario regenerar la documentación después de modificar las anotaciones OpenAPI:

```bash
php artisan l5-swagger:generate
```

Swagger incluye autenticación mediante:

```text
Bearer JWT
```

Para probar endpoints protegidos:

1. Ejecutar login.
2. Copiar el `access_token`.
3. Seleccionar **Authorize** en Swagger UI.
4. Introducir el token Bearer.
5. Ejecutar los endpoints protegidos.

---

# 15. Endpoints disponibles

## Auth

### Registrar usuario

```http
POST /api/auth/register
```

Body:

```json
{
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

### Iniciar sesión

```http
POST /api/auth/login
```

Body:

```json
{
    "email": "juan@example.com",
    "password": "password123"
}
```

La respuesta contiene el JWT:

```json
{
    "message": "Login successful.",
    "access_token": "...",
    "token_type": "Bearer",
    "expires_in": 3600
}
```

### Obtener usuario autenticado

```http
GET /api/auth/me
```

Requiere JWT.

### Cerrar sesión

```http
POST /api/auth/logout
```

Requiere JWT.

### Renovar token

```http
POST /api/auth/refresh
```

Requiere JWT.

---

# 16. Productos

### Listar productos activos

```http
GET /api/products
```

Endpoint público.

### Obtener producto

```http
GET /api/products/{product}
```

Endpoint público.

### Crear producto

```http
POST /api/products
```

Requiere JWT.

Body:

```json
{
    "name": "Laptop Pro 15",
    "description": "Laptop de alto rendimiento.",
    "price": 1299.99,
    "stock": 15,
    "is_active": true
}
```

### Actualizar producto

```http
PUT /api/products/{product}
```

Requiere JWT.

### Eliminar producto

```http
DELETE /api/products/{product}
```

Requiere JWT.

---

# 17. Órdenes

### Crear orden

```http
POST /api/orders
```

Requiere JWT.

Body:

```json
{
    "items": [
        {
            "product_id": 2,
            "quantity": 2
        }
    ]
}
```

Al crear una orden:

1. Se valida el usuario.
2. Se valida que los productos existan.
3. Se verifica que estén activos.
4. Se verifica el stock disponible.
5. Se reserva el stock.
6. Se calculan los subtotales.
7. Se calcula el total de la orden.
8. La orden queda en estado `pending`.

### Historial de órdenes

```http
GET /api/orders
```

Requiere JWT.

El usuario solamente puede consultar sus propias órdenes.

La respuesta incluye los productos de cada orden y los pagos asociados.

---

# 18. Pagos con Stripe

### Crear PaymentIntent

```http
POST /api/orders/{order}/payment
```

Requiere JWT.

El endpoint:

1. Verifica que la orden pertenezca al usuario autenticado.
2. Verifica que la orden esté en estado `pending`.
3. Crea un PaymentIntent en Stripe.
4. Guarda el PaymentIntent en la tabla `payments`.
5. Devuelve el `payment_intent_id`.
6. Devuelve el `client_secret`.

Ejemplo de respuesta:

```json
{
    "message": "PaymentIntent created successfully.",
    "payment_intent_id": "pi_...",
    "client_secret": "pi_..._secret_..."
}
```

El frontend puede utilizar el `client_secret` para completar el pago mediante Stripe.

### Webhook de Stripe

```http
POST /api/stripe/webhook
```

Este endpoint no utiliza JWT.

La autenticidad del webhook se valida mediante la firma:

```text
Stripe-Signature
```

El webhook procesa los eventos de Stripe y actualiza el estado de pagos y órdenes.

---

# 19. Flujo general de compra

El flujo principal de compra es:

```text
1. Registrar usuario
        ↓
2. Login
        ↓
3. Obtener JWT
        ↓
4. Consultar productos
        ↓
5. Crear orden
        ↓
6. Stock reservado
        ↓
7. Crear PaymentIntent
        ↓
8. Completar pago con Stripe
        ↓
9. Stripe envía webhook
        ↓
10. Laravel actualiza Payment
        ↓
11. Laravel actualiza Order
```

### Pago exitoso

```text
PaymentIntent
      ↓
payment_intent.succeeded
      ↓
Payment = succeeded
      ↓
Order = paid
```

### Pago fallido

```text
PaymentIntent
      ↓
payment_intent.payment_failed
      ↓
Payment = failed
      ↓
Order = cancelled
      ↓
Stock restaurado
```

---

# 20. Pruebas con Insomnia

Se puede utilizar Insomnia para probar todos los endpoints.

Para endpoints protegidos agregar:

```text
Authorization: Bearer <JWT>
```

Flujo recomendado:

1. `POST /api/auth/register`
2. `POST /api/auth/login`
3. Copiar `access_token`
4. Configurar Bearer Token en Insomnia
5. Probar productos
6. Crear una orden
7. Crear el PaymentIntent
8. Completar el pago mediante Stripe
9. Verificar el webhook
10. Consultar `GET /api/orders`

---

# 21. Comandos útiles

### Levantar Laravel

```bash
php artisan serve
```

### Limpiar caché

```bash
php artisan optimize:clear
```

### Ejecutar migraciones

```bash
php artisan migrate
```

### Ejecutar seeders

```bash
php artisan db:seed
```

### Migración limpia con seeders

```bash
php artisan migrate:fresh --seed
```

### Regenerar Swagger

```bash
php artisan l5-swagger:generate
```

### Ver rutas

```bash
php artisan route:list
```

### Ver rutas de Swagger

```bash
php artisan route:list | findstr swagger
```

### Verificar estado de Git

```bash
git status
```

---

# 22. Seguridad

- Las credenciales y secretos se almacenan en `.env`.
- `.env` no debe subirse al repositorio.
- `.env.example` contiene únicamente variables de configuración sin secretos.
- Los endpoints privados utilizan JWT.
- Un usuario solamente puede pagar sus propias órdenes.
- El webhook de Stripe valida la firma `Stripe-Signature`.
- El stock se reserva durante la creación de la orden.
- El stock se restaura cuando el pago falla o se cancela.
- Las operaciones relacionadas con órdenes y stock utilizan transacciones de base de datos.

---

# 23. Repositorio

Repositorio GitHub:

https://github.com/river717/api-ecommerce

---

## Estado del proyecto

El proyecto incluye:

- [x] Autenticación JWT
- [x] Registro de usuarios
- [x] Login
- [x] Refresh de tokens
- [x] CRUD de productos
- [x] Creación de órdenes
- [x] Historial de órdenes
- [x] Control de stock
- [x] Integración con Stripe
- [x] PaymentIntent
- [x] Webhooks de Stripe
- [x] Manejo de pagos exitosos
- [x] Manejo de pagos fallidos
- [x] Restauración de stock
- [x] Migraciones
- [x] Seeders
- [x] `.env.example`
- [x] Swagger/OpenAPI
- [x] Autenticación Bearer JWT en Swagger
- [ ] README final publicado en el repositorio
