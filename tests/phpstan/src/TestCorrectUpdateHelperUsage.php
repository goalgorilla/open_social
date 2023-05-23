<?php

namespace Drupal\Tests\social\PHPStan;

use Drupal\social\PHPStan\Rules\CorrectUpdateHelperUsage;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Test the rule around UpdateHelper::executeUpdate.
 */
class TestCorrectUpdateHelperUsage extends RuleTestCase {

  /**
   * {@inheritdoc}
   */
  protected function getRule(): Rule {
    return new CorrectUpdateHelperUsage();
  }

  /**
   * Ensure incorrect usage is disallowed but correct usage is allowed.
   */
  public function testRule() : void {
    $this->analyse([__DIR__ . '/../data/install_example/install_example.install'], [
      [
        'Called UpdateHelper::executeUpdate in illegal_call but this should only be called within a hook_update_N function.',
        11,
      ],
    ]);
  }

}
