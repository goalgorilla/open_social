<?php

namespace Drupal\dynamic_entity_reference\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceIdFormatter;

/**
 * Plugin implementation of the 'dynamic entity reference ID' formatter.
 *
 * @FieldFormatter(
 *   id = "dynamic_entity_reference_entity_id",
 *   label = @Translation("Entity ID"),
 *   description = @Translation("Display the ID of the referenced entities."),
 *   field_types = {
 *     "dynamic_entity_reference"
 *   }
 * )
 */
class DynamicEntityReferenceIdFormatter extends EntityReferenceIdFormatter {

  use DynamicEntityReferenceFormatterTrait;

}
