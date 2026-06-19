<?php

declare(strict_types=1);

namespace Drupal\social_analytics\Hooks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hux\Attribute\Alter;
use Drupal\social_analytics\IndividualMetricsEdaHandler;
use Drupal\social_analytics\IndividualMetricsPreferenceService;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the individual metrics preference toggle to the user account form.
 *
 * @internal
 */
final class IndividualMetricsUserFormAlter implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Form value key for the checkbox inside the Privacy settings fieldset.
   */
  private const FORM_VALUE_KEY = 'show_in_individual_metrics';

  /**
   * Weight places the analytics block after all other Privacy settings fields.
   */
  private const DESCRIPTION_WEIGHT = 100;

  private const CHECKBOX_WEIGHT = 101;

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly IndividualMetricsPreferenceService $preferenceService,
    private readonly IndividualMetricsEdaHandler $edaHandler,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get(ConfigFactoryInterface::class),
      $container->get(IndividualMetricsPreferenceService::class),
      $container->get(IndividualMetricsEdaHandler::class),
    );
  }

  /**
   * Alters the user account settings form.
   */
  #[Alter('form_user_form')]
  public function alterUserForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->configFactory->get('social_analytics.settings');
    if (!$config->get('individual_metrics_preference_visibility_enabled')) {
      return;
    }

    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof EntityFormInterface) {
      return;
    }

    /** @var \Drupal\user\UserInterface $account */
    $account = $form_object->getEntity();
    if ($account->isNew()) {
      return;
    }

    // Nest inside Privacy settings (added by social_profile on this form).
    if (!isset($form['profile_privacy'])) {
      return;
    }

    $uid = (int) $account->id();
    $form['profile_privacy']['individual_metrics_description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Manage how your information appears in analytics and reports on this platform.'),
      '#weight' => self::DESCRIPTION_WEIGHT,
    ];
    $form['profile_privacy'][self::FORM_VALUE_KEY] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show my name in analytics reports and rankings'),
      '#default_value' => $this->preferenceService->getEffectiveShowInIndividualMetrics($uid),
      '#weight' => self::CHECKBOX_WEIGHT,
      '#attributes' => [
        'data-switch' => TRUE,
      ],
    ];

    if (isset($form['actions']['submit'])) {
      $form['actions']['submit']['#submit'][] = [$this, 'submitUserForm'];
    }
  }

  /**
   * Persists the individual metrics preference when it changed on submit.
   */
  public function submitUserForm(array $form, FormStateInterface $form_state): void {
    $uid = $this->resolveUid($form_state);
    if ($uid <= 0) {
      return;
    }

    $values = $form_state->getValue('profile_privacy');
    if (!is_array($values) || !array_key_exists(self::FORM_VALUE_KEY, $values)) {
      return;
    }

    $submitted = (bool) $values[self::FORM_VALUE_KEY];
    $previousEffective = $this->preferenceService->getEffectiveShowInIndividualMetrics($uid);
    if ($submitted === $previousEffective) {
      return;
    }

    $this->preferenceService->setShowInIndividualMetrics($uid, $submitted);

    $form_object = $form_state->getFormObject();
    if ($form_object instanceof EntityFormInterface) {
      $account = $form_object->getEntity();
      if ($account instanceof UserInterface) {
        $this->edaHandler->dispatchPreferenceChange($account, $submitted);
      }
    }
  }

  /**
   * Resolves the target user ID for own-account and SM edit flows.
   */
  private function resolveUid(FormStateInterface $form_state): int {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof EntityFormInterface) {
      $account = $form_object->getEntity();
      if ($account instanceof UserInterface) {
        return (int) $account->id();
      }
    }

    $uid = $form_state->getValue('uid');
    if ($uid !== NULL && $uid !== '') {
      return (int) $uid;
    }

    return 0;
  }

}
