FROM php:5.6.19-fpm

RUN apt-get update && apt-get install -y \
	monit \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng12-dev \
    && rm -r /var/lib/apt/lists/* \
    && docker-php-ext-install -j$(nproc) mbstring \
    && docker-php-ext-install -j$(nproc) pdo_mysql \
    && docker-php-ext-install -j$(nproc) iconv mcrypt \
    && docker-php-ext-install -j$(nproc) zip \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
	&& docker-php-ext-configure opcache --enable-opcache && docker-php-ext-install opcache

# Copy configuration
COPY conf/opcache.ini $PHP_INI_DIR/conf.d/
	
RUN apt-get update && apt-get install -y curl vim git mysql-client && rm -r /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin && ln -s /usr/bin/composer.phar /usr/bin/composer

RUN composer.phar global require drush/drush:7.1.0

RUN ln -s /root/.composer/vendor/bin/drush /usr/bin/drush 

RUN  cp /usr/src/php/php.ini-development /usr/local/etc/php/php.ini

RUN sed -i -- 's/memory_limit = 128M/memory_limit = 512M/g' /usr/local/etc/php/php.ini
RUN echo "date.timezone=America/Sao_Paulo" >> /usr/local/etc/php/php.ini
RUN echo "always_populate_raw_post_data=-1" >> /usr/local/etc/php/php.ini
ADD conf/files.sh /usr/bin/+files
RUN chmod +x /usr/bin/+files
WORKDIR /var/www/public_html
