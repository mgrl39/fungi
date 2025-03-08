#!/bin/bash

# MIT License
#
# Copyright (c) 2025 mgrl39
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.

# Define colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
RESET='\033[0m'

# Function to print the logo
print_logo() {
    logo="
                                    ██████   ████████   ████████
                                   ░░█████  ███░░░░███ ███░░░░███
 █████████████    ███████ ████████  ░█████ ░░░    ░███░████   ░███
░░████░░████░░███  ███░░███░░████░░███ ░████    ██████░ ░░█████████
 ░████ ░████ ░████ ░████ ░████ ░████ ░████   ░░░░░░████ ░░░░░░░████
 ░████ ░████ ░████ ░████ ░████ ░████ ░████  ██████ ░████ ██████ ░████
 █████░████ ██████░░█████████ ██████     ██████░░████████ ░░████████
░░░░░ ░░░░ ░░░░░  ░░░░░█████░░░░░     ░░░░░  ░░░░░░░░   ░░░░░░░░
                 ███ ░████
                ░░██████
                 ░░░░░░
"
    echo -e "${CYAN}${BOLD}${logo}${RESET}"
}

print_logo

# check if mysqldump is installed
if ! command -v mysqldump &> /dev/null; then
    echo -e "${RED}mysqldump could not be found. Please install it and try again.${RESET}"
    exit 1
fi

# Verificar si existe el directorio src/db
if [ -d src/db ]; then
    # El directorio existe, crear el dump dentro de src/db
    mysqldump --user=root --password='Root@1234' fungidb > src/db/data-dump.sql
    
    # Verificar si el archivo fue creado
    if [ ! -f src/db/data-dump.sql ]; then
        echo -e "${RED}The file src/db/data-dump.sql was not created.${RESET}"
        exit 1
    fi
else
    # El directorio no existe, crear el dump en el directorio actual
    mysqldump --user=root --password='Root@1234' fungidb > data-dump.sql
    
    # Verificar si el archivo fue creado
    if [ ! -f data-dump.sql ]; then
        echo -e "${RED}El archivo data-dump.sql no se ha creado.${RESET}"
        exit 1
    fi
fi

echo -e "${GREEN}The database has been exported successfully.${RESET}"