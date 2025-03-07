#!/bin/bash

# Composer
composer require "twig/twig:^3.0"
composer require twbs/bootstrap

# MySQL
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'Root@1234';

# Apache
sudo a2ensite fungi.local.conf
sudo systemctl reload apache2
