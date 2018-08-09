<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\Table as BaseTable;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "table" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("table")
 */
class Table extends BaseTable {

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables) {
    if (isset($variables['attributes']['id'])) {
      if (strpos($variables['attributes']['id'], 'edit-field-files') !== FALSE) {
        $variables['attributes']['class'][] = 'tablesaw';
        $variables['attributes']['data-tablesaw-mode'] = 'stack';
      }
      elseif ($variables['attributes']['id'] === 'social-follow-content-table') {
        $variables->header['operations']['attributes']->addClass('text-right');

        foreach ($variables->rows as &$row) {
          /** @var \Drupal\Core\Link $link */
          $link = $row['cells']['title']['content'];
          $element = $link->toRenderable();
          $element['#attributes']['class'][] = 'name';
          $row['cells']['title']['content'] = $element;

          $row['cells']['operations']['attributes']->addClass('text-right');

          $row['cells']['operations']['content']['#attributes'] = [
            'class' => [
              'btn',
              'btn-default',
              'btn-sm',
              'waves-effect',
              'waves-btn',
            ],
          ];
        }
      }
    }

    parent::preprocessVariables($variables);
  }

}
