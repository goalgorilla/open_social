# Docker #

Download and install the [toolbox](https://www.docker.com/docker-toolbox).

Note that the docker projects have to be somewhere in your /Users/ directory in order to work (limitation for Mac and Windows). Note that /Users/<name>/Sites/Docker is fine.


# Installation #

1. Start a docker machine (docker quickstart icon).

2. Clone this repository to the directory of your choice (e.g. ~/Sites/social).

3. Go inside the folder in which you cloned this repository (where the docker-compose.yml file is).

4. Build and start the docker containers.
    ```
    docker-compose up -d
    ```

    This will build multiple containers (see the Dockerfile in docker_build/drupal8) and all the dependencies.

5. Add social.dev to your /etc/hosts file based on the ip of the docker machine.

    If necessary you can find the IP with this command on your host machine:
    ```
    docker-machine ls
    ```

6. Run the install script on your host machine.
    ```
    sh docker_build/drupal8/install_script.sh
    ```

# Usage #

**If you want to see which containers are running:**
```
docker ps
```

**SSH into the container:**
```
docker exec -it social_web_1 bash
```
Here you can use _drush_ and _drupal list_.

**If you want to re-install, execute these commands in the social_web_1 container:**
```
drush sql-drop -y;
rm -f sites/default/settings.php
rm -f sites/default/settings.local.php
rm -rf sites/default/files
```

Now run the install script on your host machine again.
```
sh docker_build/drupal8/install_script.sh
```