#!/bin/bash

# Navigate to the project directory
cd /var/www/html/warehoause-system

# Save any local changes
git stash

# Pull the latest changes
git pull origin main

# Apply any saved changes
git stash pop

# Restart Apache to apply any .htaccess changes
sudo service apache2 restart

echo "Update completed successfully!" 