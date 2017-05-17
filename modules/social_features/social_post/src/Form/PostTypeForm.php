<?php

namespace Drupal\social_post\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PostTypeForm.
 *
 * @package Drupal\social_post\Form
 */
class PostTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $post_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $post_type->label(),
      '#description' => $this->t("Label for the Post type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $post_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\social_post\Entity\PostType::load',
      ],
      '#disabled' => !$post_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $post_type = $this->entity;
    $status = $post_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Post type.', [
          '%label' => $post_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Post type.', [
          '%label' => $post_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($post_type->toUrl('collection'));
  }

}
