# Usar la imagen base de PHP con Apache
FROM php:8.2-apache

# Force rebuild
RUN echo "Railway Laravel Build v3"

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configurar PHP
RUN echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/php.ini
RUN echo 'display_errors = On' >> /usr/local/etc/php/conf.d/php.ini

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar archivos y instalar dependencias
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-dev --optimize-autoloader

# Copiar resto del código
COPY . .

# Ejecutar scripts de Composer y regenerar autoloader
RUN composer run-script post-autoload-dump --no-interaction || echo "Scripts omitidos"
RUN composer dump-autoload --optimize

# Configurar permisos
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 775 storage bootstrap/cache

# Script de inicio que configura el puerto dinámicamente
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# Configurar el puerto dinámicamente\n\
PORT=${PORT:-80}\n\
echo "Configurando Apache para puerto: $PORT"\n\
\n\
# Actualizar configuración de Apache\n\
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf\n\
\n\
# Crear VirtualHost dinámico\n\
cat > /etc/apache2/sites-available/000-default.conf << EOF\n\
<VirtualHost *:$PORT>\n\
    ServerName localhost\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        DirectoryIndex index.php\n\
    </Directory>\n\
    ErrorLog \${APACHE_LOG_DIR}/error.log\n\
    CustomLog \${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>\n\
EOF\n\
\n\
# Configurar Laravel\n\
[ ! -f .env ] && cp .env.example .env\n\
chown -R www-data:www-data storage bootstrap/cache\n\
chmod -R 775 storage bootstrap/cache\n\
\n\
php artisan config:clear || true\n\
php artisan route:clear || true\n\
php artisan view:clear || true\n\
\n\
if ! grep -q "APP_KEY=base64:" .env; then\n\
    php artisan key:generate --force\n\
fi\n\
\n\
php artisan config:cache || true\n\
php artisan migrate --force || true\n\
\n\
echo "Iniciando Apache en puerto $PORT"\n\
exec apache2-foreground\n\
' > /start.sh && chmod +x /start.sh

# Exponer puerto
EXPOSE $PORT

# Comando de inicio
CMD ["/start.sh"]