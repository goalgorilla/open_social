<?php

namespace Drupal\social_profile\Hooks;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hux\Attribute\Alter;
use Drupal\social_profile\Event\SocialProfilePrePresaveSubmitEvent;
use Drupal\social_profile\GroupAffiliation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Group affiliation group type hooks.
 *
 * @internal
 */
class SocialProfileFormAlterHooks implements ContainerInjectionInterface {

  use StringTranslationTrait;

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
   * Add additional submit handler before profile presave.
   *
   * This method alters the profile add and edit forms to ensure that the
   * custom submit handler `profilePrePresaveSubmit` is executed before
   * the form’s default submit handlers, allowing for early processing
   * or modification of the profile entity before it is saved.
   *
   * For example, this is used to transfer data from the profile form to the
   * profile entity’s presave hook (via properties in the profile bundle class)
   * because form states for inline entity forms related to group membership
   * entities are not accessible on profile entity presave.
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
    Alter('form_profile_profile_edit_form'),
  ]
  public function formUserProfilePrependPrePresaveSubmitHandler(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Prepend your custom submit handler.
    array_unshift($form['actions']['submit']['#submit'], [static::class, 'profilePrePresaveSubmit']);
  }

  /**
   * Custom submit handler that dispatches an event before the profile is saved.
   *
   * This allows other components to respond to user-submitted changes
   * before the profile entity is persisted.
   *
   * Note that inline entity form handlers are always invoked prior to this
   * event, meaning any related entities managed via inline entity forms have
   * already been updated by the time this event runs.
   *
   * @param array $form
   *   The form structure array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object containing user input and context.
   *
   * @return void
   *   Return void.
   *
   * @see \Drupal\Core\Render\ElementSubmit
   *   Explanation on why inline entity form handlers run before this event.
   */
  public static function profilePrePresaveSubmit(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\profile\Form\ProfileForm $profile_form */
    $profile_form = $form_state->getFormObject();
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $profile_form->getEntity();

    // Dispatch event BEFORE entity is saved.
    \Drupal::service('event_dispatcher')->dispatch(
      new SocialProfilePrePresaveSubmitEvent($profile, $form, $form_state),
      SocialProfilePrePresaveSubmitEvent::EVENT_NAME
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
      $form['#attached']['library'][] = 'social_profile/affiliation';
    }
  }

  /**
  * Alter user profile form based on affiliation settings.
  */
  #[Alter('field_group_form_process')]
  public function socialProfileFieldGroupFormProcessAlter(array &$element, object &$group, array &$complete_form): void {
    if (isset($group->group_name) && $group->group_name === 'group_affiliation_representation') {
      $element['#attributes']['data-primary-text'][] = $this->t('Primary');
      $element['#attributes']['data-secondary-text'][] = $this->t('Drag and drop items');
    }
  }

}
