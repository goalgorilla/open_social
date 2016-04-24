<?php

namespace Drupal\Tests\search_api;

use Drupal\node\NodeInterface;

/**
 * Provides a testable version of \Drupal\node\NodeInterface.
 *
 * @see https://github.com/sebastianbergmann/phpunit-mock-objects/issues/103
 */
interface TestNodeInterface extends \Iterator, NodeInterface {
}
