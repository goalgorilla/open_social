<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Form\NodePreviewFormSelect.
 */

namespace Drupal\bootstrap\Plugin\Form;

use Drupal\bootstrap\Annotation\BootstrapForm;
use Drupal\bootstrap\Bootstrap;
use Drupal\bootstrap\Utility\Element;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * @BootstrapForm("node_preview_form_select")
 */
class NodePreviewFormSelect extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, $form_id = NULL) {
    $e = Element::create($form, $form_state);
    $e->addClass(['form-inline', 'bg-info', 'text-center', 'clearfix']);

    // Backlink.
    $options = $e->backlink->getProperty('options', []);

    $e->backlink->addClass(isset($options['attributes']['class']) ? $options['attributes']['class'] : []);
    $e->backlink->addClass(['btn', 'btn-info', 'pull-left']);
    $e->backlink->setButtonSize();
    $e->backlink->setIcon(Bootstrap::glyphicon('chevron-left'));

    // Ensure the UUID is set.
    if ($uuid = $e->uuid->getProperty('value')) {
      $options['query'] = ['uuid' => $uuid];
    }

    // Override the options attributes.
    $options['attributes'] = $e->backlink->getAttributes()->getArrayCopy();

    $e->backlink->setProperty('options', $options);


    // View mode.
    $e->view_mode->addClass('pull-right', $e::WRAPPER);
  }

}
