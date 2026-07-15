<?php

declare(strict_types=1);

namespace Drupal\Tests\social_core\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\social_core\Form\FeatureFlagManagementForm;
use Drupal\social_core\Service\FeatureFlagCheckerInterface;

/**
 * Tests the feature flag management form.
 *
 * @group social_core
 */
class FeatureFlagManagementFormTest extends KernelTestBase {

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
   * The checker used to verify enabled state.
   */
  private FeatureFlagCheckerInterface $checker;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->checker = $this->container->get(FeatureFlagCheckerInterface::class);
  }

  /**
   * Tests that the form lists discovered feature flags.
   */
  public function testBuildFormListsDiscoveredFlags(): void {
    $form_object = FeatureFlagManagementForm::create($this->container);
    $form = $form_object->buildForm([], new FormState());

    $this->assertArrayHasKey('example_feature', $form['flags']);
    $this->assertSame('Example feature', $form['flags']['example_feature']['label']['#plain_text']);
  }

  /**
   * Tests that submit persists enabled state via the manager.
   */
  public function testSubmitPersistsEnabledState(): void {
    $form_object = FeatureFlagManagementForm::create($this->container);
    $form_state = (new FormState())->setValues([
      'flags' => [
        'example_feature' => [
          'enabled' => 1,
        ],
      ],
    ]);

    $form = [];
    $form_object->submitForm($form, $form_state);

    $this->assertTrue($this->checker->isEnabled('example_feature'));
  }

}
