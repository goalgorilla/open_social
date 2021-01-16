<?php

namespace Drupal\socialbase\Plugin\Form;

use Drupal\bootstrap\Utility\Element;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * @ingroup plugins_form
 *
 * @BootstrapForm("social_post_entity_form")
 */
class SocialAlbumPostForm extends SocialAlbumImageForm {

  /**
   * {@inheritdoc}
   */
  public function alterFormElement(Element $form, FormStateInterface $form_state, $form_id = NULL) {
    if ($form_state->get('form_display')->getOriginalMode() === 'album') {
      parent::alterFormElement($form, $form_state, $form_id);
    }
  }

}
