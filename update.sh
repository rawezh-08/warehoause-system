#!/bin/bash

# Change to the project directory
cd /var/www/html/warehoause-system

# Stash any local changes
git stash

# Fetch the latest changes
git fetch origin

# Reset to the remote state
git reset --hard origin/main

# Clean untracked files
git clean -fd

# Pull the latest changes
git pull origin main

# Apply any stashed changes
git stash pop

# Set proper permissions
chmod -R 755 .
chown -R www-data:www-data .

echo "Update completed successfully!" 