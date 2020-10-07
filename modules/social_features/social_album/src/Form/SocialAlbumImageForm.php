<?php

namespace Drupal\social_album\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\social_post\Form\PostDeleteForm;

/**
 * Class SocialAlbumImageForm.
 *
 * @package Drupal\social_album\Form
 */
class SocialAlbumImageForm extends PostDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    unset($form['#title']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting this image will also delete it from the post it belongs to.');
  }

}
