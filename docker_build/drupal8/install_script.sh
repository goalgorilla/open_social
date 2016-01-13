#!/bin/sh

FILE_EXISTS=`docker exec -it social_db_1 test -f /var/lib/mysql/social/users.ibd; echo $?`

if [ "$FILE_EXISTS" -eq 1 ]; then
  docker exec -it social_web_1 drush -y site-install social --db-url=mysql://root:root@db:3306/social --account-pass=admin install_configure_form.update_status_module='array(FALSE,FALSE)'
fi
