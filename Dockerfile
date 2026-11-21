# ---------- Stage 1: build frontend assets (Vite) ----------
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---------- Stage 2: PHP + Nginx runtime ----------
FROM serversideup/php:8.3-fpm-nginx AS runtime
WORKDIR /var/www/html

# On container start: run migrations, storage:link, config/route/view cache
ENV AUTORUN_ENABLED=true
ENV PHP_OPCACHE_ENABLE=1

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist

COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev

EXPOSE 8080
