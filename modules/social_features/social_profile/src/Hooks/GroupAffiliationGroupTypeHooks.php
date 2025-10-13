<?php

namespace Drupal\social_profile\Hooks;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupType;
use Drupal\hux\Attribute\Alter;
use Drupal\hux\Attribute\Hook;
use Drupal\social_profile\Entity\ProfileAffiliationInterface;
use Drupal\social_profile\Form\SocialProfileSettingsForm;
use Drupal\social_profile\GroupAffiliation;
use Drupal\profile\Entity\ProfileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Group affiliation group type hooks.
 *
 * @internal
 */
class GroupAffiliationGroupTypeHooks implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * GroupAffiliationGroupTypeHooks constructor.
   *
   * @param \Drupal\social_profile\GroupAffiliation $groupAffiliation
   *   Group affiliation service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   */
  public function __construct(
    protected GroupAffiliation $groupAffiliation,
    protected RequestStack $requestStack,
  ) {}

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('social_profile.group_affiliation'),
      $container->get('request_stack'),
    );
  }

  /**
   * Provides extra fields for profile entities.
   *
   * This hook defines additional fields to be displayed for specific
   * entity types and view modes.
   * It configures the label, weight, and other properties for the custom
   * field added.
   *
   * @return array
   *   An associative array containing extra field information. The array
   *   keys represent the entity type, and the nested structure specifies
   *   bundles, view modes, and field settings.
   */
  #[Hook('entity_extra_field_info')]
  public function profileEntityExtraFieldInfo(): array {
    $extra['profile']['profile']['display']['primary_affiliation_name'] = [
      // @todo Probably, we should use another label for this extra field.
      'label' => $this->t('Primary Affiliation Name'),
      // By default, please it at the bottom of the profile view mode.
      'weight' => 100,
      // Hidden by default.
      'visible' => FALSE,
    ];

    $extra['profile']['profile']['display']['primary_affiliation_function'] = [
      'label' => $this->t('Primary Affiliation Function'),
      // By default, please it at the bottom of the profile view mode.
      'weight' => 100,
      // Hidden by default.
      'visible' => FALSE,
    ];

    return $extra;
  }

  /**
   * Build the extra field value for the default profile view mode.
   *
   * This method modifies the profile view to display affiliation function
   * information based on group membership, other affiliations, or profile
   * field values.
   *
   * @param array $build
   *   The profile view renders an array.
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile entity.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The view display.
   * @param string $view_mode
   *   The view mode.
   *
   * @see hook_ENTITY_TYPE_view()
   */
  #[Hook(hook: 'profile_view', priority: 100)]
  public function profileView(array &$build, ProfileInterface $profile, EntityViewDisplayInterface $display, string $view_mode): void {
    $build['#cache']['tags'] = Cache::mergeTags(
      $build['#cache']['tags'] ?? [],
      ['config:' . SocialProfileSettingsForm::SETTINGS]
    );

    if (!$profile instanceof ProfileAffiliationInterface) {
      return;
    }

    // @todo Replace "field_profile_organization" with
    //   "primary_affiliation_name" after complete migrating
    //   to affiliation feature.
    //   @see https://getopensocial.atlassian.net/browse/PROD-33090
    if ($display->getComponent('field_profile_organization')) {
      $build['field_profile_organization'] = [
        '#type' => 'markup',
        '#markup' => trim(strip_tags($profile->getPrimaryAffiliationName())),
      ];
    }

    // @todo Replace "field_profile_function" with "primary_affiliation_name"
    //   after complete migrating to affiliation feature.
    //   @see https://getopensocial.atlassian.net/browse/PROD-33090
    if ($display->getComponent('field_profile_function')) {
      $build['field_profile_function'] = [
        '#type' => 'markup',
        '#markup' => trim(strip_tags($profile->getPrimaryAffiliationFunction())),
      ];
    }
  }

  /**
   * Save affiliation settings to group type.
   *
   * @param \Drupal\group\Entity\GroupType $entity
   *   Group type entity.
   *
   * @return void
   *   Return void.
   */
  #[Hook('group_type_presave')]
  public function saveGroupTypeAffiliationSettings(GroupType $entity): void {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request instanceof Request) {
      return;
    }

    $third_party_settings = $entity->getThirdPartySettings('social_profile');
    if ($value = $request->request->get(GroupAffiliation::AFFILIATION_ENABLED_CONFIG_KEY, FALSE)) {
      $third_party_settings[GroupAffiliation::AFFILIATION_ENABLED_CONFIG_KEY] = $value;
      $entity->setThirdPartySetting('social_profile', GroupAffiliation::AFFILIATION_ENABLED_CONFIG_KEY, $third_party_settings[GroupAffiliation::AFFILIATION_ENABLED_CONFIG_KEY]);
    }
  }

  /**
   * Add affiliation settings to group type form.
   *
   * Why affiliation settings are not needed when new group type is created?
   *  "affiliation_candidate" configuration is defined programmatically via
   *   config and config does not exist when add form is rendered.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param string $form_id
   *   Form id.
   *
   * @return void
   *   Return void.
   */
  #[Alter('form_group_type_edit_form')]
  public function formGroupTypeAffiliationSettings(array &$form, FormStateInterface $form_state, string $form_id): void {
    if (!$this->groupAffiliation->isAffiliationFeatureEnabled()) {

      // Early return. Do not render affiliation setting on group type
      // if affiliation feature is disabled in profile settings.
      return;
    }

    /** @var \Drupal\group\Entity\Form\GroupTypeForm $group_type_form */
    $group_type_form = $form_state->getFormObject();
    /** @var \Drupal\group\Entity\GroupType $group_type */
    $group_type = $group_type_form->getEntity();
    $is_affiliation_candidate = $group_type->getThirdPartySetting('social_profile', GroupAffiliation::AFFILIATION_CANDIDATE_CONFIG_KEY, FALSE);

    if ($is_affiliation_candidate) {
      $default_value = $group_type->getThirdPartySetting('social_profile', GroupAffiliation::AFFILIATION_ENABLED_CONFIG_KEY, FALSE);

      $form['third_party_settings']['social_profile_affiliation'] = [
        "#type" => "details",
        "#title" => "Affiliation",
        "#description" => "Allow members to determine which organization they represent in your community. This can be made through the profile settings. This group type is eligible for user affiliation.",
        "#open" => FALSE,
      ];

      $form['third_party_settings']['social_profile_affiliation'][GroupAffiliation::AFFILIATION_ENABLED_CONFIG_KEY] = [
        '#type' => 'checkbox',
        '#title' => t('Enable affiliation for this group type'),
        '#default_value' => $default_value,
      ];
    }
  }

}
