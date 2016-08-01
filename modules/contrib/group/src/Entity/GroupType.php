<?php

/**
 * @file
 * Contains \Drupal\group\Entity\GroupType.
 */

namespace Drupal\group\Entity;

use Drupal\group\Plugin\GroupContentEnablerCollection;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Config\Entity\Exception\ConfigEntityIdLengthException;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Group type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "group_type",
 *   label = @Translation("Group type"),
 *   handlers = {
 *     "access" = "Drupal\group\Entity\Access\GroupTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupTypeForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupTypeForm",
 *       "delete" = "Drupal\group\Entity\Form\GroupTypeDeleteForm"
 *     },
 *     "list_builder" = "Drupal\group\Entity\Controller\GroupTypeListBuilder",
 *   },
 *   admin_permission = "administer group",
 *   config_prefix = "type",
 *   bundle_of = "group",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "collection" = "/admin/group/types",
 *     "edit-form" = "/admin/group/types/manage/{group_type}",
 *     "delete-form" = "/admin/group/types/manage/{group_type}/delete",
 *     "content-plugins" = "/admin/group/types/manage/{group_type}/content",
 *     "permissions-form" = "/admin/group/types/manage/{group_type}/permissions"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "content"
 *   }
 * )
 */
class GroupType extends ConfigEntityBundleBase implements GroupTypeInterface {

  /**
   * The machine name of the group type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the group type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of the group type.
   *
   * @var string
   */
  protected $description;

  /**
   * The content enabler plugin configuration for the group type.
   *
   * @var string[]
   */
  protected $content = [];

