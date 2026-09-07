FROM php:8.5-cli-alpine

WORKDIR /app

# Dependencias del sistema
RUN apk add --no-cache \
        git \
        unzip \
        icu-dev \
        libzip-dev \
        bash \
        $PHPIZE_DEPS

# Extensiones: NOTE en PHP 8.5 opcache ya viene integrado (no opcional)
# así que NO se lista. pdo antes de pdo_mysql. Los "proc" extra no hacen falta.
RUN docker-php-ext-install pdo pdo_mysql \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl zip

# Instalar Composer 2
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instalar Symfony CLI (necesita bash, ya instalado arriba)
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

EXPOSE 8000

CMD ["symfony", "serve", "--port=8000", "--no-tls", "--allow-all-ip"]