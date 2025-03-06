#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
RESET='\033[0m'

# Composer
echo -e "${GREEN}Composer${RESET}"
echo -e 'composer require "twig/twig:^3.0"'
echo -e 'composer require twbs/bootstrap'

    # MySQL
echo -e "${BLUE}MySQL${RESET}"
echo -e 'ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'Root@1234';'

# Apache
echo -e "${MAGENTA}Apache${RESET}"
echo -e 'sudo a2ensite fungi.local.conf'
echo -e 'sudo systemctl reload apache2'
