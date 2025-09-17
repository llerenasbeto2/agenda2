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
COPY composer.json composer.lock package*.json ./

# Instalar dependencias PHP (sin scripts para evitar errores)
RUN composer install --no-scripts --no-autoloader --no-dev --optimize-autoloader

# Instalar dependencias Node.js
RUN npm install --omit=dev

# Copiar el resto del código
COPY . .

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Completar instalación de Composer
RUN composer dump-autoload --optimize

# Configurar Apache para Laravel
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf
RUN echo '<VirtualHost *:80>\n\
    ServerName localhost\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        DirectoryIndex index.php index.html\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Crear script de inicio que espera la base de datos
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "=== INICIANDO APLICACIÓN ==="\n\
echo "PHP Version: $(php --version | head -n1)"\n\
echo "Laravel Version: $(php artisan --version)"\n\
echo "Working Directory: $(pwd)"\n\
echo "Files in public: $(ls -la public/ | head -5)"\n\
\n\
# Función para esperar la base de datos\n\
wait_for_db() {\n\
    echo "Esperando conexión a la base de datos..."\n\
    for i in {1..30}; do\n\
        if php artisan tinker --execute="DB::connection()->getPdo();" 2>/dev/null; then\n\
            echo "Base de datos conectada!"\n\
            return 0\n\
        fi\n\
        echo "Intento $i/30 fallido, esperando..."\n\
        sleep 2\n\
    done\n\
    echo "No se pudo conectar a la base de datos"\n\
    return 1\n\
}\n\
\n\
# Limpiar cache existente\n\
echo "Limpiando cache..."\n\
php artisan config:clear || true\n\
php artisan route:clear || true\n\
php artisan view:clear || true\n\
php artisan cache:clear || true\n\
\n\
# Verificar permisos\n\
echo "Configurando permisos..."\n\
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache\n\
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache\n\
\n\
# Optimizar aplicación\n\
echo "Optimizando aplicación..."\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan view:cache\n\
\n\
# Esperar y ejecutar migraciones solo si la DB está disponible\n\
if wait_for_db; then\n\
    echo "Ejecutando migraciones..."\n\
    php artisan migrate --force\n\
else\n\
    echo "Iniciando sin migraciones (DB no disponible)"\n\
fi\n\
\n\
# Verificar configuración final\n\
echo "=== VERIFICACIÓN FINAL ==="\n\
echo "APP_ENV: ${APP_ENV}"\n\
echo "APP_URL: ${APP_URL}"\n\
echo "Archivo index.php existe: $(test -f public/index.php && echo SI || echo NO)"\n\
\n\
# Iniciar Apache\n\
echo "Iniciando Apache..."\n\
exec apache2-foreground' > /start.sh

RUN chmod +x /start.sh

# Exponer puerto
EXPOSE 80

# Comando de inicio
CMD ["/start.sh"]