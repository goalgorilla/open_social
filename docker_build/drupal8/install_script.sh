#!/bin/sh

FILE_EXISTS=`docker-compose run db test -f /var/lib/mysql/social/users.ibd; echo $?`

if [ "$FILE_EXISTS" -eq 1 ]; then
  docker-compose run web drush -y site-install social --db-url=mysql://root:root@db:3306/social --account-pass=admin install_configure_form.update_status_module='array(FALSE,FALSE)'
fi
