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
 * @BootstrapForm("social_post_entity_form")
 */
class SocialAlbumPostForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function alterFormElement(Element $form, FormStateInterface $form_state, $form_id = NULL) {
    if ($form_state->get('form_display')->getOriginalMode() === 'first_in_album') {
      $form->actions->cancel->addClass(['btn', 'btn-default']);
    }
  }

}
