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

  public function getFormId() {
    return 'social_post_entity_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve the form display before it is overwritten in the parent.
    $display = $this->getFormDisplay($form_state);
    $form = parent::buildForm($form, $form_state);

    // Remove recipient option.
    // Only needed for 'private' permissions which we currently do not support.
    unset($form['field_visibility']['widget']['#options'][0]);

    $display_id = $display->get('id');

    if ($display_id === 'post.post.default') {
      // Set default value to public.
      $form['field_visibility']['widget']['#default_value'][0] = "1";
    }
    elseif ($display_id === 'post.post.profile') {
      // Remove public option from options.
      unset($form['field_visibility']['widget']['#options'][1]);
    }
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
