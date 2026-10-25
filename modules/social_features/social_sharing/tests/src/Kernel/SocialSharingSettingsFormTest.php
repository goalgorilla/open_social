<?php

declare(strict_types=1);

namespace Drupal\Tests\social_sharing\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\social_sharing\Form\SocialSharingSettingsForm;

/**
 * Tests the sharing settings form.
 *
 * @coversDefaultClass \Drupal\social_sharing\Form\SocialSharingSettingsForm
 * @group social_sharing
 */
class SocialSharingSettingsFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'shariff',
    'social_sharing',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['shariff', 'social_sharing']);
    $this->config('shariff.settings')
      ->set('shariff_services', ['facebook' => 'facebook', 'twitter' => 'twitter'])
      ->save();
  }

  /**
   * Submits the form with the given values.
   *
   * @param array<string, mixed> $values
   *   The submitted values.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The state of the submitted form.
   */
  private function submit(array $values): FormStateInterface {
    $form_state = (new FormState())->setValues($values);
    $this->container->get('form_builder')->submitForm(SocialSharingSettingsForm::class, $form_state);
    return $form_state;
  }

  /**
   * Tests that the enabled services are listed first and selected.
   *
   * @covers ::buildForm
   */
  public function testEnabledServicesAreListedFirst(): void {
    $form = $this->container->get('form_builder')->getForm(SocialSharingSettingsForm::class);

    $this->assertSame(['facebook', 'twitter', 'linkedin'], array_slice(array_keys($form['services']['#options']), 0, 3));
    $this->assertSame(['facebook', 'twitter'], $form['services']['#default_value']);
  }

  /**
   * Tests that the selected services are saved when sharing is enabled.
   *
   * @covers ::submitForm
   */
  public function testSelectedServicesAreSaved(): void {
    $form_state = $this->submit([
      'enabled' => 1,
      'services' => ['mail' => 'mail', 'linkedin' => 'linkedin'],
    ]);

    $this->assertSame([], $form_state->getErrors());
    $this->assertTrue($this->config('social_sharing.settings')->get('enabled'));
    $this->assertSame(['linkedin' => 'linkedin', 'mail' => 'mail'], $this->config('shariff.settings')->get('shariff_services'));
  }

  /**
   * Tests that the services are kept when sharing is disabled.
   *
   * @covers ::submitForm
   */
  public function testServicesAreKeptWhenDisabled(): void {
    // An unchecked checkbox is not submitted at all.
    $form_state = $this->submit([
      'enabled' => NULL,
      'services' => [],
    ]);

    $this->assertSame([], $form_state->getErrors());
    $this->assertFalse($this->config('social_sharing.settings')->get('enabled'));
    $this->assertSame(['facebook' => 'facebook', 'twitter' => 'twitter'], $this->config('shariff.settings')->get('shariff_services'));
  }

  /**
   * Tests that a service is required when sharing is enabled.
   *
   * @covers ::validateForm
   */
  public function testServiceIsRequiredWhenEnabled(): void {
    $form_state = $this->submit([
      'enabled' => 1,
      'services' => [],
    ]);

    $this->assertArrayHasKey('services', $form_state->getErrors());
    $this->assertSame(['facebook' => 'facebook', 'twitter' => 'twitter'], $this->config('shariff.settings')->get('shariff_services'));
  }

}
