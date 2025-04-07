<?php

namespace Drupal\social_profile\Hooks;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\hux\Attribute\Alter;
use Drupal\social_profile\GroupAffiliation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Group affiliation group type hooks.
 *
 * @internal
 */
class SocialProfileFormAlterHooks implements ContainerInjectionInterface {

  /**
   * GroupAffiliationGroupTypeHooks constructor.
   *
   * @param \Drupal\social_profile\GroupAffiliation $groupAffiliation
   *   Group affiliation service.
   */
  public function __construct(
    protected GroupAffiliation $groupAffiliation,
  ) {}

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('social_profile.group_affiliation'),
    );
  }

  /**
   * Alter user profile form based on affiliation settings.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   Unique form identifier.
   *
   * @return void
   *   Return void.
   */
  #[
    Alter('form_profile_profile_add_form'),
    Alter('form_profile_profile_edit_form')
  ]
  public function formUserProfileAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Do not display "Affiliation representation" section if affiliation
    // feature is not enabled.
    if (!$this->groupAffiliation->isAffiliationFeatureEnabled()) {
      unset($form['#fieldgroups']['group_affiliation_representation']);
      unset($form['field_enable_other_affiliations']);
      unset($form['field_other_affiliations']);

      // Early return.
      // No need to set conditional state on field that is removed.
      return;

    }
    // Hide any organization fields if affiliation feature is enabled, because
    // affiliation feature is resolving the same business logic and both
    // features can not co-exist.
    // Fields to remove from UI:
    // 1. field_profile_organization
    // 2. field_profile_function
    // 3. field_profile_organization_tag
    // 4. field_profile_org_details.
    else {
      // 1. & 2. Remove "Organization" and "Function" fields from UI.
      unset($form['field_profile_organization']);
      unset($form['field_profile_function']);

      // 3. Remove "Organization tag" (field_profile_organization_tag) field.
      // Module: social_profile_organization_tag
      unset($form['field_profile_organization_tag']);

      // 4. Remove "Organizations" (field_profile_org_details) field.
      // Module: social_organization
      unset($form['field_profile_org_details']);
    }

    // Conditional state: Show other affiliations (paragraph field) if other
    // affiliation (checkbox) is ticked.
    if (isset($form['field_other_affiliations']) && isset($form['field_enable_other_affiliations'])) {
      $form['field_other_affiliations']['#states'] = [
        'visible' => [
          ':input[name="field_enable_other_affiliations[value]"]' => ['checked' => TRUE],
        ],
      ];
    }
  }

}
