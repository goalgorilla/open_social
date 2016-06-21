<?php

namespace Drupal\entity_access_by_field\Plugin\Field\FieldType;

use Drupal\options\Plugin\Field\FieldType\ListStringItem;

/**
 * Plugin implementation of the 'entity_access_field' field type.
 *
 * @FieldType(
 *   id = "entity_access_field",
 *   label = @Translation("Entity access field"),
 *   description = @Translation("Entity Access Field selector."),
 *   category = @Translation("Text"),
 *   default_widget = "options_buttons",
 *   default_formatter = "list_default",
 *
 * )
 */
class EntityAccessField extends ListStringItem {

}
