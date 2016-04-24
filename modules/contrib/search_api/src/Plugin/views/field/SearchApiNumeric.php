<?php

namespace Drupal\search_api\Plugin\views\field;

use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ViewExecutable;

/**
 * Displays numeric data.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("search_api_numeric")
 */
class SearchApiNumeric extends NumericField implements MultiItemsFieldHandlerInterface {

  use SearchApiFieldTrait {
    defineOptions as traitDefineOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    // In case we act as the fallback handler for an entity field, our
    // submitOptionsForm() method won't be called, which means the
    // "format_plural_string" option won't be saved correctly. Fix that here.
    if (isset($options['format_plural_values'])) {
      $options['format_plural_string'] = implode(PluralTranslatableMarkup::DELIMITER, $options['format_plural_values']);
    }

    parent::init($view, $display, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = $this->traitDefineOptions();

    $options['format_plural_values'] = array('default' => array());

    return $options;
  }

}
