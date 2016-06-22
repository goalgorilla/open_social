<?php

namespace Drupal\dynamic_entity_reference\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;

/**
 * Plugin implementation of the 'options_select' widget.
 *
 * @FieldWidget(
 *   id = "dynamic_entity_reference_options_select",
 *   label = @Translation("Select list"),
 *   field_types = {
 *     "dynamic_entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class DynamicEntityReferenceOptionsSelectWidget extends OptionsSelectWidget {

  use DynamicEntityReferenceOptionsTrait;

}
