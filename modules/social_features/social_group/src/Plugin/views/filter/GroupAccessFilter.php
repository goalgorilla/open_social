<?php

namespace Drupal\social_group\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\social_group\SocialGroupHelperService;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter groups by access.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("group_access")
 */
class GroupAccessFilter extends FilterPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Social group helper to use.
   */
  protected SocialGroupHelperService $socialGroupHelper;

  /**
   * Constructs a GroupAccessFilter.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\social_group\SocialGroupHelperService $social_group_helper
   *   The social group helper service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SocialGroupHelperService $social_group_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->socialGroupHelper = $social_group_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('social_group.helper_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    $account = $this->view->getUser();
    if (!$account->hasPermission('administer group') && !$account->hasPermission('bypass group access')) {
      $group_access = $this->filterGroupsOnMembership($account);

      $group_visible = new Condition('OR');
      $group_visible->condition('groups_field_data.type', 'public_group');

      if (!$account->isAnonymous()) {
        $group_visible->condition('groups_field_data.type', 'open_group');
      }
      if ($this->moduleHandler->moduleExists('social_group_flexible_group')) {
        $flexible_groups_visible = $this->filterFlexibleGroups($account->isAnonymous());
        $group_visible->condition($flexible_groups_visible);
      }
      if ($group_access !== NULL) {
        $group_visible->condition($group_access);
      }

      $this->query->addWhere('visibility', $group_visible);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'user';
    return $contexts;
  }

  /**
   * Filter groups on membership for given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check memberships for.
   *
   * @return \Drupal\Core\Database\Query\Condition|null
   *   Returns the Condition or NULL if user has no memberships.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function filterGroupsOnMembership(AccountInterface $account): ?Condition {
    $group_memberships = $this->socialGroupHelper->getAllGroupsForUser($account->id());
    // If user is part of groups and is not anonymous.
    if (!empty($group_memberships) && !$account->isAnonymous()) {
      $configuration = [
        'left_table' => 'groups_field_data',
        'left_field' => 'id',
        'table' => 'group_content_field_data',
        'field' => 'gid',
        'operator' => '=',
      ];

      $join = Views::pluginManager('join')
        ->createInstance('standard', $configuration);
      $this->query->addRelationship('group_content', $join, 'groups_field_data');

      $group_membership_access = new Condition('OR');
      $group_membership_access->condition('group_content.gid', $group_memberships, 'IN');
      return $group_membership_access;
    }
    return NULL;
  }

  /**
   * Filter flexible groups.
   *
   * @param bool $anonymous
   *   Whether the current user is anonymous.
   *
   * @return \Drupal\Core\Database\Query\Condition
   *   Returns the Condition.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function filterFlexibleGroups(bool $anonymous): Condition {
    $configuration = [
      'left_table' => 'groups_field_data',
      'left_field' => 'id',
      'table' => 'group__field_flexible_group_visibility',
      'field' => 'entity_id',
      'operator' => '=',
    ];

    $join = Views::pluginManager('join')
      ->createInstance('standard', $configuration);
    $this->query->addRelationship('group_visibility', $join, 'groups_field_data');
    $flexible_groups_visible = new Condition('OR');
    $flexible_groups_visible->condition('group_visibility.field_flexible_group_visibility_value', 'public');
    if (!$anonymous) {
      $flexible_groups_visible->condition('group_visibility.field_flexible_group_visibility_value', 'community');
    }
    return $flexible_groups_visible;
  }

}
