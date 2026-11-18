FROM node:24-bookworm-slim AS frontend
WORKDIR /build/frontend
COPY frontend/package*.json ./
RUN npm ci --no-audit --no-fund
COPY frontend/ ./
RUN npm run build

FROM php:8.4-apache-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev libsqlite3-dev libzip-dev libonig-dev libxml2-dev unzip git && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype && docker-php-ext-install -j2 gd pdo_sqlite bcmath mbstring zip dom && a2enmod rewrite headers && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY backend/ ./backend/
COPY hosting/ ./hosting/
RUN cd backend && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/hosted-demo
COPY --from=frontend /build/frontend/dist/art-gallery/ ./backend/public/
RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /app/backend/public#' /etc/apache2/sites-available/000-default.conf && printf '<Directory /app/backend/public>\n  AllowOverride All\n  Require all granted\n</Directory>\n' > /etc/apache2/conf-available/gallery.conf && a2enconf gallery && printf 'display_errors=Off\nlog_errors=On\nupload_max_filesize=1M\npost_max_size=2M\n' > /usr/local/etc/php/conf.d/gallery.ini
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr LOG_LEVEL=error DB_CONNECTION=sqlite SESSION_DRIVER=file SESSION_SECURE_COOKIE=true SESSION_HTTP_ONLY=true SESSION_SAME_SITE=lax SESSION_ENCRYPT=true CACHE_STORE=file QUEUE_CONNECTION=sync SHOP_DEMO=true SHOP_HOSTED_DEMO=true SHOP_CHECKOUT_MODE=test SHOP_CURRENCY=EUR SHOP_TEST_SHIPPING_CENTS=0 SHOP_TEST_TAX_BASIS_POINTS=0 MAIL_MAILER=log
EXPOSE 80
CMD ["sh", "/app/hosting/start.sh"]
