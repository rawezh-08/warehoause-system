#!/bin/bash

# Go to the project directory
cd /var/www/html/warehoause-system

# Pull the latest changes
git pull origin main

# Set proper permissions
chown -R www-data:www-data /var/www/html/warehoause-system
chmod -R 755 /var/www/html/warehoause-system

# Log the update
echo "Update completed at $(date)" >> update.log
