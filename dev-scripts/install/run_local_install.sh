#!/bin/sh

OPTION=$1
FILE_EXISTS=`docker-compose run db test -f /var/lib/mysql/social/users.ibd; echo $?`

if [ "$OPTION"  == "reset" ] || [ "$FILE_EXISTS" -eq 1 ]; then
  docker-compose run web bash /root/dev-scripts/install/install_script.sh
fi
