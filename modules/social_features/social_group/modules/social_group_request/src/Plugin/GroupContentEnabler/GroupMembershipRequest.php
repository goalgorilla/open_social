<?php

namespace Drupal\social_group_request\Plugin\GroupContentEnabler;

use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Url;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a content enabler for users.
 *
 * @GroupContentEnabler(
 *   id = "group_membership_request",
 *   label = @Translation("Group membership request"),
 *   description = @Translation("Adds users as requesters for the group."),
 *   entity_type_id = "user",
 *   pretty_path_key = "request",
 *   reference_label = @Translation("Username"),
 *   reference_description = @Translation("The name of the user you want to make a member"),
 * )
 */
class GroupMembershipRequest extends GroupContentEnablerBase {

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
  public function getGroupOperations(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $operations = [];

    if (!$group->getMember($account) && $group->hasPermission('request group membership', $account)) {
      $operations['group-request-membership'] = [
        'title' => $this->t('Request group membership'),
        'url' => new Url(
          'social_group_request.request_membership',
          ['group' => $group->id()],
          ['query' => ['destination' => Url::fromRoute('<current>')->toString()]]
        ),
        'weight' => 99,
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  protected function getGroupContentPermissions() {
    $permissions = parent::getGroupContentPermissions();

    // Add extra permissions specific to membership group content entities.
    $permissions['request group membership'] = [
      'title' => 'Request group membership',
      'allowed for' => ['outsider'],
    ];

    // These are handled by 'administer members'.
    unset($permissions['update own group_membership_request content']);
    unset($permissions['view group_membership_request content']);
    unset($permissions['create group_membership_request content']);
    unset($permissions['update any group_membership_request content']);
    unset($permissions['delete any group_membership_request content']);
    unset($permissions['delete own group_membership_request content']);

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess(GroupInterface $group, AccountInterface $account) {
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, 'administer members');
  }

  /**
   * {@inheritdoc}
   */
  protected function viewAccess(GroupContentInterface $group_content, AccountInterface $account) {
    $group = $group_content->getGroup();
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, 'administer members');
  }

  /**
   * {@inheritdoc}
   */
  protected function updateAccess(GroupContentInterface $group_content, AccountInterface $account) {
    $group = $group_content->getGroup();
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, 'administer members');
  }

  /**
   * {@inheritdoc}
   */
  protected function deleteAccess(GroupContentInterface $group_content, AccountInterface $account) {
    $group = $group_content->getGroup();
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, 'administer members');
  }

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
  public function postInstall() {
    if (!\Drupal::isConfigSyncing()) {
      $group_content_type_id = $this->getContentTypeConfigId();

      // Add Status field.
      FieldConfig::create([
        'field_storage' => FieldStorageConfig::loadByName('group_content', 'grequest_status'),
        'bundle' => $group_content_type_id,
        'label' => $this->t('Request status'),
        'required' => TRUE,
        'default_value' => self::REQUEST_PENDING,
      ])->save();

      // Add "Updated by" field.
      FieldConfig::create([
        'field_storage' => FieldStorageConfig::loadByName('group_content', 'grequest_updated_by'),
        'bundle' => $group_content_type_id,
        'label' => $this->t('Approved/Rejected by'),
        'settings' => [
          'handler' => 'default',
          'target_bundles' => NULL,
        ],
      ])->save();

      // Build the 'default' display ID for both the entity form and view mode.
      $default_display_id = "group_content.$group_content_type_id.default";
      // Build or retrieve the 'default' view mode.
      if (!$view_display = EntityViewDisplay::load($default_display_id)) {
        $view_display = EntityViewDisplay::create([
          'targetEntityType' => 'group_content',
          'bundle' => $group_content_type_id,
          'mode' => 'default',
          'status' => TRUE,
        ]);
      }

      // Assign display settings for the 'default' view mode.
      $view_display
        ->setComponent('grequest_status', [
          'type' => 'number_integer',
        ])
        ->setComponent('grequest_updated_by', [
          'label' => 'above',
          'type' => 'entity_reference_label',
          'settings' => [
            'link' => 1,
          ],
        ])
        ->save();
    }
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
