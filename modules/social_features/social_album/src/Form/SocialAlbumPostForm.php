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
   * The visibility options mapping.
   *
   * The associative array where keys are node options and values are the
   * corresponding post options.
   *
   * @see field.storage.node.field_content_visibility.yml
   * @see field.storage.post.field_visibility.yml
   */
  const VISIBILITY_MAPPING = [
    'public' => '1',
    'community' => '2',
    'group' => '3',
  ];

  /**
   * The node object or NULL.
   *
   * @var \Drupal\node\NodeInterface|null
   */
  protected $node;

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\node\NodeInterface|null $node
   *   (optional) The node entity object. Defaults to NULL.
   *
   * @return array
   *   The form structure.
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

  /**
   * {@inheritdoc}
   */
  protected function configureVisibilityField(array &$form, FormStateInterface $form_state) {
    parent::configureVisibilityField($form, $form_state);

    if ($this->node) {
      $field = &$form['field_visibility']['widget'][0];
      $value = self::VISIBILITY_MAPPING[$this->node->field_content_visibility->value];

      if (isset($field['#options'][$value])) {
        $field['#default_value'] = $value;
      }

      $field['#edit_mode'] = TRUE;
    }
  }

}
