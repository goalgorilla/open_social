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
- You can execute all behat tests by using the following commands, the common behat options can be added to control what test is executed.
```
# Execute all tests
docker exec -it social_web /var/www/vendor/bin/behat --config /var/www/html/profiles/contrib/social/tests/behat/behat.yml

# Execute a specific test from flexible groups
docker exec -it social_web /var/www/vendor/bin/behat --config /var/www/html/profiles/contrib/social/tests/behat/behat.yml /var/www/html/profiles/contrib/social/tests/behat/features/capabilities/groups/flexible/groups-flexible-create.feature

# Execute tests introduced for a specific issue by using a tag
docker exec -it social_web /var/www/vendor/bin/behat --config /var/www/html/profiles/contrib/social/tests/behat/behat.yml --tags=ECI-632

# Run all tests except those with a specific tag
docker exec -it social_web /var/www/vendor/bin/behat --config /var/www/html/profiles/contrib/social/tests/behat/behat.yml --tags='~no-install'

# Get an overview of all arguments that can be passed to Behat
docker exec -it social_web /var/www/vendor/bin/behat --help
```
- Failed Behat tests will generate a screenshot and html in the following directory:
```
/var/www/html/profiles/contrib/social/tests/behat/logs
```

## Controlling test databases
When first running behat witth the above command you'll see warning such as
```
No database file specified, using the fallback at '/var/www/html/profiles/contrib/social/tests/behat/fixture/fallback.dump.sql'. Specify a database file using 'TEST_DATABASE'.`
```

To ensure every test runs in a predictable manner our `DatabaseContext` will ensure a database file is loaded before
each scenario. This file can be specified with the `TEST_DATABASE` environment variable. However, if no file is specified
the context will look for a `fallback.dump.sql` file and create it from the current database if it doesn't exist.

This behaviour can be disabled on a `Feature` level using the `@no-database` tag.

## Controlling test execution using Behat tags
Various tags can be added to a `Feature` or a `Scenario` to change its behaviour
or allow it to be executed in isolation. Below is an overview of supported tags
in our tests.

### `@api`
This tag is required for every `Feature` to enable the DrupalExtension and be
able to interact with the Drupal APIs.

### `@javascript`
This is needed to enable JavaScript support in tests and allow waiting for the UI
to perform certain client side actions.

### `@no-database`
This tag can be used to exclude the feature or scenario from loading the
`TEST_DATABASE`. Instead the scenarios affected by this test must use the test
step `Given the fixture <file.sql>`

### `@no-install`
This tag can be used to indicate that a scenario should only be run for update
testing in our CI. This can be used for tests that ensure a specific behaviour
is preserved for updated sites but does not exist for new installations
(e.g removing some default module).

### `@no-update`
This tag can be used to indicate that a scenario should only be run for install
testing in our CI. This can be used for tests that ensure a specific behaviour
is available on new sites but does not exist for existing installations
(e.g. a theme change).

### `@disabled`
Can be used to temporarily disable a test. If the test is no longer needed
prefer removing the test altogether, it can always be retrieved from git
history.

## Behat Test and Context Organisation
Our [Behat features](https://docs.behat.org/en/latest/user_guide/features_scenarios.html)
live in `features/capabilities/<domain>/<feature>`. We use `<domain>` to
group related feature's together.

To aid with tasks around different domains [we create contexts](https://docs.behat.org/en/latest/user_guide/context/definitions.html)
for individual domains (e.g. `GroupContext` for group) that implement the
Gherkin steps.
