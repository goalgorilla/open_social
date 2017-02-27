<?php

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Implements template_preprocess_form().
 * @param $variables
 */
function socialbase_preprocess_form(&$variables) {

  $element = $variables['element'];

  // If this is a search content form set a variable for twig
  if ($element['#form_id'] === 'search_content_form' || $element['#form_id'] === 'search_hero_form' || $element['#form_id'] === 'geolocation_search_form' || $element['#form_id'] === 'geolocation_search_content_form') {
    $variables['is_search_form'] = TRUE;
  }

  // Add the custom search autocomplete to search users for now only.
  if ($element['#form_id'] === 'search_users_form') {
    $variables['#attached']['library'][] = 'socialbase/form--search-autocomplete';
  }

  // We have set the region via propress block, now pass this variable on to be used in twig
  // In twig we can distinguish the navbar form from the hero form as they both have the same ID's.
  if (isset($element['#region'])) {
    if ($element['#region'] === 'hero') {
      $variables['in_hero_region'] = TRUE;
    }
  }

  $variables['attributes']['class'][] = 'clearfix';

  if ($element['#form_id'] === 'comment_comment_form' || $element['#form_id'] === 'comment_post_comment_form') {
    $current_user = \Drupal::currentUser();
    if ($current_user) {
      $storage = \Drupal::entityTypeManager()->getStorage('profile');
      if (!empty($storage)) {
        $user_profile = $storage->loadByUser($current_user, 'profile');
        if ($user_profile) {
          $content = \Drupal::entityTypeManager()
            ->getViewBuilder('profile')
            ->view($user_profile, 'compact');
          $variables['current_user_picture'] = $content;
        }
      }
    }

    // Comment edit form.
    if (\Drupal::routeMatch()->getRouteName() === 'entity.comment.edit_form') {
      $comment = \Drupal::routeMatch()->getParameter('comment');

      if (is_object($comment)) {
        // Display comment created date in format 'time ago'.
        $created_time_ago = \Drupal::service('date.formatter')
          ->formatTimeDiffSince($comment->getCreatedTime(), array('granularity' => 1, 'return_as_object' => TRUE));

        $submitted = t('@time ago', array('@time' => $created_time_ago->getString()));
        $variables['submitted'] = Link::fromTextAndUrl($submitted, $comment->urlInfo('canonical'));
        $variables['#cache']['max-age'] = $created_time_ago->getMaxAge();


        // Display author information.
        $account = $comment->getOwner();
        if ($account) {
          // Author profile picture.
          $storage = \Drupal::entityTypeManager()->getStorage('profile');
          if (!empty($storage)) {
            $user_profile = $storage->loadByUser($account, 'profile');
            if ($user_profile) {
              $content = \Drupal::entityTypeManager()
                ->getViewBuilder('profile')
                ->view($user_profile, 'compact');
              $variables['author_picture'] = $content;
            }
          }

          // Author name.
          $username = array(
            '#theme' => 'username',
            '#account' => $account,
          );
          $variables['author'] = drupal_render($username);
        }
      }

    }

  }

  if ($element['#form_id'] === 'social_post_entity_form') {

    if (\Drupal::routeMatch()->getRouteName() === 'entity.post.edit_form') {
      $post_id = $element['#post_id'];
      /** @var \Drupal\social_post\Entity\Post $post */
      $post = entity_load('post', $post_id);

      $form['#post_id'] = $post->id();
      // Display post created date in format 'time ago'.
      $created_time_ago = \Drupal::service('date.formatter')
        ->formatTimeDiffSince($post->getCreatedTime(), array('granularity' => 1, 'return_as_object' => TRUE));
      $date = t('%time ago', array('%time' => $created_time_ago->getString()));
      $variables['date']['#markup'] = $date;
      $variables['#cache']['max-age'] = $created_time_ago->getMaxAge();


      // To change user picture settings (e.g. image style), edit the 'compact'
      // view mode on the User entity. Note that the 'compact' view mode might
      // not be configured, so remember to always check the theme setting first.
      $account = $post->getOwner();
      if ($account) {
        $author_name = $account->getDisplayName();
        $variables['author_name']['#markup'] = $author_name;

        $storage = \Drupal::entityTypeManager()->getStorage('profile');
        if (!empty($storage)) {
          $user_profile = $storage->loadByUser($account, 'profile');
          if ($user_profile) {
            $content = \Drupal::entityTypeManager()
              ->getViewBuilder('profile')
              ->view($user_profile, 'compact');
            $variables['author_picture'] = $content;
          }
        }
      }
    }
  }

  if ($element['#form_id'] === 'social_user_password_form') {
    $form['mail']['#prefix'] = '<div class="help-block">';
    $form['mail']['#suffix'] = '</div>';
  }

}

