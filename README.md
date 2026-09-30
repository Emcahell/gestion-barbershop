# BarberShop — Sistema de Gestión de Citas

Sistema de reservas para barberías: los clientes reservan turnos en línea, los
barberos administran su agenda y disponibilidad, y el administrador gestiona
servicios, cuentas de barberos y la barbería.

## Stack

| Componente | Tecnología |
| --- | --- |
| Framework | Laravel 13 (PHP 8.4) |
| Base de datos | MySQL / MariaDB (`utf8mb4`) |
| Frontend | Blade + Tailwind CSS v4 |
| Build | Vite 8 |
| Tests | PHPUnit (`RefreshDatabase` con BD `barbershop_test`) |
| Código | Laravel Pint |

## Instalación

Requisitos: PHP ≥ 8.3 con `pdo_mysql`, Composer, Node.js y un servidor
MySQL/MariaDB en ejecución.

Crea las bases de datos (la de la app y la de tests) una sola vez:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS barbershop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS barbershop_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

```bash
composer setup
```

El script `setup` instala dependencias, crea el `.env` (si no existe), genera la
llave `APP_KEY`, corre las migraciones, siembra el administrador y compila los
assets.

Instalación manual equivalente:

```bash
composer install
cp .env.example .env            # revisa DB_DATABASE / DB_USERNAME / DB_PASSWORD
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
```

Servidor de desarrollo:

```bash
composer dev   # artisan dev (servidor, Vite y Pail)
```

## Configuración

Todo lo configurable vive en `.env` (nunca en el repo):

| Variable | Descripción |
| --- | --- |
| `DB_HOST` / `DB_PORT` | Servidor MySQL (por defecto `127.0.0.1:3306`). |
| `DB_DATABASE` | Base de datos de la app (`barbershop`; los tests usan `barbershop_test`). |
| `DB_USERNAME` / `DB_PASSWORD` | Credenciales de MySQL. |
| `APP_TIMEZONE` | Zona horaria (por defecto `America/Caracas`). |
| `APP_LOCALE` | Idioma de la app (`es`). |
| `ADMIN_NAME` | Nombre del administrador inicial. |
| `ADMIN_EMAIL` | Correo del administrador inicial. |
| `ADMIN_PHONE` | Teléfono (entre comillas: `"+58 412 000-0000"`). |
| `ADMIN_PASSWORD` | Contraseña; si se omite se genera una aleatoria. |
| `APPOINTMENTS_OPEN` / `APPOINTMENTS_CLOSE` | Horario de atención (09:00–19:00). |
| `APPOINTMENTS_SLOT_MINUTES` | Duración de cada turno (60). |
| `APPOINTMENTS_CANCELLATION_HOURS` | Horas mínimas para cancelar (2). |

`DatabaseSeeder` es idempotente (`firstOrCreate`) y solo crea la cuenta
administradora con las variables `ADMIN_*`. Si falta `ADMIN_EMAIL` no crea nada.

```bash
php artisan db:seed        # seguro de repetir, no toca datos existentes
```

> **Importante:** nunca uses `migrate:fresh --seed` en una BD con datos
> reales; borra todo. `php artisan migrate` solo agrega tablas nuevas sin
> modificar datos.

## Roles y permisos

| Acción | Cliente | Barbero | Admin |
| --- | :-: | :-: | :-: |
| Registrarse / iniciar sesión | ✅ | ✅ | ✅ |
| Reservar y cancelar sus citas | ✅ | ❌ | ❌ |
| Ver su agenda diaria y completar citas | ❌ | ✅ | ✅ (toda la barbería) |
| Cancelar citas desde la agenda (sin límite de 2 h) | ❌ | ✅ (las suyas) | ✅ (todas) |
| Ver reportes (ingresos, servicios y ranking) | ❌ | ✅ (los suyos) | ✅ (toda la barbería) |
| Gestionar servicios | ❌ | ✅ | ✅ |
| Marcar días/horas no disponibles | ❌ | ✅ (los suyos) | ❌ |
| Crear cuentas de barberos | ❌ | ❌ | ✅ |

El rol se asigna con el enum `App\Enums\UserRole` (`client`, `barber`,
`admin`); el registro siempre crea clientes. El acceso se controla con el
middleware `role` (`app/Http/Middleware/EnsureUserHasRole`).

## Funcionalidades

### Clientes
- **Registro e ingreso** a medida, con redirección según el rol.
- **Reserva** en 3 pasos: servicio → barbero → fecha. Calendario mensual donde
  los días no disponibles del barbero aparecen bloqueados en rojo y no se pueden
  elegir; las horas bloqueadas aparecen tachadas junto a las ya ocupadas.
