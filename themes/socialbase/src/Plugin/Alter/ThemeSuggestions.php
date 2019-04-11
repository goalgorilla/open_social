<?php

namespace Drupal\socialbase\Plugin\Alter;

use Drupal\Component\Utility\Html;
use Drupal\bootstrap\Plugin\Alter\ThemeSuggestions as BaseThemeSuggestions;

/**
 * Implements hook_theme_suggestions_alter().
 *
 * @ingroup plugins_alter
 *
 * @BootstrapAlter("theme_suggestions")
 */
class ThemeSuggestions extends BaseThemeSuggestions {

  /**
   * {@inheritdoc}
   */
  public function alter(&$suggestions, &$context1 = NULL, &$hook = NULL) {
    parent::alter($suggestions, $context1, $hook);

    $variables = $this->variables;

    switch ($hook) {

      case 'block':

        if (isset($variables['elements']['#base_plugin_id']) && $variables['elements']['#base_plugin_id'] == 'system_menu_block') {
          $menu_name = $variables['elements']['content']['#menu_name'];
          $suggestions[] = 'block__block_menu__' . $menu_name;
        }

        if (isset($variables['elements']['content']['#block_content'])) {
          $suggestions[] = 'block__' . $variables['elements']['content']['#block_content']->bundle();
        }

        $block_id = $variables['elements']['#derivative_plugin_id'];
        $blocks_id = [
          'upcoming_events-block_my_upcoming_events',
          'upcoming_events-block_community_events',
          'latest_topics-block_latest_topics',
          'newest_groups-block_newest_groups',
          'newest_users-block_newest_users',
          'events-block_events_on_profile',
          'topics-block_user_topics',
          'groups-block_user_groups',
          'group_members-block_newest_members',
          'upcoming_events-upcoming_events_group',
          'latest_topics-group_topics_block',
        ];
        if (in_array($block_id, $blocks_id)) {
          $suggestions = [$variables['theme_hook_original'] . '__' . 'views_block__sidebar'];
        }

        if (isset($variables['elements']['kpi_analytics'])) {
          $suggestions = [$variables['theme_hook_original'] . '__' . 'charts'];
        }

        if (isset($variables['elements']['#id'])) {
          $theme = \Drupal::theme()->getActiveTheme()->getName();
          $name = 'data_policy_page_title_block';

          if ($variables['elements']['#id'] == $theme . '_' . $name) {
            $suggestions[] = $variables['theme_hook_original'] . '__' . $name;
          }
        };

        break;

      case 'confirm_form':

        if (isset($variables['form']['#form_id']) && $variables['form']['#form_id'] == 'data_policy_data_policy_revision_revert_confirm') {
          $suggestions[] = $variables['theme_hook_original'] . '__modal';
        }

        break;

      case 'container':

        if (isset($variables['element']['#id']) && $variables['element']['#id'] == 'edit-field-post-image-wrapper') {
          $suggestions[] = 'container__post_image';
        };

        break;

      case 'details':
        $suggestions[] = 'details__plain';

        if (in_array('image-data__crop-wrapper', $variables['element']['#attributes']['class'])) {
          $suggestions[] = 'details__crop';
        }

        // Template suggestion for upload attachments in comments.
        if (isset($variables['element']['#entity_type']) && $variables['element']['#entity_type'] == 'comment') {
          $suggestions[] = 'details__comment';
        }

        break;

      case 'file_link':
        // For the new Social Comment we need a different theme hook suggestion.
        if (\Drupal::config('social_comment_upload.settings')
          ->get('allow_upload_comments')) {
          $file = $variables['file'];

          // For comments in activities we show the amount of attachments.
          if ($file->_referringItem !== NULL) {
            /* @var $item \Drupal\file\Plugin\Field\FieldType\FileItem  */
            $item = $file->_referringItem;
            $name = $item->getFieldDefinition()->getName();
            // For field comment files we add a new suggestion.
            if ($name === 'field_comment_files') {
              $suggestions[] = 'file_link__comment';
            }
          }
        }

        // Get the route name for file links.
        $route_name = \Drupal::routeMatch()->getRouteName();

        // If the file link is part of a node field, suggest another template.
        if ($route_name == 'entity.node.canonical') {
          /** @var \Drupal\file\Entity\File $c_file */
          $c_file = $context1['file'];
          $file_id = $c_file->id();
          $node = \Drupal::routeMatch()->getParameter('node');
          // We do not know the name of the file fields. These can be custom.
          $field_definitions = $node->getFieldDefinitions();

          // Loop over all fields and target only file fields.
          foreach ($field_definitions as $field_name => $field_definition) {
            /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
            if ($field_definition->getType() == 'file') {
              $files = $node->get($field_name)->getValue();
              foreach ($files as $file) {
                if ($file['target_id'] == $file_id) {
                  $suggestions[] = 'file_link__card';
                  break 2;
                }
              }
            }
          }
        }
        // If the file link is part of a group field, suggest another template.
        if ($route_name == 'entity.group.canonical') {
          $suggestions[] = 'file_link__card';
        }

        break;

      case 'form':

        // Alter comment form.
        if ($variables['element']['#form_id'] == 'comment_comment_form') {
          if (\Drupal::routeMatch()->getRouteName() === 'entity.comment.edit_form') {
            $suggestions = [$variables['theme_hook_original'] . '__' . 'comment_edit'];
          }
          else {
            $suggestions = [$variables['theme_hook_original'] . '__' . 'comment'];
          }
        }

        if ($variables['element']['#form_id'] == 'comment_post_comment_form') {
          if (\Drupal::routeMatch()->getRouteName() === 'entity.comment.edit_form') {
            $suggestions = [$variables['theme_hook_original'] . '__' . 'comment_edit'];
          }
          else {
            $suggestions[] = $variables['theme_hook_original'] . '__comment';
          }
        }

        // Distinguish message create form from thread form.
        if ($variables['element']['#form_id'] == 'private_message_add_form') {
          if (\Drupal::routeMatch()->getRouteName() === 'entity.private_message_thread.canonical') {
            $suggestions = [$variables['theme_hook_original'] . '__' . 'private_message_thread'];
          }
          else {
            $suggestions = [$variables['theme_hook_original'] . '__' . 'private_message_create'];
          }
        }

        // Add templates for post add/edit forms.
        if ($variables['element']['#form_id'] == 'social_post_entity_form') {
          if (\Drupal::routeMatch()->getRouteName() === 'entity.post.edit_form') {
            $suggestions[] = $variables['theme_hook_original'] . '__post_edit';
          }
          else {
            $suggestions[] = $variables['theme_hook_original'] . '__post_create';
          }
        }

        break;

      case 'form_element':

        // Lets add the form element parent to the theme suggestions.
        if (isset($variables['element']['#parents'][0])) {
          $hook = Html::escape($variables['element']['#parents'][0]);
          $suggestions[] = $variables['theme_hook_original'] . '__' . $hook;
        }

        if (!empty($variables['element']['#attributes']['data-switch'])) {
          $suggestions[] = $variables['theme_hook_original'] . '__switch';
        }

        break;

      case 'form_element_label':

        if (isset($variables['element']['#id'])) {
          if (strpos($variables['element']['#id'], 'field-visibility') !== FALSE) {
            $suggestions[] = $variables['theme_hook_original'] . '__' . 'dropdown';
          }
        }

        if (isset($variables['element']['#switch']) && $variables['element']['#switch'] == TRUE) {
          $suggestions[] = $variables['theme_hook_original'] . '__switch';
        }

        break;

      case 'input':

        // Add the form element parent to the theme suggestions.
        if (isset($variables['element']['#id'])) {
          if (strpos($variables['element']['#id'], 'field-visibility') !== FALSE) {
            $suggestions[] = $variables['theme_hook_original'] . '__' . 'dropdown';
          }
        }

        if (isset($variables['element']['#comment_button'])) {
          $suggestions[] = 'input__button__comment';
        }

        break;

      case 'views_view':

        $view_id = $variables['view']->id();
        $display_id = $variables['view']->getDisplay()->display['id'];

        if (isset($display_id)) {

          if ($display_id == 'wholiked') {
            $suggestions[] = $variables['theme_hook_original'] . '__members_list';
          }

        }

        if (isset($view_id)) {

          if ($view_id == 'view_enrollments') {
            $suggestions[] = $variables['theme_hook_original'] . '__page';
          }

          if ($view_id == 'group_managers') {
            $suggestions[] = $variables['theme_hook_original'] . '__group_managers';
          }

          if ($view_id == 'activity_stream' || $view_id == 'activity_stream_profile' || $view_id == 'activity_stream_group') {
            $suggestions[] = $variables['theme_hook_original'] . '__stream';
          }

        }

        break;

      case 'views_view_fields':

        /** @var \Drupal\views\ViewExecutable $view */
        $view = $variables['view'];
        if (($view) && $view->id() == 'who_liked_this_entity') {
          $suggestions[] = $variables['theme_hook_original'] . '__wholiked';
        }

        break;

      case 'profile':

        // Add an anonymous variant to all the default profile theme
        // suggestions.
        if (\Drupal::currentUser()->isAnonymous()) {
          $default_suggestions = profile_theme_suggestions_profile($variables->getArrayCopy());

          foreach ($default_suggestions as $suggestion) {
            // Find the position of the original suggestion.
            $reference_pos = array_search($suggestion, $suggestions);

            $anonymous_suggestion = $suggestion . '__anonymous';

            // If we can't find the reference suggestion we just add it to the
            // most important spot in the suggestions list.
            if ($reference_pos === FALSE) {
              $suggestions[] = $anonymous_suggestion;
            }
            // Otherwise ensure that our anonymous version has the same level
            // of preference as the original suggestion it extends.
            else {
              array_splice($suggestions, $reference_pos + 1, 0, $anonymous_suggestion);
            }
          }
        }

        break;

    }

  }

}
