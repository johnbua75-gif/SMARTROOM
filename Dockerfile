FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts

FROM php:8.4-apache-bookworm

RUN apt-get update \
	&& apt-get install -y --no-install-recommends \
		libfreetype6-dev \
		libicu-dev \
		libjpeg62-turbo-dev \
		libmariadb-dev \
		libonig-dev \
		libpng-dev \
		libpq-dev \
		libzip-dev \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install -j"$(nproc)" \
		bcmath \
		exif \
		gd \
		intl \
		mbstring \
		opcache \
		pcntl \
		pdo_mysql \
		pdo_pgsql \
		zip \
	&& a2enmod headers rewrite \
	&& rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

RUN sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
	&& sed -ri 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
	&& composer dump-autoload --no-dev --optimize --no-scripts \
	&& php artisan package:discover --ansi \
	&& php artisan storage:link --force \
	&& mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
	&& chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

COPY docker/entrypoint.sh /usr/local/bin/smartroom-entrypoint
RUN chmod +x /usr/local/bin/smartroom-entrypoint

CMD ["smartroom-entrypoint"]
