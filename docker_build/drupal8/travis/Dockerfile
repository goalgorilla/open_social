FROM goalgorilla/drupal_social:latest
MAINTAINER devel@goalgorilla.com

# Install composer dependencies.
ADD composer.json /root/.composer/composer.json
ADD composer.lock /root/.composer/composer.lock
RUN composer global install --prefer-dist

# Unfortunately, adding the composer vendor dir to the PATH doesn't seem to work. So:
RUN ln -s /root/.composer/vendor/bin/behat /usr/local/bin/behat
RUN ln -s /root/.composer/vendor/bin/phpunit /usr/local/bin/phpunit

RUN php -r "opcache_reset();"
