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

# Configurar PHP para debugging
RUN echo 'log_errors = On' >> /usr/local/etc/php/conf.d/docker-php-ext-custom.ini \
    && echo 'error_log = /var/log/php_errors.log' >> /usr/local/etc/php/conf.d/docker-php-ext-custom.ini \
    && echo 'display_errors = On' >> /usr/local/etc/php/conf.d/docker-php-ext-custom.ini \
    && echo 'memory_limit = 256M' >> /usr/local/etc/php/conf.d/docker-php-ext-custom.ini

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Configurar el directorio de trabajo
WORKDIR /var/www/html

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar archivos de dependencias primero
COPY composer.json composer.lock ./
COPY package*.json ./

# Instalar dependencias PHP
RUN composer install --no-scripts --no-autoloader --no-dev --optimize-autoloader

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

# Completar instalación de Composer
RUN composer dump-autoload --optimize

# Configurar Apache
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Configuración detallada del VirtualHost
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
    # Configuración de logs detallada\n\
    LogLevel info\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    \n\
    # Configuración PHP en Apache\n\
    php_admin_value memory_limit 256M\n\
    php_admin_flag display_errors On\n\
    php_admin_flag log_errors On\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Script de inicio con diagnóstico completo
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "========================================"\n\
echo "    DIAGNÓSTICO COMPLETO - RAILWAY"\n\
echo "========================================"\n\
\n\
# Información del sistema\n\
echo "=== INFORMACIÓN DEL SISTEMA ==="\n\
echo "Fecha: $(date)"\n\
echo "Usuario: $(whoami)"\n\
echo "PHP Version: $(php --version | head -n1)"\n\
echo "Apache Version: $(apache2 -v | head -n1)"\n\
echo "Working Directory: $(pwd)"\n\
echo "Memory disponible: $(free -h | grep Mem)"\n\
echo "Espacio en disco: $(df -h . | tail -1)"\n\
\n\
# Verificar estructura de archivos\n\
echo "\n=== ESTRUCTURA DE ARCHIVOS ==="\n\
echo "Archivos en raíz: $(ls -la | wc -l) archivos"\n\
echo "public/index.php: $(test -f public/index.php && echo ✓ EXISTE || echo ✗ FALTA)"\n\
echo "bootstrap/app.php: $(test -f bootstrap/app.php && echo ✓ EXISTE || echo ✗ FALTA)"\n\
echo ".env file: $(test -f .env && echo ✓ EXISTE || echo ✗ FALTA)"\n\
echo ".env.example: $(test -f .env.example && echo ✓ EXISTE || echo ✗ FALTA)"\n\
echo "composer.json: $(test -f composer.json && echo ✓ EXISTE || echo ✗ FALTA)"\n\
echo "artisan: $(test -f artisan && echo ✓ EXISTE || echo ✗ FALTA)"\n\
\n\
# Mostrar contenido de public/index.php\n\
if [ -f public/index.php ]; then\n\
    echo "Primeras líneas de public/index.php:"\n\
    head -5 public/index.php\n\
else\n\
    echo "⚠ public/index.php NO EXISTE - ESTO CAUSA ERROR 502"\n\
    echo "Contenido de public/:"\n\
    ls -la public/ 2>/dev/null || echo "Directorio public no existe"\n\
fi\n\
\n\
# Verificar permisos\n\
echo "\n=== PERMISOS ==="\n\
echo "Permisos /var/www/html: $(ls -ld /var/www/html)"\n\
echo "Permisos public/: $(ls -ld public/ 2>/dev/null || echo NO EXISTE)"\n\
echo "Permisos storage/: $(ls -ld storage/ 2>/dev/null || echo NO EXISTE)"\n\
\n\
# Crear .env si no existe\n\
if [ ! -f .env ]; then\n\
    echo "\n=== CREANDO .ENV ==="\n\
    if [ -f .env.example ]; then\n\
        cp .env.example .env\n\
        echo "✓ .env creado desde .env.example"\n\
    else\n\
        echo "APP_NAME=Laravel" > .env\n\
        echo "APP_ENV=production" >> .env\n\
        echo "APP_KEY=" >> .env\n\
        echo "APP_DEBUG=true" >> .env\n\
        echo "APP_URL=http://localhost" >> .env\n\
        echo "✓ .env básico creado"\n\
    fi\n\
