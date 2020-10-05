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
   * The node object or NULL.
   *
   * @var \Drupal\node\NodeInterface|null
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $this->node = $node;

    $form = parent::buildForm($form, $form_state);

    if ($node) {
      $form['field_album']['widget']['value']['#default_value'] = $node->id();
      $form['field_album']['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $element = parent::actionsElement($form, $form_state);

    if ($this->node) {
      $element['cancel'] = $this->node->toLink($this->t('Go to album'))->toRenderable();
    }

    return $element;
  }

}
