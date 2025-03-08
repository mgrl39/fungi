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

# Function to print colored text
print_text() {
    name_color="${YELLOW}${BOLD}"
    text_color="${1:-${WHITE}}"
    echo -e "${name_color}${2}:${RESET} ${text_color}${3}${RESET}"
}

# Function to print sections
print_section() {
    echo -e "\n${GREEN}${BOLD}=================================================="
    echo -e "${BLUE}${BOLD}${1}${RESET}"
    echo -e "${GREEN}${BOLD}==================================================${RESET}\n"
}

# Check if the user is root
if [ "$EUID" -ne 0 ]; then
    print_text "${RED}" "Error" "This script must be run as root."
    exit 1
fi

# Print the logo
print_logo

# Display license information and installation details
print_section "Installation Details"
echo -e "${YELLOW}This script will install the following components:${RESET}"
echo -e "${GREEN}- OpenSSH Server${RESET}"
echo -e "${GREEN}- Apache Web Server${RESET}"
echo -e "${GREEN}- PHP and MySQL${RESET}"
echo -e "${GREEN}- Local domain configuration (www.fungi.local)${RESET}"
echo -e "${YELLOW}By proceeding, you agree to the terms of the MIT License.${RESET}"

# Ask for confirmation
read -p "$(print_text "${YELLOW}" "Confirmation" "Do you want to continue? (y/N): ")" response
if [[ "$response" != "y" && "$response" != "Y" ]]; then
    print_text "${RED}" "Installation" "Cancelled."
    exit 0
fi

# Installation process
print_section "Updating System"
apt update -y > /dev/null 2>&1
print_text "${GREEN}" "System" "Updated successfully."

print_section "Installing OpenSSH Server"
apt install openssh-server -y > /dev/null 2>&1
print_text "${GREEN}" "OpenSSH Server" "Installed successfully."

print_section "Installing Apache"
apt install apache2 -y > /dev/null 2>&1
print_text "${GREEN}" "Apache" "Installed successfully."

print_section "Configuring UFW"
ufw allow in "ssh" > /dev/null 2>&1
ufw allow in "Apache" > /dev/null 2>&1
ufw --force enable > /dev/null 2>&1
print_text "${GREEN}" "UFW" "Configured successfully."

print_section "Installing PHP and MySQL"
apt install php libapache2-mod-php php-mysql mysql-server -y > /dev/null 2>&1
print_text "${GREEN}" "PHP and MySQL" "Installed successfully."

# Create necessary directories
if [ ! -d "/var/www/html/" ]; then
    mkdir -p /var/www/html
    print_text "${GREEN}" "Directory" "/var/www/html created."
fi

# Add entry to /etc/hosts
ETCHOSTS=$(grep "www.fungi.local" /etc/hosts)
if [ -z "$ETCHOSTS" ]; then
    echo -e "###### Fungi ######\n127.0.0.1\twww.fungi.local\n###### Fungi ######" >> /etc/hosts
    print_text "${GREEN}" "Hosts File" "www.fungi.local added."
fi

# Create website directory and test file
mkdir -p /var/www/fungi.local/public
echo "<?php phpinfo(); ?>" > /var/www/fungi.local/public/index.php
print_text "${GREEN}" "Website Directory" "/var/www/fungi.local/public created."

# Configure Apache virtual host
cat <<EOF > /etc/apache2/sites-available/fungi.local.conf
<VirtualHost *:80>
    ServerAdmin admin@fungi.local
    ServerName www.fungi.local
    ServerAlias fungi.local
    DocumentRoot /var/www/fungi.local/public
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
    <Directory /var/www/fungi.local/public>
        AllowOverride All
    </Directory>
</VirtualHost>
EOF
print_text "${GREEN}" "Apache Configuration" "fungi.local configured."

# Enable the site and reload Apache
a2ensite fungi.local.conf > /dev/null 2>&1
systemctl reload apache2 > /dev/null 2>&1
print_text "${GREEN}" "Apache Site" "fungi.local enabled and reloaded."

# Final instructions
print_section "Installation Completed"
print_text "${GREEN}" "Success" "All components installed successfully."
print_text "${YELLOW}" "Reminder" "Run 'mysql_secure_installation' to secure MySQL."
