#!/bin/bash

#===========================================
# Fungi Website Generator Script
# Author: mgrl39
# Description: Sets up a local development environment for the Fungi website
#===========================================

# Define ANSI color codes for terminal output
#-------------------------------------------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No color

#===========================================
# Root user check
#===========================================
if [ $USER != "root" ]; then
	echo -e "${RED}Script must be run as user: ${YELLOW}root${NC}"
	exit -1
fi

#===========================================
# System Requirements Installation
#===========================================
# Update package lists
echo -e "${GREEN}Updating system${NC}"
sudo apt update -y > /dev/null 2>&1

# Install required packages
echo -e "${GREEN}Installing OpenSSH Server${NC}"
sudo apt install openssh-server -y > /dev/null 2>&1

echo -e "${GREEN}Installing Apache${NC}"
sudo apt install apache2 -y > /dev/null 2>&1

# Configure firewall
echo -e "${GREEN}Allowing SSH in UFW${NC}"
sudo ufw allow in "ssh" 

echo -e "${GREEN}Allowing Apache in UFW${NC}"
sudo ufw allow in "Apache"

echo -e "${GREEN}Enabling UFW${NC}"
sudo ufw --force enable > /dev/null 2>&1

# Install PHP and MySQL
echo -e "${GREEN}Installing PHP${NC}"
sudo apt install php libapache2-mod-php php-mysql mysql-server -y > /dev/null 2>&1

#===========================================
# Web Directory Setup
#===========================================
# Create web root if it doesn't exist
if [ -d "/var/www/html/" ]; then 
	echo -e "${GREEN}Creating /var/www/html folder${NC}"
	mkdir -p /var/www/html
fi

#===========================================
# Hosts File Configuration  
#===========================================
# Add local domain to hosts file if not present
ETCHOSTS=$(cat /etc/hosts | grep www.fungi.local)
if [ -z "$ETCHOSTS" ]; then
	echo -e "Adding www.fungi.local to /etc/hosts";
	echo -e "###### Fungi ######\n127.0.0.1\twww.fungi.local\n###### Fungi ######" >> /etc/hosts
	exit -1
fi

#===========================================
# Virtual Host Setup
#===========================================
# Create website directory structure
mkdir -p /var/www/fungi.local/public

# Create test PHP file
echo -n "" > /var/www/fungi.local/public/index.php
sudo echo -e "<?php echo phpinfo();" >> /var/www/fungi.local/public/index.php

# Configure Apache virtual host
echo -e "${GREEN}Writing/Rewriting /etc/apache2/sites-available/fungi.local.conf ${NC}"
mkdir -p /etc/apache2/sites-available
echo -n ""  > /etc/apache2/sites-available/fungi.local.conf
touch /etc/apache2/sites-available/fungi.local.conf

# Write virtual host configuration
echo -e "<VirtualHost *:80>\n    ServerAdmin admin@fungi.local\n    ServerName www.fungi.local" > /etc/apache2/sites-available/fungi.local.conf
echo -e "    ServerAlias fungi.local\n    DocumentRoot /var/www/fungi.local/public" >> /etc/apache2/sites-available/fungi.local.conf
echo -e "    ErrorLog \${APACHE_LOG_DIR}/error.log" >> /etc/apache2/sites-available/fungi.local.conf
echo -e "    CustomLog \${APACHE_LOG_DIR}/access.log combined" >> /etc/apache2/sites-available/fungi.local.conf
echo -e "    <Directory /var/www/fungi.local/public>" >> /etc/apache2/sites-available/fungi.local.conf
echo -e "        AllowOverride All\n    </Directory>" >> /etc/apache2/sites-available/fungi.local.conf
echo -e "</VirtualHost>" >> /etc/apache2/sites-available/fungi.local.conf

#===========================================
# Enable Site & Restart Apache
#===========================================
cd /etc/apache2/sites-available
sudo a2ensite fungi.local.conf
sudo systemctl reload apache2

#===========================================
# Final Instructions
#===========================================
echo -e "+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo -e "${RED}REMEMBER:${NC} configure: ${YELLOW}mysql_secure_installation${NC} with params: n, n, n, n, y"
echo -e "+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
