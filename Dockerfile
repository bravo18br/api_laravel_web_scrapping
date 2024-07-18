FROM php:8.2.20-apache
LABEL Author="Christoffer"
WORKDIR /var/www/html
ENV TZ=America/Sao_Paulo

# Instala dependências necessárias
RUN apt-get update && apt-get install -y \
    locales \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    gh \
    nano \
    cron \
    && localedef -i pt_BR -c -f UTF-8 -A /usr/share/locale/locale.alias pt_BR.UTF-8 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo pdo_mysql zip bcmath \
    && a2enmod rewrite proxy proxy_http \
    && apt-get clean

# Configura o idioma
ENV LANG pt_BR.utf8

# Copia configurações e scripts
COPY apache.conf /etc/apache2/sites-available/000-default.conf
COPY .env /root/.env
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY entrypoint.sh /usr/local/bin/
COPY my_cron_jobs /etc/cron.d/my_cron_jobs

# Define permissões corretas
RUN chmod +x /usr/local/bin/entrypoint.sh
RUN chmod 0644 /etc/cron.d/my_cron_jobs

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
