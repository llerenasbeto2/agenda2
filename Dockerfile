# Usar la imagen base de PHP con Apache
FROM php:8.2-apache

# Force rebuild
RUN echo "Railway Laravel Build v6 - Vite Assets Fix"

# Instalar dependencias del sistema (incluir Node.js LTS)
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    && curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configurar PHP
RUN echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/php.ini
RUN echo 'display_errors = On' >> /usr/local/etc/php/conf.d/php.ini

# Habilitar módulos de Apache necesarios para Laravel
RUN a2enmod rewrite headers expires deflate

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar archivos y instalar dependencias
COPY composer.json composer.lock ./
COPY package*.json ./
RUN composer install --no-scripts --no-dev --optimize-autoloader

# Copiar resto del código
COPY . .

# Instalar dependencias de Node.js y compilar assets
RUN npm install && npm run build

# Ejecutar scripts de Composer y regenerar autoloader
RUN composer run-script post-autoload-dump --no-interaction || echo "Scripts omitidos"
RUN composer dump-autoload --optimize

# Configurar permisos
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 775 storage bootstrap/cache

# Crear archivo de configuración de puertos dinámico
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# Configurar el puerto dinámicamente\n\
PORT=${PORT:-80}\n\
echo "Configurando Apache para puerto: $PORT"\n\
\n\
# Crear ports.conf desde cero para evitar problemas de sed\n\
cat > /etc/apache2/ports.conf << EOF\n\
# If you just change the port or add more ports here, you will likely also\n\
# have to change the VirtualHost statement in\n\
# /etc/apache2/sites-enabled/000-default.conf\n\
\n\
Listen $PORT\n\
\n\
<IfModule ssl_module>\n\
\tListen 443\n\
</IfModule>\n\
\n\
<IfModule mod_gnutls.c>\n\
\tListen 443\n\
</IfModule>\n\
EOF\n\
\n\
# Crear VirtualHost desde cero\n\
cat > /etc/apache2/sites-available/000-default.conf << EOF\n\
<VirtualHost *:$PORT>\n\
\tServerName localhost\n\
\tDocumentRoot /var/www/html/public\n\
\t\n\
\t<Directory /var/www/html/public>\n\
\t\tAllowOverride All\n\
\t\tRequire all granted\n\
\t\tDirectoryIndex index.php\n\
\t</Directory>\n\
\t\n\
\tErrorLog \\${APACHE_LOG_DIR}/error.log\n\
\tCustomLog \\${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>\n\
EOF\n\
\n\
# Configurar ServerName global\n\
echo "ServerName localhost" >> /etc/apache2/apache2.conf\n\
\n\
# Configurar Laravel (solo una vez)\n\
if [ ! -f .env ]; then\n\
\tcp .env.example .env || echo "No se pudo crear .env"\n\
fi\n\
\n\
# Configurar permisos\n\
chown -R www-data:www-data storage bootstrap/cache\n\
chmod -R 775 storage bootstrap/cache\n\
\n\
# Limpiar cache Laravel\n\
php artisan config:clear 2>/dev/null || true\n\
php artisan route:clear 2>/dev/null || true\n\
php artisan view:clear 2>/dev/null || true\n\
\n\
# Generar APP_KEY si no existe\n\
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then\n\
\tphp artisan key:generate --force\n\
fi\n\
\n\
# Optimizar Laravel\n\
php artisan config:cache 2>/dev/null || true\n\
\n\
# Ejecutar migraciones\n\
php artisan migrate --force 2>/dev/null || echo "Migraciones omitidas"\n\
\n\
# Verificar configuración de Apache antes de iniciar\n\
echo "Verificando configuración de Apache..."\n\
apache2ctl configtest\n\
\n\
echo "Iniciando Apache en puerto $PORT"\n\
exec apache2-foreground\n\
' > /start.sh && chmod +x /start.sh

# Exponer puerto
EXPOSE $PORT

# Comando de inicio
CMD ["/start.sh"]