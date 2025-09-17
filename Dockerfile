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
    net-tools \
    procps \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configurar PHP
RUN echo 'log_errors = On' >> /usr/local/etc/php/conf.d/docker-php-ext-custom.ini \
    && echo 'error_log = /var/log/php_errors.log' >> /usr/local/etc/php/conf.d/docker-php-ext-custom.ini \
    && echo 'display_errors = On' >> /usr/local/etc/php/conf.d/docker-php-ext-custom.ini \
    && echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/docker-php-ext-custom.ini

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar archivos de dependencias primero
COPY composer.json composer.lock ./
COPY package*.json ./

# Instalar dependencias PHP CON scripts (importante para Laravel 11)
RUN composer install --no-dev --optimize-autoloader

# Instalar dependencias Node.js si existen
RUN if [ -f "package.json" ]; then npm install --omit=dev; fi

# Copiar el resto del código
COPY . .

# Crear directorios necesarios y configurar permisos
RUN mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Regenerar autoloader DESPUÉS de copiar todo
RUN composer dump-autoload --optimize

# Configurar Apache
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Configuración VirtualHost
RUN echo '<VirtualHost *:80>\n\
    ServerName localhost\n\
    DocumentRoot /var/www/html/public\n\
    \n\
    <Directory /var/www/html>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    \n\
    <Directory /var/www/html/public>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
        DirectoryIndex index.php index.html\n\
    </Directory>\n\
    \n\
    LogLevel info\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    \n\
    php_admin_value memory_limit 512M\n\
    php_admin_flag display_errors On\n\
    php_admin_flag log_errors On\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Script de inicio simplificado pero efectivo
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "========================================"\n\
echo "    INICIANDO LARAVEL EN RAILWAY"\n\
echo "========================================"\n\
\n\
# Crear .env si no existe\n\
if [ ! -f .env ]; then\n\
    echo "Creando .env..."\n\
    cp .env.example .env || echo "No se pudo crear .env"\n\
fi\n\
\n\
# Configurar permisos\n\
echo "Configurando permisos..."\n\
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache\n\
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache\n\
\n\
# Limpiar cache\n\
echo "Limpiando cache..."\n\
php artisan config:clear 2>/dev/null || true\n\
php artisan route:clear 2>/dev/null || true\n\
php artisan view:clear 2>/dev/null || true\n\
\n\
# Generar APP_KEY si no existe\n\
echo "Configurando APP_KEY..."\n\
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then\n\
    php artisan key:generate --force\n\
fi\n\
\n\
# Test crítico de Laravel\n\
echo "Verificando Laravel..."\n\
if php artisan --version; then\n\
    echo "✅ Laravel funciona correctamente"\n\
else\n\
    echo "❌ ERROR: Laravel no funciona"\n\
    echo "Verificando autoloader..."\n\
    php -r "require \"vendor/autoload.php\"; echo \"Autoloader OK\\n\";"\n\
    echo "Verificando bootstrap..."\n\
    php -r "\\$app = require \"bootstrap/app.php\"; echo \"Bootstrap OK\\n\";"\n\
fi\n\
\n\
# Optimizar solo si Laravel funciona\n\
echo "Optimizando aplicación..."\n\
php artisan config:cache 2>/dev/null || echo "Config cache omitido"\n\
php artisan route:cache 2>/dev/null || echo "Route cache omitido"\n\
\n\
# Ejecutar migraciones si DB está disponible\n\
echo "Verificando base de datos..."\n\
if php artisan migrate:status 2>/dev/null; then\n\
    echo "Ejecutando migraciones..."\n\
    php artisan migrate --force 2>/dev/null || echo "Migraciones omitidas"\n\
else\n\
    echo "Base de datos no disponible"\n\
fi\n\
\n\
echo "🚀 Iniciando Apache..."\n\
echo "========================================"\n\
\n\
# Iniciar Apache\n\
exec apache2-foreground' > /start.sh

RUN chmod +x /start.sh

# Exponer puerto
EXPOSE 80

# Comando de inicio
CMD ["/start.sh"]