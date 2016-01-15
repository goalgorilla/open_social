#!/usr/bin/env bash

DRUPAL_ROOT=/var/www/html

docker-compose -f docker-compose-travis.yml run web_scripts php -d xdebug.extended_info=0 -d xdebug.remote_autostart=0 -d xdebug.coverage_enable=0 -d xdebug.profiler_enable=0 -d xdebug.remote_enable=0 $DRUPAL_ROOT/vendor/bin/phpunit -c $DRUPAL_ROOT/core/phpunit.xml.dist --testsuite unit
