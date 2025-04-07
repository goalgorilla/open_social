<?php

namespace Drupal\social_profile\Plugin\Field\FieldWidget;

use Drupal\Core\Cache\BackendChain;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupMembershipTrait;
use Drupal\group\Entity\GroupMembership;
use Drupal\profile\Entity\Profile;
use Drupal\social_profile\GroupAffiliation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Group Affiliation Widget.
 *
 * @FieldWidget(
 *   id = "group_affiliation_widget",
 *   label = @Translation("Group Affiliation Widget"),
 *   field_types = {
 *     "group_affiliation"
 *   }
 * )
 */
class GroupAffiliationWidget extends WidgetBase {

  use GroupMembershipTrait;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CacheBackendInterface $cacheBackend,
    protected BackendChain $cacheBackendGroupMembershipChain,
    protected GroupAffiliation $groupAffiliation,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('cache.default'),
      $container->get('cache.group_memberships_chained'),
      $container->get('social_profile.group_affiliation'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param FieldItemListInterface<\Drupal\social_profile\Plugin\Field\FieldType\GroupAffiliationItem> $items
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    // Do not render anything if affiliation feature is not enabled or
    // group affiliation does not fulfill required conditions to be enabled.
    if (
      !$this->groupAffiliation->isAffiliationFeatureEnabled() ||
      !$this->groupAffiliation->isGroupAffiliationEnabled()
    ) {
      return [];
    }

    $user_membership = NULL;
    $field_name = $this->fieldDefinition->getName();

    $profile = $items->getEntity();
    assert($profile instanceof Profile, 'group_affiliation field can not be used on any other entity except Profile.');
    $user = $profile->getOwner();

    $element['#tree'] = TRUE;

    // Add a wrapper div for AJAX updates.
    $element['container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ief-ajax-wrapper-' . $delta],
    ];

    // Group select field.
    $element['container']['group'] = [
      '#type' => 'select2',
      // '#title' => $this->t('Select Group'),
      '#options' => $this->getGroupOptions($user),
      '#ajax' => [
        'callback' => [$this, 'ajaxReloadCallback'],
        'wrapper' => 'ief-ajax-wrapper-' . $delta,
      ],
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $items[$delta]->target_id ?? '',
    ];

    // Input membership value.
    $input = $form_state->getUserInput();
    // Get group id from user input or from field value as fallback.
    $delta_group_id =
      $input[$field_name][$delta]['container']['group']
      ?? ($items[$delta]->target_id ?? '');

    if (!empty($delta_group_id)) {
      /** @var \Drupal\group\Entity\Group $delta_group */
      $delta_group = $this->entityTypeManager
        ->getStorage('group')
        ->load($delta_group_id);
      $user_membership = GroupMembership::loadSingle($delta_group, $user);
    }

    if ($user_membership) {

      // Reset inline entity form values for the item (delta) on ajax, where the
      // group relationship wash changed. This makes sure that affiliation form
      // is loaded with default entity (group membership) values.
      $triggering_element = $form_state->getTriggeringElement();
      if (
        $triggering_element &&
        $triggering_element['#ajax']['wrapper'] === 'ief-ajax-wrapper-' . $delta
      ) {
        $user_input = $form_state->getUserInput();
        // Unset inline_entity_form values for group_membership_form.
        unset($user_input[$field_name][$delta]['container']['group_membership_form']);
        $form_state->setUserInput($user_input);
      }

      // Inline Entity Form configuration for group membership.
      $element['container']['group_membership_form'] = [
        '#type' => 'inline_entity_form',
        '#entity_type' => $user_membership->getEntityTypeId(),
        '#bundle' => $user_membership->bundle(),
        '#form_mode' => 'affiliation',
        '#default_value' => $user_membership,
        // Note: at the moment only adding existing relationships is allowing.
        // In the future, it will be possible to have also "add" operation here.
        '#op' => 'edit',
        '#ief_row_delta' => $delta,
      ];
    }

