<?php

namespace Drupal\search_api\Plugin\views\field;

use Drupal\views\Plugin\views\field\Boolean;
use Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface;

/**
 * Handles the display of boolean fields in Search API Views.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("search_api_boolean")
 */
class SearchApiBoolean extends Boolean implements MultiItemsFieldHandlerInterface {

  use SearchApiFieldTrait;

}
