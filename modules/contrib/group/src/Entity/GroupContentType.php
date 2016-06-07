<?php

/**
 * @file
 * Contains \Drupal\group\Entity\GroupContentType.
 *
 * @todo Create these automatically for fixed plugins!
 */

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Group content type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "group_content_type",
 *   label = @Translation("Group content type"),
 *   handlers = {
 *     "access" = "Drupal\group\Entity\Access\GroupContentTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupContentTypeForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupContentTypeForm",
 *       "delete" = "Drupal\group\Entity\Form\GroupContentTypeDeleteForm"
 *     },
 *   },
 *   admin_permission = "administer group",
 *   config_prefix = "content_type",
 *   bundle_of = "group_content",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "group_type",
 *     "content_plugin",
 *   }
 * )
 */
class GroupContentType extends ConfigEntityBundleBase implements GroupContentTypeInterface {

  /**
   * The machine name of the group content type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the group content type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of the group content type.
   *
   * @var string
   */
  protected $description;

  /**
   * The group type ID for the group content type.
   *
   * @var string
   */
  protected $group_type;

  /**
   * The group content enabler plugin ID for the group content type.
   *
   * @var string
   */
  protected $content_plugin;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupType() {
    return GroupType::load($this->group_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupTypeId() {
    return $this->group_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentPlugin() {
    return $this->getGroupType()->getContentPlugin($this->content_plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function getContentPluginId() {
    return $this->content_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByContentPluginId($plugin_id) {
    return \Drupal::entityTypeManager()
      ->getStorage('group_content_type')
      ->loadByProperties(['content_plugin' => $plugin_id]);
  }
  
  /**
   * {@inheritdoc}
   */
  public static function loadByEntityTypeId($entity_type_id) {
    $plugin_ids = [];
    
    /** @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.group_content_enabler');

    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    foreach ($plugin_manager->getAll() as $plugin_id => $plugin) {
      if ($plugin->getEntityTypeId() === $entity_type_id) {
        $plugin_ids[] = $plugin_id;
      }
    }

    // If no responsible group content plugins were found, we return nothing.
    if (empty($plugin_ids)) {
      return [];
    }

    // Otherwise load all group content types being handled by gathered plugins.
    return self::loadByContentPluginId($plugin_ids);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    // In case the group content type got deleted by uninstalling the providing
    // module, we still need to uninstall it on the group type.
    foreach ($entities as $entity) {
      /** @var \Drupal\group\Entity\GroupContentTypeInterface $entity */
      if ($entity->isUninstalling()) {
        $group_type = $entity->getGroupType();
        $group_type->getInstalledContentPlugins()->removeInstanceId($entity->getContentPluginId());
        $group_type->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // By adding the group type and module as dependencies, we ensure the group
    // content type is deleted as well when the module or group type is deleted.
    $this->addDependency('config', $this->getGroupType()->getConfigDependencyName());
    $this->addDependency('module', $this->getContentPlugin()->getProvider());
  }

}
