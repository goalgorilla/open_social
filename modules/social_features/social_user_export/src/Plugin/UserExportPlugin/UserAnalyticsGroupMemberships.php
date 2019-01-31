<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_group\SocialGroupHelperService;
use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'UserAnalyticsGroupMemberships' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_group_memberships_count",
 *  label = @Translation("Group memberships"),
 *  weight = -199,
 * )
 */
class UserAnalyticsGroupMemberships extends UserExportPluginBase {

  /**
   * The group helper service.
   *
   * @var \Drupal\social_group\SocialGroupHelperService
   */
  public $groupHelper;

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
   * @param \Drupal\social_group\SocialGroupHelperService $group_helper
   *   The group helper service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter, Connection $database, SocialGroupHelperService $group_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $date_formatter, $database);

    $this->groupHelper = $group_helper;
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
    return $this->t('Group memberships');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return count($this->groupHelper->getAllGroupsForUser($entity->id()));
  }

}
