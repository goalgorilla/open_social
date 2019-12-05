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
 *     "list_builder" = "Drupal\social_queue_storage\QueueStorageEntityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\social_queue_storage\Form\QueueStorageEntityTypeForm",
 *       "edit" = "Drupal\social_queue_storage\Form\QueueStorageEntityTypeForm",
 *       "delete" = "Drupal\social_queue_storage\Form\QueueStorageEntityTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\social_queue_storage\QueueStorageEntityTypeHtmlRouteProvider",
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
 *   links = {
 *     "canonical" = "/admin/structure/queue_storage_entity_type/{queue_storage_entity_type}",
 *     "add-form" = "/admin/structure/queue_storage_entity_type/add",
 *     "edit-form" = "/admin/structure/queue_storage_entity_type/{queue_storage_entity_type}/edit",
 *     "delete-form" = "/admin/structure/queue_storage_entity_type/{queue_storage_entity_type}/delete",
 *     "collection" = "/admin/structure/queue_storage_entity_type"
 *   }
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
