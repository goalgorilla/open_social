<?php

declare(strict_types=1);

namespace Drupal\Tests\social_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\social_core\Service\FeatureFlagCheckerInterface;
use Drupal\social_core\Service\FeatureFlagManagerInterface;

/**
 * Tests the feature flag checker service.
 *
 * @group social_core
 */
class FeatureFlagCheckerTest extends KernelTestBase {

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
   * The checker under test.
   */
  private FeatureFlagCheckerInterface $checker;

  /**
   * The manager used to toggle flags.
   */
  private FeatureFlagManagerInterface $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->checker = $this->container->get(FeatureFlagCheckerInterface::class);
    $this->manager = $this->container->get(FeatureFlagManagerInterface::class);
  }

  /**
   * Tests that undefined flags are disabled by default.
   */
  public function testUndefinedFlagIsDisabled(): void {
    $this->assertFalse($this->checker->isEnabled('nonexistent_flag'));
  }

  /**
   * Tests that defined flags are disabled until explicitly enabled.
   */
  public function testDefinedFlagDisabledByDefault(): void {
    $this->assertFalse($this->checker->isEnabled('example_feature'));
  }

  /**
   * Tests enabling and disabling a feature flag.
   */
  public function testEnableAndDisableFlag(): void {
    $this->manager->setEnabled('example_feature', TRUE);
    $this->assertTrue($this->checker->isEnabled('example_feature'));

    $this->manager->setEnabled('example_feature', FALSE);
    $this->assertFalse($this->checker->isEnabled('example_feature'));
  }

  /**
   * Tests cache tag format.
   */
  public function testGetCacheTags(): void {
    $this->assertSame(['feature_flag:example_feature'], $this->checker->getCacheTags('example_feature'));
  }

}
