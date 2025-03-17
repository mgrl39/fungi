# 🍄 Fungi

<p align="center">
  <img src="public/assets/logos/logofungi.png" width="200" alt="Fungi Logo">
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
- 🌍 **Multilingüe** con soporte para español, inglés y catalán (aunque los datos están en español)
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
  <b>Herramientas adicionales:</b> Selenium + Python (scraping) | JWT | Gettext (i18n)
</p>

## 📊 Ecosistema Fungi

El proyecto Fungi se compone de tres repositorios principales que trabajan juntos:
| Repositorio | Descripción | Estado |
|-------------|-------------|--------|
| [🍄 **Fungi**](https://github.com/mgrl39/fungi) | Aplicación web principal | [![Status](https://img.shields.io/badge/status-active-success.svg)]() |
| [🤖 **Fungi Scraping**](https://github.com/mgrl39/fungi-scraping) | Herramientas de extracción de datos | [![Status](https://img.shields.io/badge/status-active-success.svg)]() |
| [⚙️ **Fungi Installer**](https://github.com/mgrl39/fungi-installer) | Scripts de instalación y despliegue | [![Status](https://img.shields.io/badge/status-active-success.svg)]() |
| [📸 **Fungi Content**](https://github.com/mgrl39/fungi-content) | Contenido escrapeado | [![Status](https://img.shields.io/badge/status-active-success.svg)]() |

## 🏗️ Arquitectura del Proyecto

```
├── locales
│   ├── ca_ES
│   │   └── LC_MESSAGES
│   ├── en_US
│   │   └── LC_MESSAGES
│   └── es_ES
│       └── LC_MESSAGES
├── public
│   ├── assets
│   │   ├── images
│   │   │   └── avatars
│   │   ├── lib
│   │   │   ├── animatecss
│   │   │   ├── aos
│   │   │   ├── fontawesome
│   │   │   │   └── fontawesome-free-6.4.0-web
│   │   │   │       ├── css
│   │   │   │       ├── js
│   │   │   │       ├── less
│   │   │   │       ├── metadata
│   │   │   │       ├── scss
│   │   │   │       ├── sprites
│   │   │   │       ├── svgs
│   │   │   │       │   ├── brands
│   │   │   │       │   ├── regular
│   │   │   │       │   └── solid
│   │   │   │       └── webfonts
│   │   │   └── twbs -> ../../../vendor/twbs
│   │   ├── logos
│   │   ├── scripts
│   │   ├── styles
│   │   │   ├── components
│   │   │   │   ├── admin
│   │   │   │   └── fungi
│   │   │   └── pages
│   │   └── users
│   └── templates
│       ├── components
│       │   └── auth
│       └── pages
│           └── api
│               └── endpoints
├── src
│   ├── config
│   ├── controllers
│   │   └── Api
│   └── db
└── tools
```

## 🚀 Guía de Inicio Rápido

### Requisitos previos
- PHP 8.0+
- MySQL 5.7+
- Composer

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
   # Editar config/database.php con tus credenciales
   vim config/defaults.inc.php
   
   # Importar la estructura inicial
   mysql -u usuario -p bbdd < src/db/structure.sql
   ```

4. **Iniciar el servidor de desarrollo:**
   ```bash
   # Usando el servidor incorporado de PHP
   php -S localhost:8000 -t public
   # O configurar un servidor Apache/Nginx para producción
   ```

## 👥 Casos de Uso

- 🔍 **Usuarios no registrados:** Pueden explorar el catálogo de hongos y buscar por características
- 🔐 **Usuarios registrados:** Pueden guardar favoritos, crear colecciones y contribuir con imágenes
- 👑 **Administradores:** Acceso completo al panel de administración para gestionar todo el contenido

## 🌱 Roadmap

- [X] Sistema de scraping con Selenium
- [x] Internacionalización de la interfaz (ES, EN, FR)
- [X] Optimización del modelo de datos y relaciones
- [X] Documentación del código generado con Doxygen
- [X] Integración de librería gráfica para estadísticas
- [X] Refactorización del código para mejorar la modularidad y la legibilidad
- [X] Diseño responsive y experiencia de usuario con Bootstrap 5
- [ ] Intento de implementación de API REST con autenticación JWT
- [ ] Mejoras en el panel de administración y gestión de usuarios
- [ ] Implementación de pruebas unitarias y de integración

## 📄 Licencia

Este proyecto está licenciado bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

## 📧 Contacto

¿Preguntas? ¿Sugerencias? ¿Encontraste un error?
- 🌐 Sitio web: [mgrl39.github.io/fungi](https://mgrl39.github.io/fungi)
---

<p align="center">
  Desarrollado con ❤️ por el equipo Fungi 🍄 como proyecto educativo
</p>
