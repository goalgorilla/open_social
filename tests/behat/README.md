# Behat

## How to run Behat tests locally

- Check that `social_chrome` and `social_web` containers are running by running the following command:
```
docker ps
```
- If the containers are not running execute the following command in the root of your projects:
```
docker-compose up --force-recreate -d --remove-orphans
```
- If the containers are still not available, review the `docker-composer.yml` file in the project root.
- Run a Behat tests by executing the below command, the last argument is an optional Behat tag; if left empty all tests will be executed.
```
docker exec -it social_web sh /var/www/scripts/social/behatstability.sh DS-233
```
- Failed Behat tests will generate a screenshot and html in the following directory:
```
/var/www/html/profiles/contrib/social/tests/behat/logs
```
