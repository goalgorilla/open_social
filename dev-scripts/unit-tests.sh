#!/usr/bin/env bash

DRUPAL_ROOT=/var/www/html

docker-compose -f docker-compose-travis.yml run web_scripts $DRUPAL_ROOT/vendor/bin/phpunit -c $DRUPAL_ROOT/core/phpunit.xml.dist --testsuite unit  --coverage-html /root/test-results/phpunit.html --coverage-xml /root/test-results/phpunit.xml