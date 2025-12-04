# Utilise PHP 8.2 avec Apache
FROM php:8.2-apache

# Installation des extensions PHP, activation du module rewrite, configuration Apache et permissions
RUN docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite \
    && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && mkdir -p /var/www/html/img/events \
    && mkdir -p /var/www/private \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chown -R www-data:www-data /var/www/private \
    && chmod -R 750 /var/www/private

# Expose le port 80
EXPOSE 80

# Démarre Apache
CMD ["apache2-foreground"]
