<?php

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

  /**
   * {@inheritdoc}
   */
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
    $form['#attached']['library'][] = 'social_post/keycode-submit';

    if (isset($form['field_visibility'])) {
      $form['#attached']['library'][] = 'social_post/visibility-settings';

      // Default is create/add mode.
      $form['field_visibility']['widget'][0]['#edit_mode'] = FALSE;

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
              unset($form['field_visibility']['widget'][0]['#options'][0]);
              $form['field_visibility']['widget'][0]['#default_value'] = "2";
              unset($form['field_visibility']['widget'][0]['#options'][3]);
          }
          else {
            // Remove public option from options.
            $form['field_visibility']['widget'][0]['#default_value'] = "0";
            unset($form['field_visibility']['widget'][0]['#options'][1]);
            unset($form['field_visibility']['widget'][0]['#options'][2]);

            $current_group = _social_group_get_current_group();
            if (!$current_group) {
              unset($form['field_visibility']['widget'][0]['#options'][3]);
            }
            else {
              $group_type_id = $current_group->getGroupType()->id();
              if ($group_type_id !== 'closed_group') {
                unset($form['field_visibility']['widget'][0]['#options'][3]);
              }
              else {
                unset($form['field_visibility']['widget'][0]['#options'][0]);
                $form['field_visibility']['widget'][0]['#default_value'] = "3";
              }
            }
          }
      }

      // Do some alterations on this form.
      if ($this->operation == 'edit') {
          /** @var \Drupal\social_post\Entity\Post $post */
          $post = $this->entity;
          $form['#post_id'] = $post->id();

          // In edit mode we don't want people to actually change visibility setting
          // of the post.
          if ($current_value = $this->entity->get('field_visibility')->value) {
              // We set the default value.
              $form['field_visibility']['widget'][0]['#default_value'] = $current_value;
          }

          // Unset the other options, because we do not want to be able to change
          // it but we do want to use the button for informing the user.
          foreach ($form['field_visibility']['widget'][0]['#options'] as $key => $option) {
              if ($option['value'] != $form['field_visibility']['widget'][0]['#default_value']) {
                  unset($form['field_visibility']['widget'][0]['#options'][$key]);
              }
          }

          // Set button to disabled in our template, users have no option anyway.
          $form['field_visibility']['widget'][0]['#edit_mode'] = TRUE;
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
        drupal_set_message($this->t('Your post %label has been posted.', [
          '%label' => $this->entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Your post %label has been saved.', [
          '%label' => $this->entity->label(),
        ]));
    }
  }

}
