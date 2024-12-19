<?php

namespace Drupal\social_album\Form;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_post\Form\PostDeleteForm;

/**
 * Provides a form for deleting album post entities.
 *
 * @package Drupal\social_album\Form
 */
class SocialAlbumImageForm extends PostDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    unset($form['#title']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $index = 0;
    $form_state->setRedirectUrl($this->getRedirectUrl());

    if (!$fid = $form_state->get('fid')) {
      return;
    }

    /** @var \Drupal\social_post\Entity\PostInterface $entity */
    $entity = $this->getEntity();

    /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $field */
    $field = $entity->get('field_post_image');

    foreach ($field->getValue() as $index => $item) {
      if ($item['target_id'] === $fid) {
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
  public function getDescription(): TranslatableMarkup {
    return $this->t('Deleting this image will also delete it from the post it belongs to.');
  }

}
