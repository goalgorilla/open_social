<?php

declare(strict_types=1);

namespace Drupal\Tests\social_core\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Drupal\social_core\Service\FeatureFlagManagerInterface;

/**
 * Tests feature flag discovery and caching.
 *
 * @group social_core
 */
class FeatureFlagDiscoveryTest extends KernelTestBase {

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
   * The manager used to trigger discovery.
   */
  private FeatureFlagManagerInterface $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->manager = $this->container->get(FeatureFlagManagerInterface::class);
  }

  /**
   * Tests that definitions are discovered and cached.
   */
  public function testDefinitionsAreCachedByDeploymentIdentifier(): void {
    $definitions = $this->manager->getDefinitions();
    $this->assertArrayHasKey('example_feature', $definitions);

    $cache = $this->container->get('cache.default');
    $deployment_identifier = (string) Settings::get('deployment_identifier', '');
    $cache_id = 'social_core.feature_flags.definitions:' . $deployment_identifier;
    $this->assertNotFalse($cache->get($cache_id));
  }

}
