# 🗓️ D.Calendar - WordPress Plugin

Plugin profesional de WordPress para gestión de reservas y agendamiento con integración a Google Calendar, Zoom y WhatsApp.

## ✨ Características

- 📅 **Sincronización bidireccional con Google Calendar**
- 🎥 **Creación automática de reuniones Zoom**
- 📱 **Notificaciones por WhatsApp** vía Twilio
- 🔐 **Sistema seguro de gestión de reservas**
- 🌍 **Manejo inteligente de zonas horarias**
- ✉️ **Recordatorios automáticos** por email y WhatsApp
- 🔄 **Cancelación y reagendamiento** fácil
- 📊 **Panel de administración completo**

## 📋 Requisitos

- WordPress 5.8 o superior
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Composer (para desarrollo)
- Cuenta de Google Cloud (Google Calendar API)
- Cuenta de Zoom Marketplace (opcional)
- Cuenta de Twilio (WhatsApp API)

## 🚀 Instalación

### Para Usuarios

1. Descarga el plugin desde [Releases](https://github.com/jploaiza/D.Caledandar-wp/releases)
2. Sube el archivo ZIP en WordPress: `Plugins > Añadir Nuevo > Subir Plugin`
3. Activa el plugin
4. Ve a `D.Calendar > Ajustes` para configurar

### Para Desarrolladores
```bash
# Clonar repositorio
git clone https://github.com/jploaiza/D.Caledandar-wp.git
cd D.Caledandar-wp

# Instalar dependencias
composer install

# Copiar a WordPress
cp -r . /ruta/a/wordpress/wp-content/plugins/d-calendar/
```

## ⚙️ Configuración

### 1. Google Calendar API

1. Ir a [Google Cloud Console](https://console.cloud.google.com/)
2. Crear proyecto nuevo
3. Habilitar Google Calendar API
4. Crear credenciales OAuth 2.0
5. En el plugin: `Integraciones > Google Calendar > Conectar`

### 2. Zoom Integration

1. Ir a [Zoom Marketplace](https://marketplace.zoom.us/)
2. Crear OAuth App
3. Configurar Event Subscriptions:
   - URL: `https://tusitio.com/wp-json/d-calendar/v1/zoom-webhook`
   - Events: `meeting.started`, `meeting.ended`, `participant.joined`, `participant.left`
4. Copiar Client ID, Secret y Webhook Secret
5. Pegar en: `Integraciones > Zoom`

### 3. Twilio WhatsApp

1. Ir a [Twilio Console](https://console.twilio.com/)
2. Activar WhatsApp Sandbox o solicitar número de producción
3. Configurar webhook:
   - URL: `https://tusitio.com/wp-json/d-calendar/v1/twilio-webhook`
   - Método: POST
4. Copiar Account SID y Auth Token
5. Pegar en: `Integraciones > Twilio`

## 📖 Uso

### Shortcode Principal
```php
[reservas_terapia servicio_id="1" mostrar_servicios="true"]
```

**Parámetros:**
- `servicio_id`: ID del servicio específico (opcional)
- `mostrar_servicios`: Mostrar selector de servicios (true/false)

### Funciones PHP
```php
// Obtener disponibilidad
$calendar = new ReservasTerapia\Google_Calendar();
$slots = $calendar->sync_availability('2026-02-15', 'America/Santiago');

// Crear reserva
$booking = new ReservasTerapia\Booking_Manager();
$result = $booking->create_booking($data);
```

## 🏗️ Arquitectura
d-calendar/
├── admin/                  # Panel de administración
│   ├── css/
│   ├── js/
│   └── partials/
├── assets/                 # Assets públicos
│   ├── css/
│   ├── js/
│   └── images/
├── includes/               # Clases principales
│   ├── class-plugin.php
│   ├── class-database.php
│   ├── class-google-calendar.php
│   ├── class-zoom.php
│   └── class-twilio-whatsapp.php
├── public/                 # Frontend público
│   ├── css/
│   ├── js/
│   └── partials/
├── tests/                  # Tests manuales
├── docs/                   # Documentación
└── d-calendar.php         # Archivo principal

## 🧪 Testing
```bash
# Tests manuales disponibles
php test-google-calendar-manual.php
php test-zoom-manual.php
php test-twilio-manual.php
php test-availability-logic.php
```

## 🤝 Contribuir

1. Fork el proyecto
2. Crea una rama: `git checkout -b feature/nueva-funcionalidad`
3. Commit: `git commit -m 'feat: añadir nueva funcionalidad'`
4. Push: `git push origin feature/nueva-funcionalidad`
5. Abre un Pull Request

### Convenciones de Commits

- `feat:` Nueva funcionalidad
- `fix:` Corrección de bug
- `docs:` Documentación
- `style:` Formato de código
- `refactor:` Refactorización
- `test:` Tests
- `chore:` Tareas de mantenimiento

## 📝 Changelog

### [1.0.0] - 2026-01-28

#### Añadido
- Sincronización completa con Google Calendar
- Integración con Zoom Meetings
- Notificaciones WhatsApp vía Twilio
- Sistema de tokens de seguridad
- Panel de administración completo

## 📄 Licencia

GPL v2 o posterior - [LICENSE](LICENSE)

## 👥 Autores

- **Juan Pablo Loaiza** - [@jploaiza](https://github.com/jploaiza)

## 🆘 Soporte

- 📧 Email: soporte@ejemplo.com
- 🐛 Issues: [GitHub Issues](https://github.com/jploaiza/D.Caledandar-wp/issues)
- 📖 Docs: [Wiki](https://github.com/jploaiza/D.Caledandar-wp/wiki)

## 🙏 Agradecimientos

- WordPress Community
- Google Calendar API
- Zoom Developer Platform
- Twilio

---

**Hecho con ❤️ para la comunidad WordPress**