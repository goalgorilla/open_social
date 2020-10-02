<?php

namespace Drupal\social_album\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\social_post\Form\PostForm;

/**
 * Class SocialAlbumPostForm.
 *
 * @package Drupal\social_album\Form
 */
class SocialAlbumPostForm extends PostForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $form = parent::buildForm($form, $form_state);

    if ($node) {
      $form['field_album']['widget']['value']['#default_value'] = $node->id();
      $form['field_album']['#access'] = FALSE;
    }

    return $form;
  }

}
