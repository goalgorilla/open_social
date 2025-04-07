<?php

namespace Drupal\social_profile\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupType;
use Drupal\hux\Attribute\Alter;
use Drupal\hux\Attribute\Hook;
use Drupal\social_profile\GroupAffiliation;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Group affiliation group type hooks.
 *
 * @internal
 */
class GroupAffiliationGroupTypeHooks implements ContainerInjectionInterface {

  /**
   * GroupAffiliationGroupTypeHooks constructor.
   *
   * @param \Drupal\social_profile\GroupAffiliation $groupAffiliation
   *   Group affiliation service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
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
    $third_party_settings[GroupAffiliation::AFFILIATION_ENABLED_CONFIG_KEY] = $request->request->get(GroupAffiliation::AFFILIATION_ENABLED_CONFIG_KEY, FALSE);
    $entity->setThirdPartySetting('social_profile', GroupAffiliation::AFFILIATION_ENABLED_CONFIG_KEY, $third_party_settings[GroupAffiliation::AFFILIATION_ENABLED_CONFIG_KEY]);
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
        '#title' => t('Enable/Disable affiliation for this group type'),
        '#default_value' => $default_value,
      ];
    }
  }

}
