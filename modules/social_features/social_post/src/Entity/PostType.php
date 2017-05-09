<?php

namespace Drupal\social_post\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Post type entity.
 *
 * @ConfigEntityType(
 *   id = "post_type",
 *   label = @Translation("Post type"),
 *   handlers = {
 *     "list_builder" = "Drupal\social_post\PostTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\social_post\Form\PostTypeForm",
 *       "edit" = "Drupal\social_post\Form\PostTypeForm",
 *       "delete" = "Drupal\social_post\Form\PostTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\social_post\PostTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "post_type",
 *   admin_permission = "administer post entities",
 *   bundle_of = "post",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/post/{post_type}",
 *     "add-form" = "/admin/structure/post/add",
 *     "edit-form" = "/admin/structure/post/{post_type}/edit",
 *     "delete-form" = "/admin/structure/post/{post_type}/delete",
 *     "collection" = "/admin/structure/post"
 *   }
 * )
 */
class PostType extends ConfigEntityBundleBase implements PostTypeInterface {

  /**
   * The Post type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Post type label.
   *
   * @var string
   */
  protected $label;

}
