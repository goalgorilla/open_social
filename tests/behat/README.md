# Behat

Behat is a tool to write _acceptance_ tests. We use the Gherkin syntax so that
we can create scenario's that are human-readable and can be shared with
non-technical stakeholders. The actual steps to test the human-readable
requirements should be implemented in contexts.

If you want to test regression or verify a bug, you should probably use PHPUnit
instead. The maintainers of the Lightning distribution wrote [a great article on when
you should and shouldn't use Behat](https://medium.com/@djphenaproxima/ive-been-using-behat-wrong-this-whole-time-ced6efd04e72).

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

## Behat Test and Context Organisation
Our [Behat features](https://docs.behat.org/en/latest/user_guide/features_scenarios.html)
live in `features/capabilities/<domain>/<feature>`. We use `<domain>` to
group related feature's together.

To aid with tasks around different domains [we create contexts](https://docs.behat.org/en/latest/user_guide/context/definitions.html)
for individual domains (e.g. `GroupContext` for group) that implement the
Gherkin steps.
