<?php

/**
 * @file
 * The form inc file for the Social base theme.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Implements template_preprocess_form().
 */
function socialbase_preprocess_form(&$variables) {

  $element = $variables['element'];

  // If this is a search content form set a variable for twig.
  if ($element['#form_id'] === 'search_content_form' || $element['#form_id'] === 'search_hero_form' || $element['#form_id'] === 'geolocation_search_form' || $element['#form_id'] === 'geolocation_search_content_form') {
    $variables['is_search_form'] = TRUE;
  }

  // We have set the region via propress block, now pass this variable on
  // to be used in twig. In twig we can distinguish the nav bar form from
  // the hero form as they both have the same ID's.
  if (isset($element['#region'])) {
    if ($element['#region'] === 'hero') {
      $variables['in_hero_region'] = TRUE;
    }

    if ($element['#region'] === 'content-top') {
      $variables['in_content_top_region'] = TRUE;
    }
  }

  $variables['attributes']['class'][] = 'clearfix';

  if ($element['#form_id'] === 'comment_comment_form' || $element['#form_id'] === 'comment_post_comment_form' || $element['#form_id'] === 'private_message_add_form') {
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
          ->formatTimeDiffSince($comment->getCreatedTime(), ['granularity' => 1, 'return_as_object' => TRUE]);

        $submitted = t('@time ago', ['@time' => $created_time_ago->getString()]);
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
          $username = [
            '#theme' => 'username',
            '#account' => $account,
          ];
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

      // Display post created date in format 'time ago'.
      $created_time_ago = \Drupal::service('date.formatter')
        ->formatTimeDiffSince($post->getCreatedTime(), ['granularity' => 1, 'return_as_object' => TRUE]);
      $date = t('%time ago', ['%time' => $created_time_ago->getString()]);
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
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function socialbase_form_social_post_entity_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $form['actions']['#inline'] = TRUE;
  $form['field_visibility']['#inline'] = TRUE;

  // Give the `delete` button correct classes.
  if (isset($form['actions']['delete']['#type']) && $form['actions']['delete']['#type'] == 'link') {
    $form['actions']['delete']['#attributes']['class'][] = 'btn btn-flat';
  }

}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function socialbase_form_comment_post_comment_form_alter(&$form, FormStateInterface $form_state, $form_id) {

  if (isset($form['actions']['submit']['#button_type'])) {
    $form['actions']['submit']['#button_type'] = 'primary';
    $form['actions']['submit']['#comment_button'] = TRUE;
  }

  // Set a placeholder text to guide the user.
  $form['field_comment_body']['widget'][0]['#placeholder'] = t('Write a comment...');

}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function socialbase_form_private_message_add_form_alter(&$form, FormStateInterface $form_state, $form_id) {

  foreach ($form['actions'] as $key => &$actions) {
    // Must be pretty sure it's a submit button.
    if (is_array($actions) && isset($actions['#submit']) && is_array($actions['#submit'])) {
      // Key must have submit in it.
      if (strpos($key, 'submit-') !== FALSE) {
        $actions['#button_type'] = 'flat';
        $actions['#comment_button'] = TRUE;
      }
    }
  }

}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function socialbase_form_comment_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $form['field_comment_body']['widget'][0]['#title_display'] = 'invisible';
  if (isset($form['actions']['submit']['#button_type'])) {
    $form['actions']['submit']['#button_type'] = 'primary';
    $form['actions']['submit']['#comment_button'] = TRUE;
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function socialbase_form_node_event_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if (isset($form['field_event_address'])) {
    $form['field_event_address']['widget'][0]['#title'] = '';
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function socialbase_form_profile_profile_edit_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if (isset($form['field_profile_address'])) {
    $form['field_profile_address']['widget'][0]['#title'] = '';
  }
}

/**
 * Implements hook_form_user_form_alter().
 */
function socialbase_form_user_form_alter(&$form) {
  if (isset($form['email_notifications'])) {
    $form['#attached']['library'][] = 'socialbase/form-horizontal';
    $form['timezone']['#attributes']['class'][] = 'form-horizontal';
  }
}

/**
 * Implements hook_form_alter().
 */
function socialbase_form_alter(&$form, FormStateInterface $form_state, $form_id) {

  // Client side validation is not consistent accross devices. Disable for now.
  if (!isset($form['#attributes']['novalidate'])) {
    $form['#attributes']['novalidate'] = 'novalidate';
  }

  // In the code above we disable HTML5 native validation,
  // for the following forms enable validating.
  // These forms only have required attributes.
  $validate_forms = [
    'private_message_add_form',
    'comment_post_comment_form',
    'social_post_entity_form',
  ];

  if (in_array($form_id, $validate_forms)) {
    unset($form['#attributes']['novalidate']);
  }

  $social_group_types = [
    'open_group',
    'closed_group',
    'public_group',
  ];
  \Drupal::moduleHandler()->alter('social_group_types', $social_group_types);

  $group_add_card_to_forms = [];
  $group_confirm_forms = [];
  $group_default_forms = [];
  $content_reporting_forms = [];

  foreach ($social_group_types as $social_group_type) {
    $group_add_card_to_forms[] = "group_content_{$social_group_type}-group_membership_group-join_form";
    $group_add_card_to_forms[] = "group_content_{$social_group_type}-group_membership_add_form";
    $group_add_card_to_forms[] = "group_content_{$social_group_type}-group_membership_edit_form";
    $group_confirm_forms[] = "group_content_{$social_group_type}-group_membership_group-leave_form";
    $group_confirm_forms[] = "group_content_{$social_group_type}-group_membership_delete_form";
    $group_confirm_forms[] = "group_{$social_group_type}_delete_form";
    $group_default_forms[] = "group_{$social_group_type}_add_form";
    $group_default_forms[] = "group_{$social_group_type}_edit_form";
  }

  // Add card classes to all the social_content_reporting forms if enabled.
  try {
    // Get all 'report_' flags.
    $report_types = \Drupal::service('social_content_report.content_report_service')->getReportFlagTypes();
    foreach ($report_types as $report_type) {
      $content_reporting_forms[] = 'flagging_' . $report_type . '_add_form';
      $content_reporting_forms[] = 'flagging_' . $report_type . '_edit_form';
    }
  }
  // If the service doesn't exist then we don't need to do anything.
  catch (ServiceNotFoundException $e) {
  }

  // These forms have a container with class form-action at the bottom and
  // we style it in a way that the primary/submit button is aligned right
  // Also we define the style of the buttons based on their function.
  $default_forms = array_merge(
    $group_add_card_to_forms,
    $group_confirm_forms,
    $group_default_forms,
    $content_reporting_forms,
    [
      'user_form',
      'user_pass_reset',
      'social_user_login_form',
      'user_register_form',
      'profile_profile_add_form',
      'profile_profile_edit_form',
      'social_user_password_form',
      'user_cancel_form',
      'social_group_add',
      'node_event_form',
      'node_topic_form',
      'comment_comment_delete_form',
      'comment_post_comment_delete_form',
      'comment_comment_delete_form',
      'post_delete_form',
      'post_photo_delete_form',
      'node_event_delete_form',
      'node_topic_delete_form',
      'profile_profile_delete_form',
      'private_message_thread_delete_form',
      'data_policy_edit_form',
      'data_policy_data_policy_agreement',
      'data_policy_data_policy_revision_edit',
      'event_an_enroll_form',
    ]
  );

  if (in_array($form_id, $default_forms)) {
    $form['#attributes']['class'][] = 'form--default';

    if (isset($form['actions']['submit'])) {
      $form['actions']['submit']['#button_type'] = 'primary';
      $form['actions']['submit']['#button_level'] = 'raised';
    }

    if (isset($form['actions']['delete'])) {

      // Some `delete` buttons are not inputs but links.
      if (isset($form['actions']['delete']['#type']) && $form['actions']['delete']['#type'] == 'link') {
        $form['actions']['delete']['#attributes']['class'][] = 'btn btn-flat';
      }
      else {
        $form['actions']['delete']['#button_type'] = 'flat';
      }

    }

    if (isset($form['actions']['cancel'])) {

      // Some `cancel` buttons are not inputs but links.
      if (isset($form['actions']['cancel']['#type']) &&  $form['actions']['cancel']['#type'] == 'link') {
        $form['actions']['cancel']['#attributes']['class'][] = 'btn btn-flat';
      }
      else {
        $form['actions']['cancel']['#button_type'] = 'flat';
      }
    }
  }

  // Here we list all the forms that need to be wrapped in cards.
  $add_card_to_forms = array_merge(
    $group_add_card_to_forms,
    [
      'event_an_enroll_form',
      'data_policy_edit_form',
      'data_policy_data_policy_agreement',
      'data_policy_data_policy_revision_edit',
      'views_bulk_operations_configure_action',
      'views_bulk_operations_confirm_action',
    ]
  );

  if (in_array($form_id, $add_card_to_forms)) {
    $form['#attributes']['class'][] = 'card';
    $form['actions']['#prefix'] = '</div></div>';
  }

  // The content reporting form also needs a card display except when rendered
  // in a dialog.
  if (in_array($form_id, $content_reporting_forms) && !\Drupal::request()->isXmlHttpRequest()) {
    $form['#attributes']['class'][] = 'card';
    $form['actions']['#prefix'] = '</div></div>';
  }

  if (\Drupal::routeMatch()->getRouteName() === 'private_message.private_message_create' && $form_id == 'private_message_add_form') {
    $form['#attributes']['class'][] = 'card';
    $form['#attributes']['class'][] = 'form--default';
    $form['actions']['#prefix'] = '</div></div>';

    if (isset($form['actions']['submit'])) {
      $form['actions']['submit']['#button_type'] = 'primary';
      $form['actions']['submit']['#button_level'] = 'raised';
    }
  }

  // List all the confirmation forms here. We need to close the card
  // before showing the actions.
  $confirm_forms = array_merge($group_confirm_forms, [
    'user_cancel_form',
    'post_delete_form',
    'post_photo_delete_form',
    'node_event_delete_form',
    'node_topic_delete_form',
    'comment_comment_delete_form',
    'comment_post_comment_delete_form',
    '_auth_login_notice_form',
    'private_message_thread_delete_form',
    'profile_profile_delete_form',
  ]);

  if (in_array($form_id, $confirm_forms)) {
    $form['description']['#prefix'] = '<div class="clearfix">';
    $form['description']['#suffix'] = '</div></div></div>';
  }

  // Add extra classes to.
  if ($form_id == 'enroll_action_form') {
    $form['enroll_for_this_event']['#button_type'] = 'accent';
    $form['enroll_for_this_event']['#attributes']['class'][] = 'btn-lg';
  }

  // Ensure the sign-up or login link is displayed as a nested card section.
  // Bootstrap already converted the fieldset itself to a card.
  if ($form_id === 'social_user_login_form') {
    $form['username_login']['sign-up-link']['#prefix'] = '<div class="card__nested-section">';
    $form['username_login']['sign-up-link']['#suffix'] = '</div>';
  }
  elseif ($form_id === 'user_register_form') {
    $form['account']['login-link']['#prefix'] = '<div class="card__nested-section">';
    $form['account']['login-link']['#suffix'] = '</div>';
  }
  elseif ($form_id === 'social_user_password_form') {
    $form['forgot']['sign-up-link']['#prefix'] = '<div class="card__nested-section">';
    $form['forgot']['sign-up-link']['#suffix'] = '</div>';
  }
}

/**
 * Implements hook_form_BASE_ID_alter().
 *
 * Set button types for input elements.
 * Note that these variables are read in hook_preprocess_input.
 */
function socialbase_form_node_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $form['#attributes']['class'][] = 'form--default';

  // Delete is not an input element, so we must apply extra classes
  // to anchor directly.
  $button_size = theme_get_setting('button_size');
  if (isset($form['actions']['delete'])) {
    $form['actions']['delete']['#attributes']['class'][] = 'btn btn-flat';

    if ($button_size === 'btn-xs') {
      $form['actions']['delete']['#attributes']['class'][] = 'btn-xs';
    }
    elseif ($button_size === 'btn-sm') {
      $form['actions']['delete']['#attributes']['class'][] = 'btn-sm';
    }
    elseif ($button_size === 'btn-lg') {
      $form['actions']['delete']['#attributes']['class'][] = 'btn-lg';
    }
  }

  // Unlock is not an input element, so we must apply extra classes
  // to anchor directly.
  if (isset($form['actions']['unlock'])) {
    $form['actions']['unlock']['#attributes']['class'][] = 'btn';
    $form['actions']['unlock']['#attributes']['class'][] = 'btn-flat';
  }

  if (isset($form['actions']['preview'])) {
    $form['actions']['preview']['#button_type'] = 'flat';
  }
  if (isset($form['actions']['submit'])) {
    $form['actions']['submit']['#button_level'] = 'raised';
    $form['actions']['submit']['#button_type'] = 'primary';
  }
  if (isset($form['actions']['publish'])) {
    $form['actions']['publish']['#button_level'] = 'raised';
    $form['actions']['publish']['#button_type'] = 'primary';
  }
  if (isset($form['actions']['unpublish'])) {
    $form['actions']['unpublish']['#button_level'] = 'raised';
    $form['actions']['unpublish']['#button_type'] = 'primary';
  }
}

/**
 * Implements hook_form_search_content_form_alter().
 */
function socialbase_form_search_content_form_alter(&$form) {
  $form['actions']['submit']['#icon'] = [
    '#type' => "inline_template",
    '#template' => '<svg class="icon--search"><use xlink:href="#icon-search"></use></svg>',
  ];
}
