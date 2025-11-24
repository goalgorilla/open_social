<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\Hooks;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\hux\Attribute\Alter;
use Drupal\social_profile\GroupAffiliation as Affiliation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Group affiliation group type hooks.
 *
 * @internal
 */
class GroupAffiliation implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * GroupAffiliation constructor.
   *
   * @param \Drupal\social_profile\GroupAffiliation $groupAffiliation
   *   Group affiliation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected Affiliation $groupAffiliation,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('social_profile.group_affiliation'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Adds flexible group affiliation settings to the configuration form.
   *
   * This form adds a duplication of flexible group type settings exist on
   * "/admin/group/types/manage/flexible_group". This page is not accessible to
   * users without the "administer groups" permission.
   *
   * @param array $form
   *   The form array for the social group settings.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  #[Alter('form_social_group_form')]
  public function displayFlexibleGroupAffiliationSettings(array &$form, FormStateInterface $form_state): void {
    if (!$this->groupAffiliation->isAffiliationFeatureEnabled()) {
      return;
    }

    $candidates_enabled = $this->groupAffiliation->getAffiliationEnabledGroupTypes();

    $form['flexible_group_affiliation'] = [
      '#type' => 'details',
      '#title' => $this->t('Affiliation setting'),
      '#description' => $this->t('Allow members to select which group(s) they represent in your community. Users can configure this in their profile settings and the information is displayed in teasers across the platform next to the user name.'),
      '#weight' => 40,
      '#tree' => TRUE,
    ];

    $form['flexible_group_affiliation']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable affiliation for Groups'),
      '#default_value' => isset($candidates_enabled['flexible_group']),
    ];

    $form['#submit'][] = [$this, 'updateFlexibleGroupAffiliationStatusCallback'];
  }

  /**
   * Persists flexible group affiliation settings.
   *
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form, containing user input values.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateFlexibleGroupAffiliationStatusCallback(array &$form, FormStateInterface &$form_state): void {
    $status = (bool) $form_state->getValue(['flexible_group_affiliation', 'status']);

    $group_type = $this->entityTypeManager->getStorage('group_type')->load('flexible_group');
    assert($group_type instanceof GroupTypeInterface);

    $third_party_settings = $group_type->getThirdPartySettings('social_profile');
    $current_status = $third_party_settings[Affiliation::AFFILIATION_ENABLED_CONFIG_KEY] ?? FALSE;

    // Only update the setting if it has changed to avoid unnecessary
    // entity saves and cache invalidations.
    if ($status !== (bool) $current_status) {
      $group_type->setThirdPartySetting('social_profile', Affiliation::AFFILIATION_ENABLED_CONFIG_KEY, $status);
      $group_type->save();
    }
  }

}
