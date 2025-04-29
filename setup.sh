#!/bin/bash

echo "Starting setup for Job Portal on Raspberry Pi..."

# Update package lists
sudo apt update

# Install Apache, MySQL, PHP, and required modules
sudo apt install -y apache2 mysql-server php libapache2-mod-php php-mysql unzip

# Restart Apache service just to be sure
sudo systemctl restart apache2

# Create portal folder if not exists
if [ ! -d "/var/www/html/portal" ]; then
    sudo mkdir /var/www/html/portal
    echo "✅ Created /var/www/html/portal"
else
    echo "⚡ /var/www/html/portal already exists"
fi

# Create backups and uploads folders
cd /var/www/html/portal
sudo mkdir -p backups uploads

# Set correct permissions
sudo chmod 755 backups uploads

# Set ownership to www-data (Apache user)
sudo chown www-data:www-data backups uploads

# Create MySQL database
echo "CREATE DATABASE IF NOT EXISTS job_sheets;" | sudo mysql

echo "✅ Setup complete!"
echo "👉 Now copy your client-portal.php, config.php, and installer.php into /var/www/html/portal/"
echo "👉 Then visit http://your-pi-ip/portal/installer.php in your browser to complete setup!"