  /**
   * Holds the collection of content enabler plugins the group type uses.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerCollection
   */
  protected $contentCollection;

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
  public function getRoles() {
    return $this->entityTypeManager()
      ->getStorage('group_role')
      ->loadByProperties(['group_type' => $this->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getRoleIds() {
    $role_ids = [];
    foreach ($this->getRoles() as $group_role) {
      $role_ids[] = $group_role->id();
    }
    return $role_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnonymousRole() {
    return $this->entityTypeManager()
      ->getStorage('group_role')
      ->load($this->getAnonymousRoleId());
  }

  /**
   * {@inheritdoc}
   */
  public function getAnonymousRoleId() {
    return $this->id() . '-anonymous';
  }

  /**
   * {@inheritdoc}
   */
  public function getOutsiderRole() {
    return $this->entityTypeManager()
      ->getStorage('group_role')
      ->load($this->getOutsiderRoleId());
  }

  /**
   * {@inheritdoc}
   */
  public function getOutsiderRoleId() {
    return $this->id() . '-outsider';
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberRole() {
    return $this->entityTypeManager()
      ->getStorage('group_role')
      ->load($this->getMemberRoleId());
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberRoleId() {
    return $this->id() . '-member';
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // Throw an exception if the group type ID is longer than the limit.
    if (strlen($this->id()) > GroupTypeInterface::ID_MAX_LENGTH) {
      throw new ConfigEntityIdLengthException("Attempt to create a group type with an ID longer than " . GroupTypeInterface::ID_MAX_LENGTH . " characters: {$this->id()}.");
    }

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (!$update) {
      // Store the id in a short variable for readability.
      $group_type_id = $this->id();

      // @todo Remove this line when https://www.drupal.org/node/2645202 lands.
      $this->setOriginalId($group_type_id);

      // Create the three special roles for the group type.
      GroupRole::create([
        'id' => $this->getAnonymousRoleId(),
        'label' => t('Anonymous'),
        'weight' => -102,
        'internal' => TRUE,
        'group_type' => $group_type_id,
      ])->save();
      GroupRole::create([
        'id' => $this->getOutsiderRoleId(),
        'label' => t('Outsider'),
        'weight' => -101,
        'internal' => TRUE,
        'group_type' => $group_type_id,
      ])->save();
      GroupRole::create([
        'id' => $this->getMemberRoleId(),
        'label' => t('Member'),
        'weight' => -100,
        'internal' => TRUE,
        'group_type' => $group_type_id,
      ])->save();

      // Enable enforced content plugins for new group types.
      $this->getContentEnablerManager()->installEnforced($this);
    }
  }

  /**
   * Returns the content enabler plugin manager.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   *   The group content plugin manager.
   */
  protected function getContentEnablerManager() {
    return \Drupal::service('plugin.manager.group_content_enabler');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstalledContentPlugins() {
    if (!$this->contentCollection) {
      $this->contentCollection = new GroupContentEnablerCollection($this->getContentEnablerManager(), $this->content);
      $this->contentCollection->sort();
    }
    return $this->contentCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function hasContentPlugin($plugin_id) {
    return isset($this->content[$plugin_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getContentPlugin($plugin_id) {
    return $this->getInstalledContentPlugins()->get($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['content' => $this->getInstalledContentPlugins()];
  }

  /**
   * {@inheritdoc}
   */
  public function installContentPlugin($plugin_id, array $configuration = []) {
    // The content plugins expect the actual configurable data to be under the
    // 'data' key and the crucial data at the root level, so let's fix that.
    $configuration['data'] = $configuration;

    // Add in the crucial configuration keys.
    $configuration['id'] = $plugin_id;
    $configuration['group_type'] = $this->id();

    // Save the plugin to the group type.
    $this->getInstalledContentPlugins()->addInstanceId($plugin_id, $configuration);
    $this->save();

    // Save the group content type config entity.
    $plugin = $this->getContentPlugin($plugin_id);
    $values = [
      'id' => $plugin->getContentTypeConfigId(),
      'label' => $plugin->getContentTypeLabel(),
      'description' => $plugin->getContentTypeDescription(),
      'group_type' => $this->id(),
      'content_plugin' => $plugin_id,
    ];
    GroupContentType::create($values)->save();

    // Run the post install tasks on the plugin.
    $plugin->postInstall();

    // Rebuild the routes if the plugin defines any.
    if (!empty($plugin->getRoutes())) {
      \Drupal::service('router.builder')->setRebuildNeeded();
    }

    // Rebuild the local actions if the plugin defines any.
    if (!empty($plugin->getLocalActions())) {
      \Drupal::service('plugin.manager.menu.local_action')->clearCachedDefinitions();
    }

    // Clear the entity type cache if the plugin adds to the GroupContent info.
    if (!empty($plugin->getEntityForms())) {
      $this->entityTypeManager()->clearCachedDefinitions();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function updateContentPlugin($plugin_id, array $configuration) {
    if ($this->hasContentPlugin($plugin_id)) {
      // @todo Refactor the way GroupContentEnablerBase saves config.
      $plugin = $this->getContentPlugin($plugin_id);
      $old = $plugin->getConfiguration();
      $old['data'] = $configuration + $old['data'];
      $this->getInstalledContentPlugins()->setInstanceConfiguration($plugin_id, $old);
      $this->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function uninstallContentPlugin($plugin_id) {
    // Get the content type ID from the plugin instance before we delete it.
    $plugin = $this->getContentPlugin($plugin_id);
    $content_type_id = $plugin->getContentTypeConfigId();

    // Remove the plugin from the group type.
    $this->getInstalledContentPlugins()->removeInstanceId($plugin_id);
    $this->save();

    // Delete the group content type config entity.
    GroupContentType::load($content_type_id)->delete();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // All dependencies should be recalculated on every save apart from enforced
    // dependencies. This ensures stale dependencies are never saved.
    $this->dependencies = array_intersect_key($this->dependencies, ['enforced' => '']);

    // The parent calculateDependencies() would merge in the installed plugin
    // dependencies at this point. However, because we have an uninstall
    // validator preventing you from removing any module that provides a plugin
    // which has content for it, we don't want the plugin's dependencies added
    // to the group type as it would get deleted when the module which provides
    // that plugin is uninstalled.

    // Taken from the parent function 1:1.
    if ($this instanceof ThirdPartySettingsInterface) {
      // Configuration entities need to depend on the providers of any third
      // parties that they store the configuration for.
      foreach ($this->getThirdPartyProviders() as $provider) {
        $this->addDependency('module', $provider);
      }
    }

    return $this;
  }

}