/**
 * Implements hook_form_FORM_ID_alter().
 * @param $form
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 * @param $form_id
 */
function socialbase_form_social_post_entity_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $form['actions']['#inline'] = TRUE;
  $form['field_visibility']['#inline'] = TRUE;

  // Set an extra wrapper to display the dropdown and forms actions inline.
  $form['field_visibility']['#prefix'] = '<div class="form-group-inline form-group-inline--right">';
  $form['actions']['#suffix'] = '</div>';

  // Give the `delete` button correct classes.
  if (isset($form['actions']['delete']['#type']) && $form['actions']['delete']['#type'] == 'link') {
    $form['actions']['delete']['#attributes']['class'][] = 'btn btn-flat';
  }

}

/**
 * Implements hook_form_FORM_ID_alter().
 * @param $form
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 * @param $form_id
 */
function socialbase_form_comment_post_comment_form_alter(&$form, FormStateInterface $form_state, $form_id) {

  if(isset($form['actions']['submit']['#button_type'])) {
    $form['actions']['submit']['#button_type'] = 'flat';
  }

}

/**
 * Implements hook_form_FORM_ID_alter().
 * @param $form
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 * @param $form_id
 */
function socialbase_form_comment_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $form['field_comment_body']['widget'][0]['#title_display'] = 'invisible';
  if(isset($form['actions']['submit']['#button_type'])) {
    $form['actions']['submit']['#button_type'] = 'primary';
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 * @param $form
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 * @param $form_id
 */
function socialbase_form_node_event_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if (isset($form['field_event_address'])) {
    $form['field_event_address']['widget'][0]['#title'] = '';
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 * @param $form
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 * @param $form_id
 */
function socialbase_form_profile_profile_edit_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if (isset($form['field_profile_address'])) {
    $form['field_profile_address']['widget'][0]['#title'] = '';
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 * @param $form
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 * @param $form_id
 */
function socialbase_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // These forms have a container with class form-action at the bottom and
  // we style it in a way that the primary/submit button is aligned right
  // Also we define the style of the buttons based on their function

  $default_forms = array(
    'user_form',
    'social_user_login_form',
    'user_register_form',
    'profile_profile_edit_form',
    'social_user_password_form',
    'user_cancel_form',
    'group_open_group_add_form',
    'group_open_group_edit_form',
    'node_event_form',
    'node_topic_form',
    'group_content_open_group-group_membership_group-join_form',
    'group_content_open_group-group_membership_group-leave_form',
    'group_content_open_group-group_membership_add_form',
    'group_content_open_group-group_membership_edit_form',
    'group_open_group_delete_form',
    'comment_comment_delete_form',
    'comment_post_comment_delete_form',
    'comment_comment_delete_form',
    'post_delete_form',
    'node_event_delete_form',
    'node_topic_delete_form',
  );


  if (in_array($form_id, $default_forms)) {
    $form['#attributes']['class'][] = 'form--default';

    if(isset($form['actions']['submit'])) {
      $form['actions']['submit']['#button_type'] = 'primary';
      $form['actions']['submit']['#button_level'] = 'raised';
    }

    if(isset($form['actions']['delete'])) {

      // Some `delete` buttons are not inputs but links
      if (isset($form['actions']['delete']['#type']) && $form['actions']['delete']['#type'] == 'link') {
        $form['actions']['delete']['#attributes']['class'][] = 'btn btn-flat';
      }  else {
        $form['actions']['delete']['#button_type'] = 'flat';
      }

    }

    if(isset($form['actions']['cancel'])) {

      // Some `cancel` buttons are not inputs but links
      if (isset($form['actions']['cancel']['#type']) &&  $form['actions']['cancel']['#type'] == 'link') {
        $form['actions']['cancel']['#attributes']['class'][] = 'btn btn-flat';
      }  else {
        $form['actions']['cancel']['#button_type'] = 'flat';
      }
    }
  }

  // Here we list all the forms that need to be wrapped in cards
  $add_card_to_forms = array(
    'group_content_open_group-group_membership_group-join_form',
    'group_content_open_group-group_membership_add_form',
    'group_content_open_group-group_membership_edit_form',
    'social_user_login_form',
    'user_register_form',
    'social_user_password_form',
  );

  if (in_array($form_id, $add_card_to_forms)) {
    $form['#attributes']['class'][] = 'card';
    $form['actions']['#prefix'] = '</div></div>';
  }


  /**
   * List all the confirmation forms here. We need to close the card before showing the actions.
   */

  $confirm_forms = array (
    'user_cancel_form',
    'post_delete_form',
    'node_event_delete_form',
    'node_topic_delete_form',
    'group_open_group_delete_form',
    'comment_comment_delete_form',
    'comment_post_comment_delete_form',
    'group_content_open_group-group_membership_group-leave_form',
  );

  if (in_array($form_id, $confirm_forms)) {
    $form['description']['#prefix'] = '<div class="clearfix">';
    $form['description']['#suffix'] = '</div></div></div>';
  }

  // Add extra classes to
  if($form_id == 'enroll_action_form') {
    $form['enroll_for_this_event']['#button_type'] = 'accent';
    $form['enroll_for_this_event']['#attributes']['class'][] = 'btn-lg';
  }

}

/**
 * Implements hook_form_BASE_ID_alter().
 * @param $form
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 * @param $form_id
 *
 * Set button types for input elements.
 * Note that these variables are read in hook_preprocess_input.
 *
 */
function socialbase_form_node_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $form['#attributes']['class'][] = 'form--default';

  // Delete is not an input element, so we must apply extra classes to anchor directly
  if(isset($form['actions']['delete'])) {
    $form['actions']['delete']['#attributes']['class'][] = 'btn btn-flat';
  }

  if(isset($form['actions']['preview'])) {
    $form['actions']['preview']['#button_type'] = 'flat';
  }
  if(isset($form['actions']['submit'])) {
    $form['actions']['submit']['#button_level'] = 'raised';
    $form['actions']['submit']['#button_type'] = 'primary';
  }
  if(isset($form['actions']['publish'])) {
    $form['actions']['publish']['#button_level'] = 'raised';
    $form['actions']['publish']['#button_type'] = 'primary';
  }
  if(isset($form['actions']['unpublish'])) {
    $form['actions']['unpublish']['#button_level'] = 'raised';
    $form['actions']['unpublish']['#button_type'] = 'primary';
  }

}

/**
 * Implements hook_theme_suggestions_form_alter().
 * @param array $suggestions
 * @param array $variables
 */
function socialbase_theme_suggestions_form_alter(array &$suggestions, array $variables) {

  // Alter comment form.
  if ($variables['element']['#form_id'] == 'comment_comment_form') {
    if (\Drupal::routeMatch()->getRouteName() === 'entity.comment.edit_form') {
      $suggestions = array($variables['theme_hook_original'] . '__' . 'comment_edit');
    }
    else {
      $suggestions = array($variables['theme_hook_original'] . '__' . 'comment');
    }
  }

  if ($variables['element']['#form_id'] == 'comment_post_comment_form') {
    if (\Drupal::routeMatch()->getRouteName() === 'entity.comment.edit_form') {
      $suggestions = array($variables['theme_hook_original'] . '__' . 'comment_edit');
    } else {
      $suggestions[] = $variables['theme_hook_original'] . '__comment';
    }
  }

  // Add templates for post add/edit forms.
  if ($variables['element']['#form_id'] == 'social_post_entity_form') {
    if (\Drupal::routeMatch()->getRouteName() === 'entity.post.edit_form') {
      $suggestions[] = $variables['theme_hook_original'] . '__post_edit';
    }
  }
}
