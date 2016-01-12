#!/bin/sh

FILE_EXISTS=`docker exec -it social_db_1 test -f /var/lib/mysql/social/users.ibd; echo $?`

if [ "$FILE_EXISTS" -eq 1 ]; then
  docker exec -it social_web_1 drush site-install social --db-url=mysql://root:root@db:3306/social --account-pass=admin
fi
