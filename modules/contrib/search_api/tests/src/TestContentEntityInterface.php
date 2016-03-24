<?php

/**
 * @file
 * Contains \Drupal\Tests\search_api\TestContentEntityInterface.
 */

namespace Drupal\Tests\search_api;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides a testable version of \Drupal\Core\Entity\ContentEntityInterface.
 *
 * @see https://github.com/sebastianbergmann/phpunit-mock-objects/issues/103
 */
interface TestContentEntityInterface extends \Iterator, ContentEntityInterface {
}
