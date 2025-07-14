# Multi-stage build para optimizar la imagen final
FROM composer:2.6 AS composer

# Etapa de construcción
FROM php:8.2-apache AS builder

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libxml2-dev \
    libzip-dev \
    libgd-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libonig-dev \
    libsqlite3-dev \
    unzip \
    git \
    curl \
    sqlite3 \
    && rm -rf /var/lib/apt/lists/*

# Configurar y instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    soap \
    gd \
    zip \
    mbstring \
    xml \
    pdo \
    pdo_sqlite \
    && docker-php-ext-enable soap gd zip mbstring xml pdo pdo_sqlite

# Configurar PHP para producción
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Configuración personalizada de PHP
RUN echo "memory_limit = 256M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "upload_max_filesize = 64M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "post_max_size = 64M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "max_execution_time = 300" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "date.timezone = America/Argentina/Buenos_Aires" >> "$PHP_INI_DIR/conf.d/custom.ini"

# Configurar Apache
RUN a2enmod rewrite headers ssl
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configurar Apache para la aplicación
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Etapa final
FROM builder AS production

# Copiar Composer desde la imagen oficial
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Crear usuario y grupo para la aplicación
RUN groupadd -g 1000 afipapi && useradd -u 1000 -g 1000 -s /bin/bash -m afipapi

# Crear directorios de trabajo
WORKDIR /app

# Copiar archivos de configuración de dependencias
COPY --chown=afipapi:afipapi composer.json composer.lock ./

# Instalar dependencias de PHP (como root temporalmente)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copiar el código de la aplicación
COPY --chown=afipapi:afipapi . .

# Crear directorios necesarios y establecer permisos
RUN mkdir -p \
    database \
    logs \
    public/facturas \
    storage/certificates \
    storage/uploads \
    var/cache \
    var/tmp \
    && chown -R afipapi:afipapi \
    database \
    logs \
    public/facturas \
    storage \
    var \
    && chmod -R 755 \
    database \
    logs \
    public/facturas \
    storage \
    var

# Configurar el index principal
RUN ln -sf /app/public/index_v2.php /app/public/index.php

# Hacer el script de cliente ejecutable
RUN chmod +x bin/client-manager.php

# Configurar Apache para servir desde el directorio public
ENV APACHE_DOCUMENT_ROOT=/app/public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /app/public|g' /etc/apache2/sites-available/000-default.conf

# Configurar el directorio de Apache
RUN echo '<Directory /app/public>' >> /etc/apache2/apache2.conf \
    && echo '    Options Indexes FollowSymLinks' >> /etc/apache2/apache2.conf \
    && echo '    AllowOverride All' >> /etc/apache2/apache2.conf \
    && echo '    Require all granted' >> /etc/apache2/apache2.conf \
    && echo '</Directory>' >> /etc/apache2/apache2.conf

# Variables de entorno por defecto
ENV PHP_ENV=production
ENV DB_TYPE=sqlite
ENV DB_PATH=/app/database/clients.db
ENV LOG_LEVEL=info
ENV CERTIFICATES_PATH=/app/storage/certificates
ENV FACTURAS_PATH=/app/public/facturas

# Crear script de inicialización
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Exponer puerto 80
EXPOSE 80

# Health check mejorado
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/api/v1/health?api_key=health_check || exit 1

# Script de entrada
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Comando por defecto
CMD ["apache2-foreground"] 