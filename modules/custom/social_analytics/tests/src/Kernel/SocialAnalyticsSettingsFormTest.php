<?php

declare(strict_types=1);

namespace Drupal\Tests\social_analytics\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\social_analytics\Form\SocialAnalyticsSettingsForm;

/**
 * Tests individual metrics platform settings for site managers.
 *
 * @group social_analytics
 * @coversDefaultClass \Drupal\social_analytics\Form\SocialAnalyticsSettingsForm
 */
final class SocialAnalyticsSettingsFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'social_analytics',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['social_analytics']);
  }

  /**
   * @covers ::buildForm
   */
  public function testDefaultPlatformSettingsAreDisabled(): void {
    $config = $this->config('social_analytics.settings');
    self::assertFalse($config->get('individual_metrics_preference_visibility_enabled'));
    self::assertFalse($config->get('individual_metrics_show_by_default'));
  }

  /**
   * @covers ::submitForm
   */
  public function testSettingsFormPersistsPlatformSettings(): void {
    $form_state = (new FormState())->setValues([
      'individual_metrics_preference_visibility_enabled' => TRUE,
      'individual_metrics_show_by_default' => TRUE,
    ]);

    $form = [];
    $form_object = SocialAnalyticsSettingsForm::create($this->container);
    $form_object->submitForm($form, $form_state);

    $config = $this->config('social_analytics.settings');
    self::assertTrue($config->get('individual_metrics_preference_visibility_enabled'));
    self::assertTrue($config->get('individual_metrics_show_by_default'));
  }

}
