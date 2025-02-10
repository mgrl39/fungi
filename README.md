# 🍄 Fungi

```
bash -c "$(wget -qO- doncom.me/fungi/init.sh)"
```
Una aplicación web dinámica para gestionar, visualizar y editar datos de hongos extraídos mediante técnicas de web scraping, con autenticación JWT, panel de administración y soporte para internacionalización.

## 🔨 Tecnologías

- **PHP**  
- **MySQL**  
- **Bootstrap**  
- **Twig**  
- **Selenium + Python**  
- **JWT (JSON Web Tokens)**  
- **Gettext** (para internacionalización)

<div>
  <img src="public/assets/img/logos/bootstrap.png" width="50" height="50" alt="Bootstrap Logo">&nbsp;&nbsp;&nbsp;
  <img src="public/assets/img/logos/mysql.png" width="50" height="50" alt="MySQL Logo">&nbsp;&nbsp;&nbsp;
  <img src="public/assets/img/logos/php.png" width="50" height="50" alt="PHP Logo">
</div>


## 📊 Datos Extraídos

Los datos se extraen mediante [Fungi Scraping](https://www.github.com/mgrl39/fungi-scraping) utilizando **Python 3** y Selenium.  
La información se guarda en formato JSON y se inserta en la base de datos para su posterior gestión y edición desde el panel de administración.

## 📚 Estructura del Código

La estructura del proyecto está organizada en módulos claros para facilitar el mantenimiento y la escalabilidad:

```bash
.
├── composer.json
├── composer.lock
├── OBJECTIVES.md
├── locales
│   ├── ca_ES
│   │   └── messages.po
│   ├── en_US
│   │   └── messages.po
│   └── es_ES
│       └── messages.po
├── public
│   ├── assets
│   │   ├── css
│   │   │   └── style.css
│   │   ├── img
│   │   │   └── logos
│   │   │       ├── bootstrap.png
│   │   │       ├── mysql.png
│   │   │       ├── php.png
│   │   │       └── twig.png
│   │   └── js
│   │       └── script.js
│   ├── index.php
│   └── templates
│       ├── 404.twig
│       ├── about.twig
│       ├── admin.twig
│       ├── contact.twig
│       ├── faq.twig
│       ├── footer.twig
│       ├── fungi_list.twig
│       ├── fungus_detail.twig
│       ├── header.twig
│       ├── index.twig
│       ├── login.twig
│       ├── profile.twig
│       ├── register.twig
│       ├── reset_password.twig
│       ├── terms.twig
│       └── view_toggle.twig
├── README.md
├── src
│   ├── controllers
│   │   ├── ApiController.php
│   │   └── DatabaseController.php
│   └── db
│       └── structure.sql
├── tools
│   └── generate_website.sh
├── UTILS.md
└── vendor
    └── ... (archivos de dependencias)
```

## 🚀 Instalación

Para instalar las dependencias del proyecto, ejecuta:

```shell
composer install
```

## 📖 Uso y Ejecución

1. **Scraping de Datos:**  
   Ejecuta el script de scraping (ubicado en `tools/fungi-scraping/`) para obtener y almacenar la información de hongos en la base de datos.

2. **Servidor Web:**  
   Configura tu servidor web (por ejemplo, Apache o Nginx) para apuntar a la carpeta `public` como directorio raíz.

3. **Acceso a la Aplicación:**  
   - Navega a `/` para ver el listado de fungis.
   - Accede a `/login` para iniciar sesión.
   - Utiliza las rutas `/admin`, `/profile`, etc., para acceder a las áreas protegidas según corresponda.

4. **Internacionalización:**  
   La aplicación soporta múltiples idiomas (ej. español e inglés) mediante gettext. Revisa la carpeta `locales` para ver los archivos de traducción.

## 📝 Documentación Adicional

- **Estructura SQL:**  
  El modelo de datos se encuentra en `src/db/structure.sql`. Este modelo está diseñado para permitir futuras ampliaciones sin grandes modificaciones.

- **Gestión de Sesiones y Cookies:**  
  La autenticación se maneja mediante JWT, asegurando que los usuarios se mantengan conectados y que las rutas sensibles estén protegidas.

## 📂 Contribuciones

El proyecto está alojado en GitHub. Puedes ver el repositorio principal en:  
- [Fungi](https://github.com/mgrl39/fungi)  
- [Fungi Scraping](https://github.com/mgrl39/fungi-scraping)  
- [Fungi Installer](https://github.com/mgrl39/fungi-installer)

¡Todas las contribuciones son bienvenidas!

## 📜 Licencia

Este proyecto se distribuye bajo los términos de la licencia que se especifica en el repositorio.
