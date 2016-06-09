<?php

namespace Drupal\dynamic_entity_reference\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;

/**
 * Plugin implementation of the 'options_buttons' widget.
 *
 * @FieldWidget(
 *   id = "dynamic_entity_reference_options_buttons",
 *   label = @Translation("Check boxes/radio buttons"),
 *   field_types = {
 *     "dynamic_entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class DynamicEntityReferenceOptionsButtonsWidget extends OptionsButtonsWidget {
  use DynamicEntityReferenceOptionsTrait;

}
