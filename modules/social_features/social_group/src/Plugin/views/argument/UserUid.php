<?php

namespace Drupal\social_group\Plugin\views\argument;

use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\query;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a user id to check for groups that user created
 * or is a member off.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("group_content_user_uid")
 */
class UserUid extends ArgumentPluginBase {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('database'));
  }

  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    // Use the table definition to correctly add this user ID condition.
    if ($this->table != 'group_content_field_data') {
      $subselect2 = $this->database->select('group_content_field_data', 'gc');
      $subselect2->addField('gc', 'gid');
      $subselect2->condition('gc.entity_id', $this->argument);
      $subselect2->condition('gc.type', '%' . $this->database->escapeLike('membership') . '%', 'LIKE');

      $this->query->addWhere($this->options['group'], 'groups_field_data.id', $subselect2, 'IN');
    }
  }
}
