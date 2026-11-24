FROM php:8.3-apache
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo_pgsql && rm -rf /var/lib/apt/lists/*
RUN printf "upload_max_filesize=8M\npost_max_size=40M\nmax_file_uploads=20\n" > /usr/local/etc/php/conf.d/uploads.ini
COPY . /var/www/html/
RUN chmod +x /var/www/html/start.sh
CMD ["/var/www/html/start.sh"]
