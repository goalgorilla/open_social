<?php

namespace Drupal\alternative_frontpage\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\alternative_frontpage\AlternativeFrontpageInterface;

/**
 * Defines the alternative_frontpage entity.
 *
 * @ConfigEntityType(
 *   id = "alternative_frontpage",
 *   label = @Translation("Alternative Frontpage"),
 *   handlers = {
 *     "list_builder" = "Drupal\alternative_frontpage\Controller\AlternativeFrontpageListBuilder",
 *     "form" = {
 *       "add" = "Drupal\alternative_frontpage\Form\AlternativeFrontpageForm",
 *       "edit" = "Drupal\alternative_frontpage\Form\AlternativeFrontpageForm",
 *       "delete" = "Drupal\alternative_frontpage\Form\AlternativeFrontpageDeleteForm",
 *     }
 *   },
 *   config_prefix = "alternative_frontpage",
 *   admin_permission = "administer alternative frontpage settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "path",
 *     "roles_target_id",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/alternative_frontpage/manage/{alternative_frontpage}",
 *     "delete-form" = "/admin/config/alternative_frontpage/manage/{alternative_frontpage}/delete",
 *   }
 * )
 */
class AlternativeFrontpage extends ConfigEntityBase implements AlternativeFrontpageInterface {

  /**
   * The alternative_frontpage ID.
   *
   * @var string
   */
  public $id;

  /**
   * The alternative_frontpage label.
   *
   * @var string
   */
  public $label;

  /**
   * The alternative_frontpage path.
   *
   * @var string
   */
  public $path;

  /**
   * The alternative_frontpage user role.
   *
   * @var string
   */
  public $roles_target_id;

}
