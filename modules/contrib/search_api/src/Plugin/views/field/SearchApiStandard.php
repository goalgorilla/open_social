<?php

namespace Drupal\search_api\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface;

/**
 * Provides a default handler for fields in Search API Views.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("search_api")
 */
class SearchApiStandard extends FieldPluginBase implements MultiItemsFieldHandlerInterface {

  use SearchApiFieldTrait;

  /**
   * {@inheritdoc}
   */
  public function render_item($count, $item) {
    $type = !empty($this->definition['filter_type']) ? $this->definition['filter_type'] : 'plain';
    return $this->sanitizeValue($item['value'], $type);
  }

}
