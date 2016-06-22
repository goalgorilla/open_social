<?php

namespace Drupal\Tests\search_api;

use Drupal\Core\TypedData\ComplexDataInterface;

/**
 * Provides a testable version of \Drupal\Core\TypedData\ComplexDataInterface.
 *
 * @see https://github.com/sebastianbergmann/phpunit-mock-objects/issues/103
 */
interface TestComplexDataInterface extends \Iterator, ComplexDataInterface {
}
