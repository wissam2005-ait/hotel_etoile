FROM php:8.2-apache
RUN docker-php-ext-install mysqli \
 && a2enmod rewrite
# Railway fournit le port dans $PORT
RUN sed -ri 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
 && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf
ENV PORT=8080
COPY . /var/www/html/
RUN rm -rf /var/www/html/deploy/*.md /var/www/html/.git \
 && mkdir -p /var/www/html/uploads/chambres \
 && chown -R www-data:www-data /var/www/html/uploads \
 && printf '<FilesMatch "\\.(sql|md)$">\n  Require all denied\n</FilesMatch>\n<DirectoryMatch "/deploy">\n  Require all denied\n</DirectoryMatch>\n' > /etc/apache2/conf-enabled/protect.conf
EXPOSE 8080
