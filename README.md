# Ecosistema Ureña

Sistema integral de gestión comercial, inventarios multibodega, punto de venta (POS), cotizaciones, pedidos especiales y finanzas para **Mueblería Ureña** (Laravel 10 + PHP 8.3 + MySQL 8 + Vite).

---

## 🐳 Entorno Local de Trabajo (Docker / Laravel Sail)

El entorno local está contenerizado con Docker y configurado con puertos dedicados para evitar colisiones con otros proyectos locales:

| Servicio | Puerto Local (Host) | Puerto Interno | Acceso / Credenciales |
| :--- | :--- | :--- | :--- |
| **Aplicación Web (HTTP)** | `8082` | `80` | [http://localhost:8082](http://localhost:8082) |
| **MySQL 8.4** | `3309` | `3306` | Host: `127.0.0.1` · Puerto: `3309` · BD: `ecosistema_urena` · User: `sail` · Pass: `password` |
| **Vite (HMR)** | `5176` | `5176` | `http://localhost:5176` |

### Comandos de uso diario en local

```bash
# Levantar contenedores en segundo plano
docker compose up -d

# Detener contenedores
docker compose down

# Ejecutar migraciones y seeders desde cero (Base de datos en blanco con catálogos base)
docker exec ecosistema-urena-laravel.test-1 php artisan migrate:fresh --seed

# Limpiar cachés de desarrollo
docker exec ecosistema-urena-laravel.test-1 php artisan optimize:clear
```

### Credenciales iniciales (Seeders)
* **Usuario Administrador:** `admin@urenamuebles.com` (o usuario `admin`)
* **Contraseña:** `password`
* **Rol:** `Super Admin`
* **Sucursales base creadas:** `Sucursal Matriz (MATZ)` y `Sucursal Galván (GALV)`

---

## 🚀 Flujo CI/CD con GitHub Actions

El proyecto cuenta con dos pipelines automatizados en `.github/workflows/`:

1. **Integración Continua (`.github/workflows/ci.yml`):**
   Se dispara en cada `push` o `pull_request` hacia `main` y `develop`. Levanta un contenedor efímero con PHP 8.3 y MySQL 8.0, instala dependencias de Composer, compila los assets con Vite (`npm ci && npm run build`), valida que `php artisan migrate:fresh --seed` se ejecute sin errores y corre las pruebas automatizadas (`phpunit`).
2. **Despliegue Continuo (`.github/workflows/deploy.yml`):**
   Se dispara al hacer `push` a `main` y ejecuta remotamente el script [`deploy.sh`](./deploy.sh) en el servidor de producción vía SSH.

### Secretos requeridos en GitHub (`Settings -> Secrets and variables -> Actions`)
Para activar el despliegue automático al nuevo servidor de producción, registra los siguientes *Repository Secrets* en GitHub:

* `SSH_HOST`: IP o dominio del nuevo servidor.
* `SSH_PORT`: Puerto SSH del servidor (ej. `22` o `65002` en Hostinger).
* `SSH_USER`: Usuario SSH de despliegue.
* `SSH_PRIVATE_KEY`: Llave privada SSH (`id_ed25519`) autorizada en el `~/.ssh/authorized_keys` del servidor.
* `DEPLOY_PATH`: Ruta absoluta del proyecto en el servidor (ej. `/var/www/ecosistema-urena`).
