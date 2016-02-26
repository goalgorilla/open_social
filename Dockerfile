FROM drupal:8
MAINTAINER devel@goalgorilla.com

# Install packages.
RUN apt-get update && apt-get install -y \
  php-pclzip \
  mysql-client \
  git \
  ssmtp && \
  apt-get clean

ADD docker_build/drupal8/mailcatcher-ssmtp.conf /etc/ssmtp/ssmtp.conf

# Dockerhub currently runs on docker 1.8 and does not support the ARG command.
# Reset the logic after the dockerhub is updated.
# https://docs.docker.com/v1.8/reference/builder/
# ARG hostname=goalgorilla.com

RUN echo "hostname=goalgorilla.com" >> /etc/ssmtp/ssmtp.conf
RUN echo 'sendmail_path = "/usr/sbin/ssmtp -t"' > /usr/local/etc/php/conf.d/mail.ini

ADD docker_build/drupal8/php.ini /usr/local/etc/php/php.ini

RUN docker-php-ext-install zip

# Install bcmath
RUN docker-php-ext-install bcmath

# Install Composer.
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

# Install composer dependencies.
ADD docker_build/drupal8/composer.json /root/.composer/composer.json
ADD docker_build/drupal8/composer.lock /root/.composer/composer.lock
RUN composer global install --prefer-dist

# Unfortunately, adding the composer vendor dir to the PATH doesn't seem to work. So:
RUN ln -s /root/.composer/vendor/bin/drush /usr/local/bin/drush

ADD public_html/ /var/www/html/
WORKDIR /var/www/html/
RUN chown -R www-data:www-data *

# Install Drupal console
RUN curl https://drupalconsole.com/installer -L -o drupal.phar
RUN mv drupal.phar /usr/local/bin/drupal
RUN chmod +x /usr/local/bin/drupal

RUN if [ ! -f /root/.composer/vendor/drush/drush/lib/Console_Table-1.1.3/Table.php ]; then pear install Console_Table; fi

RUN php -r 'opcache_reset();'

# Fix shell.
RUN echo "export TERM=xterm" >> ~/.bashrc