# Valentina App — Sistema de Gestión Empresarial

Sistema web PHP para gestión de clientes, inventario, ventas, usuarios y reportes de una microempresa.

## Requisitos

- PHP 7.4+
- MySQL / MariaDB
- Servidor web (Apache, Nginx, IIS)

## Instalación

1. Clonar el repositorio.
2. Crear la base de datos ejecutando `database/schema.sql`.
3. Copiar `config/.env.example` como `config/.env` y configurar las credenciales de la base de datos.
4. Iniciar sesión con usuario `admin` y contraseña `admin123`.

## Ejecucion de proyecto

php -S localhost:8000

## Ruta local del proyecto

[localhost:8000](http://localhost:8000/login.php)

## Módulos

| Archivo                 | Descripción                       |
| ----------------------- | --------------------------------- |
| `login.php`             | Inicio de sesión                  |
| `dashboard.php`         | Panel principal con navegación    |
| `usuarios-list.php`     | CRUD de usuarios (solo admin)     |
| `clientes-list.php`     | CRUD de clientes                  |
| `inventario-list.php`   | CRUD de inventario                |
| `sales.php` / `php.php` | Registro de ventas                |
| `reportes.php`          | Reportes con filtro de fechas     |
| `logout.php`            | Cierre de sesión                  |
| `config/auth.php`       | Autenticación mediante sesiones   |
| `config/ui.php`         | Notificaciones flash e iconos SVG |
| `assets/css/style.css`  | Estilos del sistema               |
| `assets/js/ui.js`       | Interactividad frontend           |

## Roles de usuario

| Rol     | Acceso                                                                |
| ------- | --------------------------------------------------------------------- |
| `admin` | Acceso completo a todos los módulos                                   |
| `user`  | Clientes (CRUD completo), Inventario (solo lectura), Ventas, Reportes |

La columna `rol` en la tabla `usuarios` determina los permisos de cada cuenta.

## Base de datos

El esquema se encuentra en `database/schema.sql`. La tabla `usuarios` incluye una columna `rol` con valores `admin` o `user`.
