<?php

namespace Drupal\search_api\Plugin\views\field;

use Drupal\views\Plugin\views\field\Date;
use Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface;

/**
 * Handles the display of date fields in Search API Views.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("search_api_date")
 */
class SearchApiDate extends Date implements MultiItemsFieldHandlerInterface {

  use SearchApiFieldTrait;

}
