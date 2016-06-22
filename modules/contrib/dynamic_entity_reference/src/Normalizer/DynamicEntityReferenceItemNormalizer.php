<?php

namespace Drupal\dynamic_entity_reference\Normalizer;

use Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceItem;
use Drupal\hal\Normalizer\EntityReferenceItemNormalizer;

/**
 * Dynamic entity reference normalizer.
 */
class DynamicEntityReferenceItemNormalizer extends EntityReferenceItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = DynamicEntityReferenceItem::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = array()) {
    $normalized = parent::normalize($field_item, $format, $context);
    $normalized['target_type'] = $field_item->target_type;
    return $normalized;
  }

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    if ($id = $this->entityResolver->resolve($this, $data, $data['target_type'])) {
      return array(
        'target_type' => $data['target_type'],
        'target_id' => $id,
      );
    }
    return NULL;
  }

}
