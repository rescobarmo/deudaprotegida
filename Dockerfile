FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install -j"$(nproc)" pdo pdo_mysql mysqli mbstring \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod headers rewrite setenvif

COPY docker/apache.conf /etc/apache2/conf-available/deudaprotegida.conf
RUN a2enconf deudaprotegida

RUN mkdir -p /var/lib/php/sessions \
    && chown -R www-data:www-data /var/lib/php/sessions \
    && { \
        echo "display_errors=Off"; \
        echo "log_errors=On"; \
        echo "error_log=/dev/stderr"; \
        echo "expose_php=Off"; \
        echo "session.save_path=/var/lib/php/sessions"; \
        echo "session.cookie_httponly=1"; \
        echo "session.use_strict_mode=1"; \
        echo "date.timezone=America/Santiago"; \
        echo "memory_limit=256M"; \
        echo "opcache.enable=1"; \
    } > /usr/local/etc/php/conf.d/deudaprotegida.ini

COPY --chown=www-data:www-data . /var/www/html/
COPY --chown=www-data:www-data api/config.example.php /var/www/html/api/config.php
RUN rm -rf /var/www/html/docker

EXPOSE 80
