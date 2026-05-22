<?php

namespace Drupal\social_content_report_legacy_entity_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a legacy-style publishable test entity.
 *
 * @ContentEntityType(
 *   id = "test_legacy_entity",
 *   label = @Translation("Test legacy entity"),
 *   base_table = "test_legacy_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "status" = "status",
 *   }
 * )
 */
class TestLegacyEntity extends ContentEntityBase {

  /**
   * Sets the entity publication status using the legacy boolean API.
   *
   * @param bool $published
   *   Whether the entity should be published.
   *
   * @return $this
   */
  public function setPublished(bool $published) {
    $this->set('status', $published);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setDefaultValue(TRUE);

    return $fields;
  }

}
