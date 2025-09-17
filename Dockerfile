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
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Configurar el directorio de trabajo
WORKDIR /var/www/html

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar archivos de dependencias primero (para cache de Docker)
COPY composer.json composer.lock ./
COPY package*.json ./

# Instalar dependencias PHP
RUN composer install --no-scripts --no-autoloader --no-dev --optimize-autoloader

# Instalar dependencias Node.js
RUN npm install --omit=dev

# Copiar el resto del código
COPY . .

# Configurar permisos ANTES de optimizar
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Completar instalación de Composer
RUN composer dump-autoload --optimize

# Configurar Apache para Laravel
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Configuración mejorada del VirtualHost
RUN echo '<VirtualHost *:80>\n\
    ServerName localhost\n\
    DocumentRoot /var/www/html/public\n\
    \n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
        DirectoryIndex index.php index.html\n\
    </Directory>\n\
    \n\
    # Logs más detallados\n\
    LogLevel info\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    \n\
    # Configuración PHP\n\
    php_flag display_errors On\n\
    php_flag log_errors On\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Script de inicio mejorado
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "=== INICIANDO APLICACIÓN ==="\n\
echo "PHP Version: $(php --version | head -n1)"\n\
echo "Working Directory: $(pwd)"\n\
echo "Usuario actual: $(whoami)"\n\
\n\
# Verificar estructura de archivos críticos\n\
echo "=== VERIFICACIÓN DE ARCHIVOS ==="\n\
echo "public/index.php: $(test -f public/index.php && echo ✓ || echo ✗)"\n\
echo ".env: $(test -f .env && echo ✓ || echo ✗)"\n\
echo "bootstrap/app.php: $(test -f bootstrap/app.php && echo ✓ || echo ✗)"\n\
\n\
# Crear .env si no existe\n\
if [ ! -f .env ]; then\n\
    echo "Creando .env desde .env.example..."\n\
    cp .env.example .env || echo "No se pudo crear .env"\n\
fi\n\
\n\
# Verificar y configurar permisos\n\
echo "=== CONFIGURANDO PERMISOS ==="\n\
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true\n\
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true\n\
\n\
# Limpiar cache existente\n\
echo "=== LIMPIANDO CACHE ==="\n\
php artisan config:clear 2>/dev/null || echo "Config clear falló"\n\
php artisan route:clear 2>/dev/null || echo "Route clear falló"\n\
php artisan view:clear 2>/dev/null || echo "View clear falló"\n\
php artisan cache:clear 2>/dev/null || echo "Cache clear falló"\n\
\n\
# Generar APP_KEY si no existe\n\
echo "=== CONFIGURANDO APP_KEY ==="\n\
if ! grep -q "APP_KEY=base64:" .env; then\n\
    php artisan key:generate --force\n\
    echo "APP_KEY generada"\n\
fi\n\
\n\
# Función para verificar la base de datos\n\
wait_for_db() {\n\
    echo "=== VERIFICANDO BASE DE DATOS ==="\n\
    for i in {1..10}; do\n\
        if php artisan tinker --execute="try { DB::connection()->getPdo(); echo \"DB OK\"; } catch(Exception \$e) { echo \"DB Error: \" . \$e->getMessage(); exit(1); }" 2>/dev/null; then\n\
            echo "✓ Base de datos conectada"\n\
            return 0\n\
        fi\n\
        echo "Intento $i/10 fallido..."\n\
        sleep 3\n\
    done\n\
    echo "⚠ Base de datos no disponible"\n\
    return 1\n\
}\n\
\n\
# Intentar ejecutar migraciones\n\
if wait_for_db; then\n\
    echo "=== EJECUTANDO MIGRACIONES ==="\n\
    php artisan migrate --force 2>/dev/null || echo "Migraciones fallaron"\n\
fi\n\
\n\
# Optimizar solo después de que todo esté configurado\n\
echo "=== OPTIMIZANDO APLICACIÓN ==="\n\
php artisan config:cache 2>/dev/null || echo "Config cache falló"\n\
php artisan route:cache 2>/dev/null || echo "Route cache falló"\n\
php artisan view:cache 2>/dev/null || echo "View cache falló"\n\
\n\
# Verificación final crítica\n\
echo "=== VERIFICACIÓN FINAL ==="\n\
echo "APP_ENV: ${APP_ENV:-local}"\n\
echo "APP_URL: ${APP_URL:-http://localhost}"\n\
echo "Puerto Apache: $(netstat -tlnp 2>/dev/null | grep :80 || echo No encontrado)"\n\
\n\
# Mostrar los primeros errores de Laravel si existen\n\
if [ -f storage/logs/laravel.log ]; then\n\
    echo "=== ÚLTIMOS ERRORES LARAVEL ==="\n\
    tail -10 storage/logs/laravel.log 2>/dev/null || true\n\
fi\n\
\n\
echo "=== INICIANDO APACHE ==="\n\
exec apache2-foreground' > /start.sh

RUN chmod +x /start.sh

# Exponer puerto
EXPOSE 80

# Comando de inicio
CMD ["/start.sh"]