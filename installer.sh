#!/bin/bash
if [ "$EUID" -ne 0 ]
  then echo "Please run as root"
  exit
fi
echo "======== Proxy Aggregator Installer 1.0 ========="
echo
echo "WARNING: Tested only on Ubuntu 14.04."
echo "This script will install all dependencies for the project."
read -n1 -r -p "Press any key to continue" key
sudo apt-get update
sudo apt-get install -y apache2 mysql-server php5 curl php5-mysql php5-curl phpmyadmin
sudo ln -s /usr/share/phpmyadmin /var/www/phpmyadmin
echo "Creating database. Please, type the password of the MySQL root user:"
mysqladmin -u root -p create proxy
sudo a2enmod rewrite
read -n1 -r -p "Please, in the next step confirm the database settings. Press any key to continue." key
nano config/db.php
curl -sS https://getcomposer.org/installer | php
php composer.phar global require "fxp/composer-asset-plugin:1.0.0"
php composer.phar install
sudo ln -sfn "$(pwd)/web" /var/www/proxy
sudo cp -rf config/000-default.conf /etc/apache2/sites-available/000-default.conf
sudo ln -s /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-enabled/000-default.conf
sudo service apache2 restart
echo "Proxy Aggregator installed successfully."