fi\n\
\n\
# Configurar permisos críticos\n\
echo "\n=== CONFIGURANDO PERMISOS ==="\n\
chown -R www-data:www-data /var/www/html 2>/dev/null || echo "Chown falló"\n\
chmod -R 775 /var/www/html/storage 2>/dev/null || echo "Chmod storage falló"\n\
chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || echo "Chmod bootstrap falló"\n\
\n\
# Limpiar cache\n\
echo "\n=== LIMPIANDO CACHE ==="\n\
php artisan config:clear 2>/dev/null || echo "Config clear falló"\n\
php artisan route:clear 2>/dev/null || echo "Route clear falló"\n\
php artisan view:clear 2>/dev/null || echo "View clear falló"\n\
\n\
# Generar APP_KEY si está vacía\n\
echo "\n=== CONFIGURANDO APP_KEY ==="\n\
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then\n\
    php artisan key:generate --force 2>/dev/null && echo "✓ APP_KEY generada" || echo "✗ Error generando APP_KEY"\n\
fi\n\
\n\
# Test básico de PHP\n\
echo "\n=== TEST PHP BÁSICO ==="\n\
php -r "echo \"PHP funciona correctamente\\n\";" || echo "⚠ PHP tiene problemas"\n\
\n\
# Test de Laravel\n\
echo "\n=== TEST LARAVEL ==="\n\
if php artisan --version 2>/dev/null; then\n\
    echo "✓ Laravel funciona"\n\
else\n\
    echo "✗ Laravel tiene problemas"\n\
    echo "Error detallado:"\n\
    php artisan --version 2>&1 || true\n\
fi\n\
\n\
# Test de base de datos (opcional)\n\
echo "\n=== TEST BASE DE DATOS ==="\n\
php -r "try { \n\
    require __DIR__.\"/bootstrap/app.php\"; \n\
    \$app = require_once __DIR__.\"/bootstrap/app.php\"; \n\
    \$app->make(\"Illuminate\\Contracts\\Console\\Kernel\")->bootstrap(); \n\
    DB::connection()->getPdo(); \n\
    echo \"✓ Base de datos conectada\\n\"; \n\
} catch(Exception \$e) { \n\
    echo \"⚠ DB no disponible: \" . \$e->getMessage() . \"\\n\"; \n\
}" 2>/dev/null || echo "⚠ No se pudo probar la DB"\n\
\n\
# Mostrar variables de entorno importantes\n\
echo "\n=== VARIABLES DE ENTORNO ==="\n\
echo "APP_ENV: ${APP_ENV:-NOT_SET}"\n\
echo "APP_DEBUG: ${APP_DEBUG:-NOT_SET}"\n\
echo "APP_URL: ${APP_URL:-NOT_SET}"\n\
echo "DB_HOST: ${DB_HOST:-NOT_SET}"\n\
echo "PORT: ${PORT:-NOT_SET}"\n\
\n\
# Optimizar aplicación\n\
echo "\n=== OPTIMIZACIÓN FINAL ==="\n\
php artisan config:cache 2>/dev/null || echo "Config cache falló"\n\
php artisan route:cache 2>/dev/null || echo "Route cache falló"\n\
\n\
# Verificar que Apache puede arrancar\n\
echo "\n=== VERIFICANDO CONFIGURACIÓN APACHE ==="\n\
apache2ctl configtest 2>&1 || echo "⚠ Configuración Apache tiene problemas"\n\
\n\
echo "\n=== INICIANDO APACHE ==="\n\
echo "Si ves este mensaje, Apache debería iniciar ahora..."\n\
echo "Revisa los logs HTTP en Railway para ver errores 502"\n\
echo "========================================"\n\
\n\
# Iniciar Apache en foreground\n\
exec apache2-foreground' > /start.sh

RUN chmod +x /start.sh

# Exponer puerto
EXPOSE 80

# Comando de inicio
CMD ["/start.sh"]