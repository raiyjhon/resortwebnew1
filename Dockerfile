# Use an official PHP image as the base
FROM php:8.3-apache

# Copy your entire project to the web server root
COPY . /var/www/html/

# Expose port 80 to the outside world
EXPOSE 80

# The default command to start the Apache server is already set in the base image.