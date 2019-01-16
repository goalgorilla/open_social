<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\Group;
use Drupal\social_group\SocialGroupHelperService;
use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'UserGroupMemberships' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_group_memberships",
 *  label = @Translation("Group memberships (specified)"),
 *  weight = -198,
 * )
 */
class UserGroupMemberships extends UserExportPluginBase {

  /**
   * The group helper service.
   *
   * @var \Drupal\social_group\SocialGroupHelperService
   */
  public $group_helper;

  /**
   * UserExportPluginBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter, Connection $database, SocialGroupHelperService $group_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $date_formatter, $database);

    $this->group_helper = $group_helper;
  }

  /**
   * The create method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container interface.
   * @param array $configuration
   *   An array of configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return \Drupal\Core\Plugin\ContainerFactoryPluginInterface|\Drupal\social_user_export\Plugin\UserExportPluginBase
   *   Returns the UserExportPluginBase.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('database'),
      $container->get('social_group.helper_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Group memberships (specified)');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    $group_memberships = Group::loadMultiple($this->group_helper->getAllGroupsForUser($entity->id()));
    $groups = [];
    foreach ($group_memberships as $group) {
      $groups[] = "{$group->label()} ({$group->id()})";
    }

    return implode(', ', $groups);
  }

}
