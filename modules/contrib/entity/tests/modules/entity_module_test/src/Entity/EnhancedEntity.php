<?php

/**
 * @file
 * Contains \Drupal\entity_module_test\Entity\EnhancedEntity.
 */

namespace Drupal\entity_module_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity\Revision\RevisionableContentEntityBase;

/**
 * Provides a test entity which uses all the capabilities of entity module.
 *
 * @ContentEntityType(
 *   id = "entity_test_enhanced",
 *   label = @Translation("Entity test with enhancements"),
 *   handlers = {
 *     "storage" = "\Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "form" = {
 *       "add" = "\Drupal\entity\Form\RevisionableContentEntityForm",
 *       "edit" = "\Drupal\entity\Form\RevisionableContentEntityForm",
 *       "delete" = "\Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "\Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *       "revision" = "\Drupal\entity\Routing\RevisionRouteProvider",
 *       "delete-multiple" = "\Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *     "list_builder" = "\Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   base_table = "entity_test_enhanced",
 *   data_table = "entity_test_enhanced_field_data",
 *   revision_table = "entity_test_enhanced_revision",
 *   revision_data_table = "entity_test_enhanced_field_revision",
 *   translatable = TRUE,
 *   revisionable = TRUE,
 *   admin_permission = "administer entity_test_enhanced",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "revision" = "vid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "add-page" = "/entity_test_enhanced/add",
 *     "add-form" = "/entity_test_enhanced/add/{type}",
 *     "edit-form" = "/entity_test_enhanced/{entity_test_enhanced}/edit",
 *     "canonical" = "/entity_test_enhanced/{entity_test_enhanced}",
 *     "collection" = "/entity_test_enhanced",
 *     "delete-multiple-form" = "/entity_test_enhanced/delete",
 *     "revision" = "/entity_test_enhanced/{entity_test_enhanced}/revisions/{entity_test_enhanced_revision}/view",
 *     "revision-revert-form" = "/entity_test_enhanced/{entity_test_enhanced}/revisions/{entity_test_enhanced_revision}/revert",
 *     "version-history" = "/entity_test_enhanced/{entity_test_enhanced}/revisions",
 *   },
 *   bundle_entity_type = "entity_test_enhanced_bundle",
 * )
 */
class EnhancedEntity extends RevisionableContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel('Name')
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ]);

    return $fields;
  }

}
