<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Preprocess\FieldMultipleValueForm.
 */

namespace Drupal\bootstrap\Plugin\Preprocess;

use Drupal\bootstrap\Annotation\BootstrapPreprocess;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "field_multiple_value_form" theme hook.
 *
 * @ingroup theme_preprocess
 *
 * @BootstrapPreprocess("field_multiple_value_form")
 */
class FieldMultipleValueForm extends PreprocessBase implements PreprocessInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocessElement(Variables $variables, $hook, array $info) {
    // Wrap header columns in label element for Bootstrap.
    if ($variables['multiple']) {
      $header = [
        [
          'data' => [
            '#prefix' => '<label class="label">',
            'title' => ['#markup' => $variables->element->getProperty('title')],
            '#suffix' => '</label>',
          ],
          'colspan' => 2,
          'class' => ['field-label'],
        ],
        t('Order', [], ['context' => 'Sort order']),
      ];

      $variables['table']['#header'] = $header;
    }
  }

}
