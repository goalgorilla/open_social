<?php

namespace Drupal\socialbase\Plugin\Alter;

use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\bootstrap\Utility\Variables;
use Drupal\bootstrap\Plugin\Alter\ThemeSuggestions;

/**
 * Implements hook_theme_suggestions_alter().
 *
 * @ingroup plugins_alter
 *
 * @BootstrapAlter("theme_suggestions")
 */
class SocialBaseThemeSuggestions extends ThemeSuggestions {

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

        // Get the route name for file links.
        $route_name = \Drupal::routeMatch()->getRouteName();

        // If the file link is part of a node field, suggest another template.
        if ($route_name == 'entity.node.canonical') {
          $file_id = $context1['file']->id();
          $node = \Drupal::routeMatch()->getParameter('node');
          $files = $node->get('field_files')->getValue();
          foreach ($files as $file) {
            if ($file['target_id'] == $file_id) {
              $suggestions[] = 'file_link__card';
              break;
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
          $hook = HtmlUtility::escape($variables['element']['#parents'][0]);
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

    }

  }

}
