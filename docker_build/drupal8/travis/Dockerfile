FROM goalgorilla/drupal_social:latest
MAINTAINER devel@goalgorilla.com

# Install development related packages.
RUN apt-get update && apt-get install -y \
git

# Install composer dependencies.
ADD composer.json /root/.composer/composer.json
RUN composer global update --prefer-stable

# Unfortunately, adding the composer vendor dir to the PATH doesn't seem to work. So:
RUN ln -s /root/.composer/vendor/bin/behat /usr/local/bin/behat
RUN ln -s /root/.composer/vendor/bin/phpunit /usr/local/bin/phpunit
