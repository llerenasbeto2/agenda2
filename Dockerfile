# Usar la imagen base de PHP con Apache
FROM php:8.2-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Configurar el directorio de trabajo
WORKDIR /var/www/html

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar archivos de dependencias primero (para cache de Docker)
COPY composer.json composer.lock package.json package-lock.json ./

# Instalar dependencias PHP
RUN composer install --no-scripts --no-autoloader --no-dev

# Instalar dependencias Node.js
RUN npm install --production

# Copiar el resto del código
COPY . .

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Completar instalación de Composer
RUN composer dump-autoload --optimize

# Optimizar Laravel (SIN migraciones aún)
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Configurar Apache para Laravel
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Crear script de inicio
RUN echo '#!/bin/bash\n\
# Ejecutar migraciones cuando la base de datos esté disponible\n\
php artisan migrate --force\n\
# Limpiar y optimizar cache\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan view:cache\n\
# Iniciar Apache\n\
apache2-foreground' > /start.sh

RUN chmod +x /start.sh

# Exponer puerto
EXPOSE 80

# Comando de inicio
CMD ["/start.sh"]