    return $element;
  }

  /**
   * AJAX callback to reload the widget.
   *
   * This callback reloads the whole widget container with group select form
   * item and inline entity form item.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Widget render array.
   */
  public function ajaxReloadCallback(array &$form, FormStateInterface $form_state): array {
    $field_name = $this->fieldDefinition->getName();
    $triggering_element = $form_state->getTriggeringElement();

    // Just to satisfy phpstan.
    if (!isset($triggering_element['#parents'])) {
      return [];
    }

    $delta = $triggering_element['#parents'][1];
    return $form[$field_name]['widget'][$delta]['container'];
  }

  /**
   * Get the list of groups the user can affiliate with.
   *
   * Conditions required for the user to affiliate with the group:
   *  - Group type must be affiliation candidate
   *  - Group type must have affiliation enabled
   *  - User must be member of a group
   *  - Group must be published
   *  - Group visibility must not be limited to members (Flexible group only)
   *
   * Method cache_id:
   *   group_affiliation_options_by_user:{user_id}
   *
   * Cache tags:
   *   group_affiliation_options_by_user
   *
   * Example result:
   * [
   *   {Group type name} =>
   *     [
   *       {group_id} => {Group name},
   *       {group_id} => {Group name},
   *       ...
   *     ]
   *   Flexible group =>
   *     [
   *       1 => Group 1,
   *       2 => Group 2,
   *       ...
   *     ]
   *   ...
   * ]
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to load the memberships for.
   * @param string|array|null $roles
   *   (optional) A group role machine name or a list of group role machine
   *    names to filter on. Valid results only need to match on one role.
   *
   * @return array<string, array<int,string|\Drupal\Core\StringTranslation\TranslatableMarkup|null>>
   *   List of groups, grouped by group type.
   */
  private function getGroupOptions(AccountInterface $account, array|null|string $roles = NULL): array {
    // @todo this is not cached by roles, because roles parameter is not yet
    //   used.
    $cache_id = 'group_affiliation_options_by_user:' . $account->id();
    $cache = $this->cacheBackend->get($cache_id);
    if ($cache !== FALSE) {

      return $cache->data;
    }

    $options = [];
    $allowed_group_types = $this->groupAffiliation->getAffiliationEnabledGroupTypes();

    /**
     * @var string $allowed_group_type_id
     * @var \Drupal\group\Entity\GroupType $group_type
     */
    foreach ($allowed_group_types as $allowed_group_type_id => $group_type) {
      $groups = [];

      $user_memberships = $this->getUserMembershipsByGroupType($account, $allowed_group_type_id, $roles);
      if (empty($user_memberships)) {
        continue;
      }
      foreach ($user_memberships as $group_id => $user_membership) {
        /** @var \Drupal\group\Entity\Group $membership_group */
        $membership_group = $user_membership->getGroup();
        $groups[(int) $membership_group->id()] = $membership_group->label();
      }

      $options[(string) $group_type->label()] = $groups;
    }

    $cacheability = (new CacheableMetadata())
      ->setCacheTags([GroupAffiliation::GENERAL_CACHE_TAG]);

    $this->cacheBackend->set($cache_id, $options, Cache::PERMANENT, $cacheability->getCacheTags());

    return $options;
  }

  /**
   * Get user memberships by group type.
   *
   * This method is inspired by
   * Drupal\group\Entity\GroupMembershipTrait::loadByUser(). The main difference
   * is that we are limit memberships by group type.
   *
   * Cache tags:
   *   group_affiliation_options_by_user
   *   group_content_list:plugin:group_membership:entity:{uid}
   *   group_content_list:plugin:group_membership:entity:{uid}:group_type:{group_type}
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to load the memberships for.
   * @param string $group_type
   *   Group type id.
   * @param array|string|null $roles
   *   (optional) A group role machine name or a list of group role machine
   *    names to filter on. Valid results only need to match on one role.
   *
   * @return array<int, GroupMembership>
   *   An array keyed by group ID and group membership as value.
   */
  private function getUserMembershipsByGroupType(AccountInterface $account, string $group_type, array|null|string $roles = NULL): array {
    $allowed_group_types = $this->groupAffiliation->getAffiliationEnabledGroupTypes();

    // Early return if affiliation is not enabled for group type.
    if (!in_array($group_type, array_keys($allowed_group_types))) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('group_content');
    $cache_backend = $this->cacheBackendGroupMembershipChain;

    $cid = self::createCacheId([
      'entity_id' => $account->id(),
      'group_type' => $group_type,
      'roles' => $roles ?? 'any-roles',
    ]);

    if ($cache = $cache_backend->get($cid)) {
      if (empty($cache->data)) {
        return [];
      }

      /** @var array<int, \Drupal\group\Entity\GroupMembership> $memberships */
      $memberships = $storage->loadMultiple($cache->data);
      return $memberships;
    }

    $query = $storage->getQuery()
      ->condition('entity_id', $account->id())
      ->condition('plugin_id', 'group_membership')
      ->condition('group_type', $group_type)
      ->condition('gid.entity.status', 1)
      ->accessCheck(FALSE);

    if ($group_type === 'flexible_group') {
      // Remove flexible groups visible to members only (secret).
      $query->condition('gid.entity.field_flexible_group_visibility.value', 'members', '!=');
    }

    if (isset($roles)) {
      $query->condition('group_roles', (array) $roles, 'IN');
    }

    // Sort alphabetically by group name.
    $query->sort('gid.entity.label', 'ASC');

    $cacheability = (new CacheableMetadata())
      ->addCacheTags([GroupAffiliation::GENERAL_CACHE_TAG])
      ->addCacheTags(['group_content_list:plugin:group_membership:entity:' . $account->id()])
      ->addCacheTags(['group_content_list:plugin:group_membership:entity:' . $account->id() . ':group_type:' . $group_type]);

    // Cache the IDs by group ID.
    // ATM there is no particular reason to do so, except to keep the same cache
    // structure as Drupal\group\Entity\GroupMembershipTrait::loadByUser().
    $cached_ids = [];
    /** @var array<int, \Drupal\group\Entity\GroupMembership> $memberships */
    $memberships = $storage->loadMultiple($query->execute());
    foreach ($memberships as $membership) {
      $cached_ids[$membership->getGroupId()] = $membership->id();
    }
    $cache_backend->set($cid, $cached_ids, $cacheability->getCacheMaxAge(), $cacheability->getCacheTags());

    return $memberships;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    $processed_values = [];

    foreach ($values as $delta => $item) {
      if (!empty($item['container']['group'])) {
        $processed_values[$delta]['target_id'] = $item['container']['group'];
      }
    }

    return $processed_values;
  }

}
