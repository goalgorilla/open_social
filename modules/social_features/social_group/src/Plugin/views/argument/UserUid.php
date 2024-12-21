<?php

namespace Drupal\social_group\Plugin\views\argument;

use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a user id.
 *
 * This checks for groups that user created or is a member of.
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
  protected Connection $database;

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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('database'));
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE): void {
    $this->ensureMyTable();

    // Use the table definition to correctly add this user ID condition.
    if ($this->table !== 'group_relationship_field_data') {
      $sub_select2 = $this->database->select('group_relationship_field_data', 'gc');
      $sub_select2->addField('gc', 'gid');
      $sub_select2->condition('gc.entity_id', $this->argument);
      $sub_select2->condition('gc.plugin_id', '%' . $this->database->escapeLike('membership') . '%', 'LIKE');

      /** @var \Drupal\views\Plugin\views\query\Sql $query */
      $query = $this->query;
      if ($this->usesOptions && isset($this->options['group'])) {
        $query->addWhere($this->options['group'], $this->tableAlias . '.id', $sub_select2, 'IN');
      }
      else {
        // Add with default options (AND).
        $query->addWhere(0, $this->tableAlias . '.id', $sub_select2, 'IN');
      }
      $this->query = $query;
    }
  }

}
