#!/bin/bash

# Navigate to the project directory
cd /var/www/html/warehoause-system

# Configure Git to use merge strategy
git config pull.rebase false

# Save any local changes
git stash

# Pull the latest changes with merge
git pull --no-rebase origin main

# Apply any saved changes
git stash pop

# Restart Apache to apply any .htaccess changes
sudo service apache2 restart

echo "Update completed successfully!" 