<?php

namespace Drupal\socialbase\Plugin\Form;

use Drupal\bootstrap\Plugin\Form\FormBase;
use Drupal\bootstrap\Utility\Element;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * @ingroup plugins_form
 *
 * @BootstrapForm("post_photo_delete_image_form")
 */
class SocialAlbumImageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function alterFormElement(Element $form, FormStateInterface $form_state, $form_id = NULL) {
    $form->actions->cancel->addClass(['btn', 'btn-default']);
  }

}
