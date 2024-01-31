<?php

namespace Drupal\grequest\Plugin\Group\Relation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationBase;

/**
 * Provides a group relation type for users.
 *
 * @GroupRelationType(
 *   id = "group_membership_request",
 *   label = @Translation("Group user"),
 *   description = @Translation("Adds users to groups without making them members."),
 *   entity_type_id = "user",
 *   pretty_path_key = "user",
 *   reference_label = @Translation("Username"),
 *   reference_description = @Translation("The name of the user you want to add to the group"),
 *   admin_permission = "administer user_as_content"
 * )
 */
class GroupMembershipRequest extends GroupRelationBase {

  /**
   * Invitation created and waiting for user's response.
   */
  const REQUEST_PENDING = 0;

  /**
   * Invitation accepted by user.
   */
  const REQUEST_ACCEPTED = 1;

  /**
   * Invitation rejected by user.
   */
  const REQUEST_REJECTED = 2;

  /**
   * {@inheritdoc}
   */
  public function getEntityReferenceSettings() {
    $settings = parent::getEntityReferenceSettings();
    $settings['handler_settings']['include_anonymous'] = FALSE;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['entity_cardinality'] = 1;

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other content enabler plugins.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

    return $form;
  }

}
