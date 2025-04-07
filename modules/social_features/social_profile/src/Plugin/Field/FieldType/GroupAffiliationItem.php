<?php

namespace Drupal\social_profile\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_profile\GroupAffiliation;

/**
 * Defines the 'group_affiliation' field type.
 *
 * @FieldType(
 *   id = "group_affiliation",
 *   label = @Translation("Group affiliation"),
 *   description = @Translation("Group affiliation field."),
 *   category = @Translation("Reference"),
 *   default_widget = "group_affiliation_widget",
 *   default_formatter = "entity_reference_label",
 *   no_ui = "TRUE",
 * )
 *
 * @extends EntityReferenceItem<\Drupal\group\Entity\GroupInterface>
 */
class GroupAffiliationItem extends EntityReferenceItem {

  /**
   * Gets the group affiliation service.
   *
   * Since field types don't support Dependency Injection, we use
   * \Drupal::service(). See https://www.drupal.org/node/2053415.
   *
   * @return \Drupal\social_profile\GroupAffiliation
   *   The group affiliation service.
   */
  protected static function getGroupAffiliation(): GroupAffiliation {
    return \Drupal::service('social_profile.group_affiliation');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings(): array {
    $settings = parent::defaultStorageSettings();

    // Automatically set the target entity type.
    $settings['target_type'] = 'group';
    // Automatically set target_bundles.
    $settings['handler_settings']['target_bundles'] = self::getAllowedGroupTypes();

    return $settings;
  }

  /**
   * {@inheritDoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {

    // Disable storage settings form and allow only Group as target type.
    $element = parent::storageSettingsForm($form, $form_state, $has_data);
    $element['target_type']['#options'] = [
      'group' => $this->t('Group'),
    ];
    $element['target_type']['#disabled'] = TRUE;

    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::fieldSettingsForm($form, $form_state,);

    // Disable UI form elements that should be managed programmatically via
    // group type affiliation configuration.
    $form['handler']['handler']['#disabled'] = TRUE;
    $form['handler']['handler_settings']['target_bundles']['#disabled'] = TRUE;
    $form['handler']['handler_settings']['target_bundles']['#options'] = self::getAllowedGroupTypes();

    return $form;
  }

  /**
   * Returns affiliation enabled group types.
   *
   * @return array<string, string>
   *   Associative array of affiliation enabled group types.
   */
  private static function getAllowedGroupTypes(): array {
    $affiliation_enabled_group_types = self::getGroupAffiliation()->getAffiliationEnabledGroupTypes();
    // Convert to target_bundles array.
    return array_combine(array_keys($affiliation_enabled_group_types), array_keys($affiliation_enabled_group_types));
  }

}
