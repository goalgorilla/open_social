<?php

namespace Drupal\Tests\social_core\Config;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\KernelTests\KernelTestBase;

/**
 * Confirm that language overrides work.
 *
 * @group social_core
 */
class ConfigLanguageManagerTest extends KernelTestBase {

  /**
   * The service under test.
   *
   * @var \Drupal\social_core\Service\ConfigLanguageManager
   */
  protected $configLanguageManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'user',
    'language',
    'social_core',
    'social_core_test',
    'system',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['social_core_test']);
    $this->configLanguageManager = \Drupal::service('social_core.config_language_manager');
  }

  /**
   * Tests locale override based on language.
   *
   * Tests override with use of configOverrideLanguageStart() and
   * configOverrideLanguageEnd().
   *
   * Test scenario:
   * - set configuration override language to English.
   * - start override in French language with configOverrideLanguageStart().
   * - stop override with configOverrideLanguageEnd().
   * - confirm that configuration override is reverted to 'en' again.
   */
  public function testConfigLanguageOverride(): void {
    // The language module implements a config factory override object that
    // overrides configuration when the Language module is enabled. This test
    // ensures that English overrides work.
    \Drupal::languageManager()->setConfigOverrideLanguage(\Drupal::languageManager()->getLanguage('en'));
    $config = \Drupal::config('social_core_test.system');
    $this->assertSame('en bar', $config->get('foo'));

    // Ensure that the raw data is not translated.
    $raw = $config->getRawData();
    $this->assertSame('bar', $raw['foo']);

    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Ensure that French overrides work.
    $this->configLanguageManager->configOverrideLanguageStart('fr');
    $config = \Drupal::config('social_core_test.system');
    $this->assertSame('fr bar', $config->get('foo'));
    $this->configLanguageManager->configOverrideLanguageEnd();

    // Ensure that French override is reverted to English.
    $config = \Drupal::config('social_core_test.system');
    $this->assertSame('en bar', $config->get('foo'));
  }

}
