<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Preprocess\ImageWidget.
 */

namespace Drupal\bootstrap\Plugin\Preprocess;

use Drupal\bootstrap\Annotation\BootstrapPreprocess;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "image_widget" theme hook.
 *
 * @ingroup theme_preprocess
 *
 * @see image-widget.html.twig
 *
 * @BootstrapPreprocess("image_widget",
 *   replace = "template_preprocess_image_widget"
 * )
 */
class ImageWidget extends PreprocessBase implements PreprocessInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocessElement(Variables $variables, $hook, array $info) {
    $variables->addClass(['image-widget', 'js-form-managed-file', 'form-managed-file', 'clearfix']);

    $data = &$variables->offsetGet('data', []);
    foreach ($variables->element->children() as $key => $child) {
      // Modify the label to be a placeholder instead.
      if ($key === 'alt') {
        $child->setProperty('form_group', FALSE);
        $placeholder = (string) $child->getAttribute('placeholder');
        if (!$placeholder) {
          $label = ['#theme' => 'form_element_label'];
          $label += array_intersect_key($child->getArray(), array_flip(['#id', '#required', '#title', '#title_display']));
          $child->setProperty('title_display', 'invisible');
          $placeholder = trim(strip_tags(Element::create($label)->render()));
          if ($child->getProperty('required')) {
            $child->setProperty('description', t('@description (Required)', [
              '@description' => $child->getProperty('description'),
            ]));
          }
        }
        if ($placeholder) {
          $child->setAttribute('placeholder', $placeholder);
        }
      }
      $data[$key] = $child->getArray();
    }
  }

}