- **Mis citas**: próximas citas e historial, con cancelación hasta 2 horas
  antes del turno.

### Barberos
- **Agenda diaria** con navegación por fecha (← Anterior / Siguiente / Ir a
  hoy), facturación estimada del día y sección de próximas citas.
- **Completar** citas (pasan al historial del cliente) y **cancelarlas** desde
  la agenda en cualquier momento (la regla de 2 h solo aplica al cliente).
- **Disponibilidad**: calendario para marcar días completos no disponibles y
  checkboxes para bloquear horas puntuales (por ejemplo, si ese día empiezas a
  las 11:00, bloqueas 09:00 y 10:00). Lo que el barbero marque queda bloqueado
  para todos los clientes.
- **Servicios**: crear, editar y desactivar (nunca se borran).
- **Reportes**: ingresos y número de servicios completados, con gráfico por
  día/semana/mes y filtro por últimos 7 días, 30 días, 3 meses o año en curso;
  además del top 10 de clientes con más citas completadas.

### Administrador
- **Agenda completa** de todos los barberos con facturación del día.
- **Barberos**: crea cuentas con nombre, correo y teléfono.
- **Servicios** de la barbería.
- **Reportes** de toda la barbería: ingresos, servicios y ranking de clientes.
- No reserva citas ni gestiona la disponibilidad de cada barbero.

## Reglas de negocio

- Turnos fijos de 60 minutos de 09:00 a 18:00 (10 por día), generados por
  `Appointment::slots()` desde `config/appointments.php`.
- Un barbero no puede tener dos citas en la misma fecha y hora: la validación
  de `AppointmentController::store` da el aviso amigable y el índice único de
  `appointments.slot_key` (solo para citas agendadas) garantiza que, si dos
  clientes reservan en el mismo instante, la base de datos acepte solo una y
  la otra reciba «Ese horario acaba de ocuparse…».
- Días y horas bloqueados por el barbero se validan en el servidor
  (`StoreAppointmentRequest`), además de deshabilitarlos en la interfaz.
- La cancelación del cliente exige más de 2 horas de anticipación; la cita
  cancelada libera el turno.
- El ingreso se contabiliza al completar la cita: `completed_at` y el `price`
  se sellan en ese instante, por eso cambiar después el precio de un servicio
  no altera el histórico y las citas canceladas nunca suman.

## Estructura

```
app/
├── Enums/            UserRole, AppointmentStatus (con labels en español)
├── Http/
│   ├── Controllers/  Appointment, Availability, Panel, Report, Service, Barber, Home, Auth
│   ├── Middleware/    EnsureUserHasRole (alias `role`)
│   └── Requests/     StoreAppointmentRequest (validación de reserva)
├── Models/           User, Service, Appointment, BarberUnavailability
└── Support/          MonthCalendar (rejilla compartida de calendarios)
config/appointments.php   horario, duración y regla de cancelación
database/
├── migrations/       users(+phone/role), services, appointments(+completed_at/price), barber_unavailabilities
├── factories/        estados: barber, admin, inactive, completed, cancelled
└── seeders/          DatabaseSeeder (solo admin desde env)
resources/views/      Blade de components, home (landing), auth, appointments,
                      panel, availability, reports, services y barbers
tests/Feature/        82 tests (auth, reserva, cancelación, agenda, servicios,
                      barberos, disponibilidad, reportes, landing)
```

## Comandos

```bash
composer dev                  # desarrollo: servidor + Vite + Pail
php artisan test --compact     # suite (82 tests / 285 assertions, usa barbershop_test)
vendor/bin/pint --format agent # formato de código
npm run build                  # compilar assets para producción
php artisan migrate            # aplicar migraciones pendientes (no borra datos)
php artisan db:seed            # sembrar/actualizar el admin (idempotente)
```

## Notas para producción

- El proyecto no viene con Git inicializado; ejecuta `git init` antes de
  publicar y confirma que `.env` esté en `.gitignore` (las credenciales nunca
  se suben al repo).
- Cambia la contraseña `ADMIN_PASSWORD` del administrador y usa un valor fuerte.
- Crea la base de datos en el servidor con `utf8mb4_unicode_ci` y define `DB_*`
  en el `.env` de producción antes de `php artisan migrate --force`.
- Revisa que `APP_URL`, `APP_TIMEZONE` y `APP_DEBUG=false` estén correctos.
