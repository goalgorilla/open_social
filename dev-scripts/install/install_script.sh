#!/bin/bash

# Install script for in the docker container.
# Only should be used for local development!
# See docker_build for install scripts for other environments.
cd /var/www/html/;
drush -y site-install social --db-url=mysql://root:root@db:3306/social --account-pass=admin install_configure_form.update_status_module='array(FALSE,FALSE)';
chmod 777 sites/default/settings.php;

# TODO, can probably improve this by using drupal_rewrite_settings?
PATTERN="\$settings['trusted_host_patterns'] = array('[\s\S]*');";
HOSTED_PATTERN_EXISTS=`grep -Fxq "$PATTERN" sites/default/settings.php; echo $?;`;
if [ "$HOSTED_PATTERN_EXISTS" -eq 1 ]; then
  echo "Set to trust all patterns in trusted host patterns config in settings.php";
  echo ${PATTERN} >> sites/default/settings.php;
fi
php -r 'opcache_reset();';
drush genu 5 --pass=test;
chmod 444 sites/default/settings.php
drupal create:nodes topic --limit=250 --title-words=12 --time-range=Y
