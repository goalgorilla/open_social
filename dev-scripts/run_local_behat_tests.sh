#!/usr/bin/env bash

# Initiate the docker environment.
status_default=`docker-machine status default`
if [ "$status_default" == "Running" ]; then
  echo "Docker enviroment set for default machine"
  eval "$(docker-machine env default)";
fi
export PLATFORM_DOCKER_MACHINE_NAME="default"

docker exec -i social_behat sh /root/dev-scripts/behatstability.sh