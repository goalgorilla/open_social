#!/bin/bash

# Script to update Drupal in the docker container.
cd /var/www/html/;

drush -y updatedb