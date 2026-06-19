<?php

declare(strict_types=1);

namespace Drupal\Tests\social_analytics\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\social_analytics\Hooks\IndividualMetricsUserFormAlter;
use Drupal\social_analytics\IndividualMetricsPreferenceService;
use Drupal\user\Entity\User;
use Drupal\user\ProfileForm;
use Drupal\user\UserInterface;

/**
 * Tests individual metrics user preference storage and form integration.
 *
 * @group social_analytics
 * @coversDefaultClass \Drupal\social_analytics\IndividualMetricsPreferenceService
 */
final class IndividualMetricsPreferenceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'hux',
    'social_analytics',
  ];

  /**
   * The preference service under test.
   */
  private IndividualMetricsPreferenceService $preferenceService;

  /**
   * The user form alter hook under test.
   */
  private IndividualMetricsUserFormAlter $formAlter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['social_analytics']);

    $this->preferenceService = $this->container->get('social_analytics.individual_metrics_preference');
    $this->formAlter = IndividualMetricsUserFormAlter::create($this->container);
  }

  /**
   * @covers ::getEffectiveShowInIndividualMetrics
   */
  public function testEffectiveValueUsesPlatformDefaultWhenNoStoredPreference(): void {
    $user = $this->createUser();

    $this->setPlatformSettings(TRUE, FALSE);
    self::assertFalse($this->preferenceService->getEffectiveShowInIndividualMetrics((int) $user->id()));

    $this->setPlatformSettings(TRUE, TRUE);
    self::assertTrue($this->preferenceService->getEffectiveShowInIndividualMetrics((int) $user->id()));
  }

  /**
   * @covers ::getStoredShowInIndividualMetrics
   * @covers ::setShowInIndividualMetrics
   */
  public function testStoredPreferenceOverridesPlatformDefault(): void {
    $user = $this->createUser();
    $uid = (int) $user->id();

    $this->setPlatformSettings(TRUE, TRUE);
    $this->preferenceService->setShowInIndividualMetrics($uid, FALSE);

    self::assertTrue($this->preferenceService->hasStoredPreference($uid));
    self::assertFalse($this->preferenceService->getStoredShowInIndividualMetrics($uid));
    self::assertFalse($this->preferenceService->getEffectiveShowInIndividualMetrics($uid));
  }

  /**
   * Stored preferences must not change when platform settings are updated.
   */
  public function testPlatformConfigChangeDoesNotMutateStoredPreference(): void {
    $user = $this->createUser();
    $uid = (int) $user->id();

    $this->setPlatformSettings(TRUE, FALSE);
    $this->preferenceService->setShowInIndividualMetrics($uid, TRUE);

    $this->setPlatformSettings(TRUE, TRUE);

    self::assertTrue($this->preferenceService->getStoredShowInIndividualMetrics($uid));
    self::assertTrue($this->preferenceService->getEffectiveShowInIndividualMetrics($uid));
  }

  /**
   * Toggle is absent when the visibility platform setting is disabled.
   */
  public function testToggleHiddenWhenVisibilityDisabled(): void {
    $user = $this->createUser();
    $this->setPlatformSettings(FALSE, FALSE);

    $form = $this->buildUserForm($user);
    $this->formAlter->alterUserForm($form, $this->createFormState($user));

    self::assertArrayNotHasKey('show_in_individual_metrics', $form['profile_privacy']);
  }

  /**
   * Toggle is absent when Privacy settings fieldset is not on the form.
   */
  public function testToggleHiddenWhenPrivacyFieldsetMissing(): void {
    $user = $this->createUser();
    $this->setPlatformSettings(TRUE, FALSE);

    $form = [
      'actions' => [
        'submit' => [
          '#submit' => [],
        ],
      ],
    ];
    $this->formAlter->alterUserForm($form, $this->createFormState($user));

    self::assertArrayNotHasKey('profile_privacy', $form);
  }

  /**
   * Toggle is present when visibility is enabled.
   */
  public function testToggleVisibleWhenVisibilityEnabled(): void {
    $user = $this->createUser();
    $this->setPlatformSettings(TRUE, FALSE);

    $form = $this->buildUserForm($user);
    $this->formAlter->alterUserForm($form, $this->createFormState($user));

    self::assertArrayHasKey('show_in_individual_metrics', $form['profile_privacy']);
    self::assertArrayHasKey('individual_metrics_description', $form['profile_privacy']);
    self::assertFalse($form['profile_privacy']['show_in_individual_metrics']['#default_value']);
    self::assertGreaterThan(
      $form['profile_privacy']['individual_metrics_description']['#weight'],
      $form['profile_privacy']['show_in_individual_metrics']['#weight'],
    );
  }

  /**
   * Checkbox default reflects platform default without a stored preference.
   */
  public function testCheckboxDefaultReflectsPlatformDefault(): void {
    $user = $this->createUser();
    $this->setPlatformSettings(TRUE, TRUE);

    $form = $this->buildUserForm($user);
    $this->formAlter->alterUserForm($form, $this->createFormState($user));

    self::assertTrue($form['profile_privacy']['show_in_individual_metrics']['#default_value']);
  }

  /**
   * Form submit persists an explicit preference for the edited user.
   */
  public function testFormSubmitPersistsPreferenceForEditedUser(): void {
    $user = $this->createUser();
    $uid = (int) $user->id();
    $this->setPlatformSettings(TRUE, FALSE);

    $form = $this->buildUserForm($user);
    $form_state = $this->createFormState($user);
    $this->formAlter->alterUserForm($form, $form_state);

    $form_state->setValue('profile_privacy', [
      'show_in_individual_metrics' => 1,
    ]);
    $this->formAlter->submitUserForm($form, $form_state);

    self::assertTrue($this->preferenceService->getStoredShowInIndividualMetrics($uid));
  }

  /**
   * Unchanged effective value on submit does not create a stored preference.
   */
  public function testFormSubmitSkipsWriteWhenEffectiveValueUnchanged(): void {
    $user = $this->createUser();
    $uid = (int) $user->id();
    $this->setPlatformSettings(TRUE, FALSE);

    $form = $this->buildUserForm($user);
    $form_state = $this->createFormState($user);
    $this->formAlter->alterUserForm($form, $form_state);

    $form_state->setValue('profile_privacy', [
      'show_in_individual_metrics' => 0,
    ]);
    $this->formAlter->submitUserForm($form, $form_state);

    self::assertFalse($this->preferenceService->hasStoredPreference($uid));
  }

  /**
   * Site manager edits save the preference on the target user account.
   */
  public function testSiteManagerEditSavesPreferenceForTargetUser(): void {
    $site_manager = $this->createUser(['uid' => 2]);
    $target_user = $this->createUser(['uid' => 3]);
    $target_uid = (int) $target_user->id();
    $this->setPlatformSettings(TRUE, FALSE);

    $form = $this->buildUserForm($target_user);
    $form_state = $this->createFormState($target_user);
    $form_state->setValue('uid', $target_uid);
    $this->formAlter->alterUserForm($form, $form_state);

    $form_state->setValue('profile_privacy', [
      'show_in_individual_metrics' => 1,
    ]);
    $this->formAlter->submitUserForm($form, $form_state);

    self::assertTrue($this->preferenceService->getStoredShowInIndividualMetrics($target_uid));
    self::assertFalse($this->preferenceService->hasStoredPreference((int) $site_manager->id()));
  }

  /**
   * Creates and saves a user account for testing.
   *
   * @param array<string, mixed> $values
   *   Optional entity values.
   */
  private function createUser(array $values = []): UserInterface {
    $user = User::create($values + [
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
      'status' => 1,
    ]);
    $user->save();

    return $user;
  }

  /**
   * Updates individual metrics platform settings.
   */
  private function setPlatformSettings(bool $visibility_enabled, bool $show_by_default): void {
    $this->config('social_analytics.settings')
      ->set('individual_metrics_preference_visibility_enabled', $visibility_enabled)
      ->set('individual_metrics_show_by_default', $show_by_default)
      ->save();
  }

  /**
   * Builds a minimal user form scaffold for alter/submit tests.
   *
   * @return array<string, mixed>
   *   Form array.
   */
  private function buildUserForm(UserInterface $user): array {
    return [
      'profile_privacy' => [
        '#type' => 'fieldset',
        '#tree' => TRUE,
      ],
      'actions' => [
        'submit' => [
          '#submit' => [],
        ],
      ],
      '#form_id' => 'user_form',
      '#entity' => $user,
    ];
  }

  /**
   * Creates a form state wired to a user form object.
   */
  private function createFormState(UserInterface $user): FormState {
    $form_object = ProfileForm::create($this->container);
    $form_object->setEntity($user);

    return (new FormState())->setFormObject($form_object);
  }

}
