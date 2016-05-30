<?php

/**
 * @file
 * Contains \Drupal\social_post\Form\PostForm.
 */

namespace Drupal\social_post\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
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
    if (isset($display)) {
      $this->setFormDisplay($display, $form_state);
    }
    else {
      $visibility_value = $this->entity->get('field_visibility')->value;
      $display_id = ($visibility_value === '0') ? 'post.post.profile' : 'post.post.default';
      $display = EntityFormDisplay::load($display_id);
      // Set the custom display in the form.
      $this->setFormDisplay($display, $form_state);
    }

    if (isset($display) && ($display_id = $display->get('id'))) {
      if ($display_id === 'post.post.default') {
        // Set default value to community.
        // Remove recipient option.
        // Only needed for 'private' permissions which we currently do not support.
        unset($form['field_visibility']['widget'][0]['#options'][0]);
        $form['field_visibility']['widget'][0]['#default_value'] = "2";
      }
      else {
        // Remove public option from options.
        $form['field_visibility']['widget'][0]['#default_value'] = "0";
        unset($form['field_visibility']['widget'][0]['#options'][1]);
        unset($form['field_visibility']['widget'][0]['#options'][2]);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $display = $this->getFormDisplay($form_state);

    if (isset($display) && ($display_id = $display->get('id'))) {
      if ($display_id === 'post.post.profile') {
        $account_profile = \Drupal::routeMatch()->getParameter('user');
        $this->entity->get('field_recipient_user')->setValue($account_profile);
      }
      elseif ($display_id === 'post.post.group') {
        $group = \Drupal::routeMatch()->getParameter('group');
        $this->entity->get('field_recipient_group')->setValue($group);
      }
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Post.', [
          '%label' => $this->entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Post.', [
          '%label' => $this->entity->label(),
        ]));
    }
  }

}
