# Usar PHP 8.2 con Apache
FROM php:8.2-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libxml2-dev \
    libzip-dev \
    libgd-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libonig-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Configurar y instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    soap \
    openssl \
    gd \
    zip \
    mbstring \
    xml \
    && docker-php-ext-enable soap openssl gd zip mbstring xml

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar Apache
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Crear directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de configuración de Composer
COPY composer.json composer.lock ./

# Instalar dependencias de PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copiar el código de la aplicación
COPY . .

# Crear directorios necesarios y establecer permisos
RUN mkdir -p logs public/facturas certs \
    && chown -R www-data:www-data logs public/facturas certs \
    && chmod -R 755 logs public/facturas certs

# Configurar Apache para servir desde el directorio public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's|<Directory /var/www/>|<Directory /var/www/html/public>|g' /etc/apache2/apache2.conf
RUN sed -i 's|</Directory>|</Directory>|g' /etc/apache2/apache2.conf

# Configurar variables de entorno para desarrollo
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Exponer puerto 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Comando por defecto
CMD ["apache2-foreground"] 