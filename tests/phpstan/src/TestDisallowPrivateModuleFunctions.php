<?php

namespace Drupal\Tests\social\PHPStan;

use Drupal\social\PHPStan\Rules\DisallowPrivateModuleFunctions;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Test the rule covering correct calling of private methods.
 */
class TestDisallowPrivateModuleFunctions extends RuleTestCase {

  /**
   * {@inheritdoc}
   */
  protected function getRule(): Rule {
    return new DisallowPrivateModuleFunctions();
  }

  /**
   * Ensure incorrect usage is disallowed but correct usage is allowed.
   */
  public function testRule() : void {
    $this->analyse([__DIR__ . '/../data/private_function_example/private_function_example.module'], [
      [
        'Not allowed to call private function _some_modules_function_we_are_not_allowed_to_call from module private_function_example.',
        3,
      ],
    ]);
  }

}
