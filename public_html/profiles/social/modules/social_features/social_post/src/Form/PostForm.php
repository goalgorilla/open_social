<?php

/**
 * @file
 * Contains \Drupal\social_post\Form\PostForm.
 */

namespace Drupal\social_post\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Post edit forms.
 *
 * @ingroup social_post
 */
class PostForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\social_post\Entity\Post */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Post.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Post.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.post.canonical', ['post' => $entity->id()]);
  }

}
