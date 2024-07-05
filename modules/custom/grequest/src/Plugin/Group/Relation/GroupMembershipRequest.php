<?php

namespace Drupal\grequest\Plugin\Group\Relation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a group relation for users as group membership request.
 *
 * @GroupRelationType(
 *   id = "group_membership_request",
 *   label = @Translation("Group membership request"),
 *   description = @Translation("Adds users as requesters for the group."),
 *   entity_type_id = "user",
 *   pretty_path_key = "request",
 *   reference_label = @Translation("Username"),
 *   reference_description = @Translation("The name of the user you want to make a member"),
 *   admin_permission = "administer membership requests"
 * )
 */
class GroupMembershipRequest extends GroupRelationBase {

  /**
   * Transition id for approval.
   */
  const TRANSITION_APPROVE = 'approve';

  /**
   * Transition id for approval.
   */
  const TRANSITION_REJECT = 'reject';

  /**
   * Transition id for creation.
   */
  const TRANSITION_CREATE = 'create';

  /**
   * Status field.
   */
  const STATUS_FIELD = 'grequest_status';

  /**
   * Request created by default with new status.
   */
  const REQUEST_NEW = 'new';

  /**
   * Request created and waiting for administrator's response.
   */
  const REQUEST_PENDING = 'pending';

  /**
   * Request is approved by administrator.
   */
  const REQUEST_APPROVED = 'approved';

  /**
   * Request is rejected by administrator.
   */
  const REQUEST_REJECTED = 'rejected';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;
    $config['remove_group_membership_request'] = FALSE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['remove_group_membership_request'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove a group membership request, when user join the group.'),
      '#default_value' => $this->getConfiguration()['remove_group_membership_request'] ?? FALSE,
    ];

    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other group relations.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

    return $form;
  }

}
