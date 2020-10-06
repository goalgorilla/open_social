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
      $form['#disable_inline_form_errors'] = TRUE;

      $form['field_album']['widget']['value']['#default_value'] = $node->id();
      $form['field_album']['#access'] = FALSE;

      $form['field_post_image']['widget'][0]['#required'] = TRUE;
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

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    if ($this->node) {
      $form_state->setRedirect('entity.node.canonical', [
        'node' => $this->node->id(),
      ]);
    }
  }

}
