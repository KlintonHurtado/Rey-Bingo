FROM php:8.2-apache

# Evitar prompts interactivos
ENV DEBIAN_FRONTEND=noninteractive

# Instalar dependencias del sistema, librerias PHP, Node.js y Supervisor
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    git \
    unzip \
    supervisor \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    ca-certificates \
    gnupg \
    && rm -rf /var/lib/apt/lists/*

# Instalar Node.js 20 LTS y Soketi globalmente
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && npm install -g @soketi/soketi \
    && rm -rf /var/lib/apt/lists/*

# Configurar e instalar extensiones de PHP requeridas por CodeIgniter 4
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        intl \
        mbstring \
        mysqli \
        pdo \
        pdo_mysql \
        gd \
        zip \
        opcache

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Habilitar modulos necesarios de Apache (mod_rewrite, proxy para Soketi WebSockets)
RUN a2enmod rewrite headers proxy proxy_http proxy_wstunnel

# Configurar Apache VirtualHost
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Configurar Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copiar el codigo del proyecto al contenedor
WORKDIR /var/www/html
COPY . /var/www/html

# Instalar dependencias de Composer si existe composer.json
RUN if [ -f /var/www/html/reybingo.com/public_html/composer.json ]; then \
        cd /var/www/html/reybingo.com/public_html && \
        composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/reybingo.com/public_html/writable \
    && chmod +x /var/www/html/docker/entrypoint.sh

# Exponer el puerto web de Apache
EXPOSE 80

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
