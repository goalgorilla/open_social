<?php

namespace Drupal\social_album\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\social_post\Entity\PostInterface;
use Drupal\social_post\Form\PostDeleteForm;

/**
 * Class SocialAlbumImageForm.
 *
 * @package Drupal\social_album\Form
 */
class SocialAlbumImageForm extends PostDeleteForm {

  /**
   * The file entity ID.
   *
   * @var int|null
   */
  protected $fid = NULL;

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\node\NodeInterface|null $node
   *   (optional) The node entity object. Defaults to NULL.
   * @param \Drupal\social_post\Entity\PostInterface|null $post
   *   (optional) The post entity object. Defaults to NULL.
   * @param int|null $fid
   *   (optional) The file entity ID. Defaults to NULL.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    NodeInterface $node = NULL,
    PostInterface $post = NULL,
    $fid = NULL
  ) {
    $this->fid = $fid;

    $form = parent::buildForm($form, $form_state);

    unset($form['#title']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->getRedirectUrl());

    if (!$this->fid) {
      return;
    }

    /** @var \Drupal\social_post\Entity\PostInterface $entity */
    $entity = $this->getEntity();

    /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $field */
    $field = $entity->field_post_image;

    foreach ($field->getValue() as $index => $item) {
      if ($item['target_id'] === $this->fid) {
        break;
      }
    }

    $field->removeItem($index);

    if ($field->isEmpty()) {
      $entity->set('field_album', NULL);
    }

    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting this image will also delete it from the post it belongs to.');
  }

}
