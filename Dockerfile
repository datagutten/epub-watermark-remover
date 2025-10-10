FROM php:8.4
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN apt-get update && apt-get install -y libzip-dev zip

WORKDIR /app
COPY composer.json .
COPY src src
COPY class_remove_epub_watermark.php .

RUN composer update

ENTRYPOINT ["php", "remove_watermark.php"]