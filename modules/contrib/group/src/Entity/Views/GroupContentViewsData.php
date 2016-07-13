<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Views\GroupContentViewsData.
 */

namespace Drupal\group\Entity\Views;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\views\EntityViewsData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the views data for the group content entity type.
 */
class GroupContentViewsData extends EntityViewsData {

  /**
   * The group content enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * The entity manager set but not declared in the parent class.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface;
   */
  protected $entityManager;

  /**
   * Constructs a GroupContentViewsData object.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content enabler plugin manager.
   */
  function __construct(EntityTypeInterface $entity_type, SqlEntityStorageInterface $storage_controller, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, TranslationInterface $translation_manager, GroupContentEnablerManagerInterface $plugin_manager) {
    parent::__construct($entity_type, $storage_controller, $entity_manager, $module_handler, $translation_manager);
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('string_translation'),
      $container->get('plugin.manager.group_content_enabler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Get the data table for GroupContent entities.
    $data_table = $this->entityType->getDataTable();

    // Unset the 'entity_id' field relationship as we want a more powerful one.
    // @todo Eventually, we may want to replace all of 'entity_id'.
    unset($data[$data_table]['entity_id']['relationship']);

    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    $entity_types = $this->entityManager->getDefinitions();

    // Add views data for each installed plugin.
    foreach ($this->pluginManager->getInstalled() as $plugin_id => $plugin) {
      $entity_type_id = $plugin->getEntityTypeId();
      $entity_type = $entity_types[$entity_type_id];
      $entity_data_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();

      // Create a unique field name for this views field.
      $field_name = 'gc__' . $entity_type_id;

      // We only add one 'group_content' relationship per entity type.
      if (isset($data[$entity_data_table][$field_name])) {
        continue;
      }

      $t_args = [
        '@entity_type' => $entity_type->getLabel(),
      ];

      // This relationship will allow a group content entity to easily map to a
      // content entity that it ties to a group, optionally filtering by plugin.
      $data[$data_table][$field_name] = array(
        'title' => t('@entity_type from group content', $t_args),
        'help' => t('Relates to the @entity_type entity the group content represents.', $t_args),
        'relationship' => array(
          'group' => $entity_type->getLabel(),
          'base' => $entity_data_table,
          'base field' => $entity_type->getKey('id'),
          'relationship field' => 'entity_id',
          'id' => 'group_content_to_entity',
          'label' => t('Group content @entity_type', $t_args),
          'target_entity_type' => $entity_type_id,
        ),
      );
    }

    return $data;
  }

}
