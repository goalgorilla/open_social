<?php

namespace Drupal\social_queue_storage\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Queue storage entity type entity.
 *
 * @ConfigEntityType(
 *   id = "queue_storage_entity_type",
 *   label = @Translation("Queue storage entity type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {},
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "queue_storage_entity_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "queue_storage_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {}
 * )
 */
class QueueStorageEntityType extends ConfigEntityBundleBase implements QueueStorageEntityTypeInterface {

  /**
   * The Queue storage entity type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Queue storage entity type label.
   *
   * @var string
   */
  protected $label;

}
