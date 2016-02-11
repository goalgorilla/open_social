#!/usr/bin/env bash

DRUPAL_ROOT=/var/www/html
DEV_SCRIPTS=/root/dev-scripts

php -d xdebug.extended_info=0 -d xdebug.remote_autostart=0 -d xdebug.coverage_enable=0 -d xdebug.profiler_enable=0 -d xdebug.remote_enable=0 $DRUPAL_ROOT/vendor/bin/phpunit -c $DEV_SCRIPTS/phpunit.xml.dist --testsuite social
