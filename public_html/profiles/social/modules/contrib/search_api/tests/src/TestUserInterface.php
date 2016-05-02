<?php

namespace Drupal\Tests\search_api;

use Drupal\user\UserInterface;

/**
 * Provides a testable version of \Drupal\user\UserInterface.
 *
 * @see https://github.com/sebastianbergmann/phpunit-mock-objects/issues/103
 */
interface TestUserInterface extends \Iterator, UserInterface {
}
