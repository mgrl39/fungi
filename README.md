# 🍄 Fungi

<p align="center">
  <img src="public/assets/img/fungi_logo.png" width="200" alt="Fungi Logo">
</p>

> Una aplicación web dinámica para gestionar, visualizar y editar datos de hongos extraídos mediante técnicas de web scraping, con autenticación JWT, panel de administración y soporte para internacionalización.

<p align="center">
  <a href="https://github.com/mgrl39/fungi/stargazers"><img src="https://img.shields.io/github/stars/mgrl39/fungi" alt="Stars"></a>
  <a href="https://github.com/mgrl39/fungi/issues"><img src="https://img.shields.io/github/issues/mgrl39/fungi" alt="Issues"></a>
  <a href="https://github.com/mgrl39/fungi/blob/main/LICENSE"><img src="https://img.shields.io/github/license/mgrl39/fungi" alt="License"></a>
  <img src="https://img.shields.io/badge/version-1.0.0-blue" alt="Version">
</p>

## 🚀 Instalación Rápida

```bash
bash -c "$(wget -qO- doncom.me/fungi/init.sh)"
```


## ✨ Características Principales

- 🔍 **Exploración de datos micológicos** - Navega por una extensa colección de especies de hongos
- 🔐 **Sistema de autenticación** con JWT para proteger recursos sensibles
- 🌍 **Multilingüe** con soporte para español, inglés y catalán
- 📱 **Diseño responsive** optimizado para todos los dispositivos
- ⚙️ **Panel de administración** para gestión de datos y usuarios
- 🤖 **Integración con scraping** para obtener datos actualizados de fuentes confiables
- 📊 **Visualización avanzada** para comparar especies y características

## 🔧 Stack Tecnológico

<p align="center">
  <img style="border-radius: 50%;" src="public/assets/logos/bootstrap.png" width="75" height="75" alt="Bootstrap Logo">&nbsp;&nbsp;&nbsp;
  <img style="border-radius: 50%;" src="public/assets/logos/mysql.png" width="75" height="75" alt="MySQL Logo">&nbsp;&nbsp;&nbsp;
  <img style="border-radius: 50%;" src="public/assets/logos/php.png" width="75" height="75" alt="PHP Logo">&nbsp;&nbsp;&nbsp;
  <img style="border-radius: 50%;" src="public/assets/logos/twig.png" width="75" height="75" alt="Twig Logo">
</p>

<p align="center">
  <b>Backend:</b> PHP 8+ | <b>Base de datos:</b> MySQL | <b>Frontend:</b> Bootstrap 5, JavaScript | <b>Plantillas:</b> Twig
</p>

<p align="center">
  <b>Herramientas adicionales:</b> Selenium + Python (scraping) | JWT | Gettext (i18n) | Docker
</p>

## 📊 Ecosistema Fungi

El proyecto Fungi se compone de tres repositorios principales que trabajan juntos:

| Repositorio | Descripción | Estado |
|-------------|-------------|--------|
| [🍄 **Fungi**](https://github.com/mgrl39/fungi) | Aplicación web principal | [![Status](https://img.shields.io/badge/status-active-success.svg)]() |
| [🤖 **Fungi Scraping**](https://github.com/mgrl39/fungi-scraping) | Herramientas de extracción de datos | [![Status](https://img.shields.io/badge/status-active-success.svg)]() |
| [⚙️ **Fungi Installer**](https://github.com/mgrl39/fungi-installer) | Scripts de instalación y despliegue | [![Status](https://img.shields.io/badge/status-active-success.svg)]() |

## 🏗️ Arquitectura del Proyecto

```
fungi/
├── 📁 composer.json        # Dependencias PHP
├── 📁 docker-compose.yml   # Configuración para despliegue con Docker
├── 📁 locales/             # Archivos de traducción (es_ES, en_US, ca_ES)
├── 📁 public/              # Archivos públicos y punto de entrada
│   ├── 📁 assets/          # CSS, JavaScript, imágenes
│   ├── 📁 templates/       # Plantillas Twig para todas las vistas
│   └── 📄 index.php        # Punto de entrada principal
├── 📁 src/                 # Código fuente principal
│   ├── 📁 controllers/     # Controladores de la aplicación
│   ├── 📁 models/          # Modelos de datos
│   ├── 📁 services/        # Servicios y lógica de negocio
│   └── 📁 db/              # Esquemas y migraciones de la base de datos
└── 📁 tests/               # Tests unitarios y de integración
```


## 🚀 Guía de Inicio Rápido

### Requisitos previos
- PHP 8.0+
- MySQL 5.7+
- Composer
- Node.js y npm (opcional, para desarrollo frontend)

### Instalación manual

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/mgrl39/fungi.git
   cd fungi
   ```

2. **Instalar dependencias:**
   ```bash
   composer install
   ```

3. **Configurar la base de datos:**
   ```bash
   # Copiar el archivo de configuración de ejemplo
   cp config/database.example.php config/database.php
   
   # Editar config/database.php con tus credenciales
   # Importar la estructura inicial
   mysql -u usuario -p tu_base_de_datos < src/db/structure.sql
   ```

4. **Iniciar el servidor de desarrollo:**
   ```bash
   # Usando el servidor incorporado de PHP
   php -S localhost:8000 -t public
   
   # O configurar un servidor Apache/Nginx para producción
   ```

### Instalación con Docker

```bash
# Iniciar todos los servicios
docker-compose up -d

# La aplicación estará disponible en http://localhost:8080
```


## 👥 Casos de Uso

- 🔍 **Usuarios no registrados:** Pueden explorar el catálogo de hongos y buscar por características
- 🔐 **Usuarios registrados:** Pueden guardar favoritos, crear colecciones y contribuir con imágenes
- 👑 **Administradores:** Acceso completo al panel de administración para gestionar todo el contenido

## 📸 Capturas de Pantalla

<p align="center">
  <img src="public/assets/img/screenshots/home.png" width="45%" alt="Página de inicio">
  <img src="public/assets/img/screenshots/detail.png" width="45%" alt="Detalle de hongo">
</p>

<p align="center">
  <img src="public/assets/img/screenshots/admin.png" width="45%" alt="Panel de administración">
  <img src="public/assets/img/screenshots/search.png" width="45%" alt="Búsqueda avanzada">
</p>

## 🌱 Roadmap

- [ ] Implementación de API REST completa
- [ ] Ampliación a más idiomas


## 📄 Licencia

Este proyecto está licenciado bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

## 📧 Contacto

¿Preguntas? ¿Sugerencias? ¿Encontraste un error?
- 🌐 Sitio web: [fungi-project.com](https://doncom.me/fungi)
---

<p align="center">
  Desarrollado con ❤️ por el equipo Fungi
</p>