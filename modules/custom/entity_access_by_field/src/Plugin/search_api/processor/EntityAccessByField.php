<?php

namespace Drupal\entity_access_by_field\Plugin\search_api\processor;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\Plugin\search_api\processor\ContentAccess;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds content access checks for nodes.
 *
 * @SearchApiProcessor(
 *   id = "entity_access_by_field",
 *   label = @Translation("Entity Access By Field"),
 *   description = @Translation("Adds content access checks for custom view permissions for entities based on field values in the entity_access field."),
 *   stages = {
 *     "add_properties" = 0,
 *     "pre_index_save" = -10,
 *     "preprocess_query" = -30,
 *   },
 * )
 */
class EntityAccessByField extends ContentAccess {

  use LoggerTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection|null
   */
  protected $database;

  /**
   * The current_user service used by this plugin.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|null
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->setLogger($container->get('logger.channel.search_api'));
    $processor->setDatabase($container->get('database'));
    $processor->setCurrentUser($container->get('current_user'));

    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    static $anonymous_user;

    if (!isset($anonymous_user)) {
      // Load the anonymous user.
      $anonymous_user = new AnonymousUserSession();
    }

    // Only run for node items.
    $entity_type_id = $item->getDatasource()->getEntityTypeId();
    if (!in_array($entity_type_id, ['node'])) {
      return;
    }

    // Get the node object.
    $node = $this->getNode($item->getOriginalObject());
    if (!$node) {
      // Apparently we were active for a wrong item.
      return;
    }

    // Get the field definitions of the node.
    $field_definitions = $node->getFieldDefinitions();
    /* @var \Drupal\Core\Field\FieldConfigInterface $field_definition */
    foreach ($field_definitions as $field_name => $field_definition) {
      // Lets get a node access realm if the field is implemented.
      if ($field_definition->getType() === 'entity_access_field') {
        $realm = [
          'view',
          'node',
          $node->getType(),
          $field_name,
        ];
        $realm = implode('_', $realm);

        $fields = $item->getFields();
        $fields = $this->getFieldsHelper()
          ->filterForPropertyPath($fields, NULL, 'search_api_node_grants');
        foreach ($fields as $field) {
          // Collect grant records for the node.
          $sql = 'SELECT gid, realm FROM {node_access} WHERE (nid = 0 OR nid = :nid) AND grant_view = 1 AND realm LIKE :realm';
          $args = [
            ':nid' => $node->id(),
            ':realm' => $this->getDatabase()->escapeLike($realm) . '%',
          ];
          $grant_records = $this->getDatabase()->query($sql, $args)->fetchAll();
          if ($grant_records) {
            foreach ($grant_records as $grant) {
              $field->addValue("node_access_{$grant->realm}:{$grant->gid}");
            }
          }
        }
      }
    }
  }

}
