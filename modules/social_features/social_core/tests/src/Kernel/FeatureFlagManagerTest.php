<?php

declare(strict_types=1);

namespace Drupal\Tests\social_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\social_core\FeatureFlag\FeatureFlagState;
use Drupal\social_core\Service\FeatureFlagCheckerInterface;
use Drupal\social_core\Service\FeatureFlagManagerInterface;

/**
 * Tests the feature flag manager service.
 *
 * @group social_core
 */
class FeatureFlagManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'social_core',
    'social_core_feature_flags_test',
  ];

  /**
   * The manager under test.
   */
  private FeatureFlagManagerInterface $manager;

  /**
   * The checker used to verify enabled state.
   */
  private FeatureFlagCheckerInterface $checker;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->manager = $this->container->get(FeatureFlagManagerInterface::class);
    $this->checker = $this->container->get(FeatureFlagCheckerInterface::class);
  }

  /**
   * Tests that definitions are discovered from the test module.
   */
  public function testGetDefinitions(): void {
    $definitions = $this->manager->getDefinitions();
    $this->assertArrayHasKey('example_feature', $definitions);
    $this->assertSame('social_core_feature_flags_test', $definitions['example_feature']->provider);
    $this->assertSame('Example feature', $definitions['example_feature']->label);
  }

  /**
   * Tests that validation passes for valid definitions.
   */
  public function testValidationPassesForValidDefinitions(): void {
    $this->assertSame([], $this->manager->getValidationErrors());
  }

  /**
   * Tests that stale state entries are pruned for removed flags.
   */
  public function testStaleStateIsPruned(): void {
    $state = $this->container->get('state');
    $state->set(FeatureFlagState::STATE_KEY, [
      'example_feature' => TRUE,
      'removed_flag' => TRUE,
    ]);

    $this->manager->getDefinitions();

    $enabled = $state->get(FeatureFlagState::STATE_KEY, []);
    $this->assertArrayHasKey('example_feature', $enabled);
    $this->assertArrayNotHasKey('removed_flag', $enabled);
  }

  /**
   * Tests that setEnabled ignores unknown flags.
   */
  public function testSetEnabledIgnoresUnknownFlags(): void {
    $this->manager->setEnabled('unknown_flag', TRUE);
    $this->assertFalse($this->checker->isEnabled('unknown_flag'));

    $enabled = $this->container->get('state')->get(FeatureFlagState::STATE_KEY, []);
    $this->assertIsArray($enabled);
    $this->assertArrayNotHasKey('unknown_flag', $enabled);
  }

}
