<?php

namespace Drupal\socialbase\Plugin\Alter;

use Drupal\Component\Utility\Html;
use Drupal\bootstrap\Plugin\Alter\ThemeSuggestions as BaseThemeSuggestions;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements hook_theme_suggestions_alter().
 *
 * @ingroup plugins_alter
 *
 * @BootstrapAlter("theme_suggestions")
 */
class ThemeSuggestions extends BaseThemeSuggestions implements ContainerFactoryPluginInterface {

  /**
   * Route Match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $config;

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The theme manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected ThemeManagerInterface $themeManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    RouteMatchInterface $route_match,
    ConfigFactoryInterface $config,
    AccountProxyInterface $account_proxy,
    ThemeManagerInterface $theme_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->config = $config;
    $this->currentUser = $account_proxy;
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('theme.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function alter(&$suggestions, &$context1 = NULL, &$hook = NULL): void {
    parent::alter($suggestions, $context1, $hook);

    $variables = $this->variables;

    switch ($hook) {

      case 'comment':

        if (
          function_exists('_social_dashboard_is_activity_stream_on_dashboard') &&
          _social_dashboard_is_activity_stream_on_dashboard() &&
          $variables['elements']['#view_mode'] === 'activity_comment'
        ) {
          $node = $this->routeMatch->getParameter('node');
          /** @var \Drupal\layout_builder\SectionStorageInterface $section_storage */
          $section_storage = $this->routeMatch->getParameter('section_storage');
          $bundle = is_null($node) ? $section_storage->getContextValues()['entity']->bundle() : $node->bundle();

          $suggestions[] = 'comment__' . $variables['elements']['#view_mode'] . '__' . $bundle;
        }

        break;

      case 'block':

        if (isset($variables['elements']['#base_plugin_id'])) {
          switch ($variables['elements']['#base_plugin_id']) {
            case 'system_menu_block':
              $menu_name = $variables['elements']['content']['#menu_name'];
              $suggestions[] = 'block__block_menu__' . $menu_name;
              break;

            case 'system_main_block':
              if ($this->routeMatch->getRouteName() === 'social_album.post') {
                $suggestions[] = 'block__social_post';
              }
              break;
          }
        }

        if (isset($variables['elements']['content']['#block_content'])) {
          // Keep the theme suggestion before the most specific plugin based
          // suggestion. This allows cases like layout builder blocks to take
          // precedence over our generic theme based use of the block.
          // See block_theme_suggestions_block() for how tis is constructed.
          $parts = explode(':', $variables['elements']['#plugin_id']);
          $insert_before = 'block__' . implode(
            '__',
            array_map(
              static fn($part): string => str_replace('-', '_', $part),
              $parts
            )
          );
          $new_suggestion = 'block__' . $variables['elements']['content']['#block_content']->bundle();
          array_splice($suggestions, array_search($insert_before, $suggestions, TRUE), 0, $new_suggestion);
        }

        if (isset($variables['elements']['content']['#lazy_builder']) && $variables['elements']['content']['#lazy_builder'][0] === 'social_content_block.content_builder:build') {
          // Add a block--block-type suggestion just above the layout builder so
          // it can be shared with other places the block is shown. This works
          // for properly designed blocks and gives plenty of opportunities for
          // misbehaving blocks.
          $block_content_bundle = $variables['elements']['content']['#lazy_builder'][1][2];
          $new_suggestion = 'block__' . $block_content_bundle;
          $insert_before = 'block__inline_block__' . $block_content_bundle;
          array_splice($suggestions, array_search($insert_before, $suggestions, TRUE), 0, $new_suggestion);
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
          $theme = $this->themeManager->getActiveTheme()->getName();
          $name = 'data_policy_page_title_block';

          if ($variables['elements']['#id'] == $theme . '_' . $name) {
            $suggestions[] = $variables['theme_hook_original'] . '__' . $name;
          }
        }

        break;

      case 'big_pipe_interface_preview':

        if (is_array($context1['arguments'])) {
          $arguments = preg_replace('/[^a-zA-Z0-9]/', '_', $context1['arguments']);
          foreach ($arguments as $argument) {
            // This is the main stream activity block.
            if (str_contains($argument, 'views_block__activity_stream')) {
              $suggestions[] = 'big_pipe_interface_preview__views_block__activity_stream';
            }
            // Generic theme suggestion for big-pipe.
            else if (!empty($argument) && $argument != 'full') {
              $suggestions[] = 'big_pipe_interface_preview' . '__' . str_replace('socialblue_', '', $argument);
            }
          }
        }

        break;

      case 'confirm_form':

        if (isset($variables['form']['#form_id']) && $variables['form']['#form_id'] == 'data_policy_data_policy_revision_revert_confirm') {
          $suggestions[] = $variables['theme_hook_original'] . '__modal';
        }

        break;

      case 'container':

        if (isset($variables['element']['#id']) && $variables['element']['#id'] == 'edit-field-post-image-wrapper') {
          $suggestions[] = 'container__post_image';
        }

        break;

      case 'details':
        $suggestions[] = 'details__plain';

        if (in_array('image-data__crop-wrapper', $variables['element']['#attributes']['class'])) {
          $suggestions[] = 'details__crop';
        }

        // Template suggestion for upload attachments in comments.
        if (isset($variables['element']['#id']) && strpos($variables['element']['#id'], 'edit-group-add-attachment') === 0) {
          $suggestions[] = 'details__comment';
        }

        // Template suggestion for upload attachments in comments.
        if (isset($variables['element']['#attributes']['class']) && in_array('social-collapsible-fieldset', $variables['element']['#attributes']['class'])) {
          $suggestions[] = 'details__collapsible';
        }

        // Template suggestion for FieldGroupFormatter "details_card".
        if (($variables['element']['#card'] ?? FALSE)) {
          $suggestions[] = 'details__card';
        }

        // Template suggestion for FieldGroupFormatter "details_header_card".
        if (($variables['element']['#header_card'] ?? FALSE)) {
          $suggestions[] = 'details__header_card';
        }

        break;

      case 'field':
        // Add the view mode to the field theme suggestion since it matters for
        // profiles.
        if (isset($context1['element']['#entity_type'], $context1['element']['#bundle'], $context1['element']['#view_mode'])) {
          $view_mode = $context1['element']['#view_mode'];
          $suggestion = $hook . "__" . $context1['element']['#entity_type'] . "__" . $context1['element']['#bundle'];
          $idx = array_search($suggestion, $suggestions, TRUE);
          if (is_int($idx)) {
            // Insert after the original suggestion.
            array_splice($suggestions, $idx + 1, 0, $suggestion . "__" . $view_mode);
          }
        }

        break;

      case 'file_link':
        // For the new Social Comment we need a different theme hook suggestion.
        if ($this->config->get('social_comment_upload.settings')
          ->get('allow_upload_comments')) {
          $file = $variables['file'];

          // For comments in activities we show the amount of attachments.
          if (isset($file->_referringItem) && $file->_referringItem !== NULL) {
            /** @var \Drupal\file\Plugin\Field\FieldType\FileItem $item  */
            $item = $file->_referringItem;
            $name = $item->getFieldDefinition()->getName();
            // For field comment files we add a new suggestion.
            if ($name === 'field_comment_files') {
              $suggestions[] = 'file_link__comment';
            }
          }
        }

        // Get the route name for file links.
        $route_name = $this->routeMatch->getRouteName();

        // Ensure that it is file.
        if (
          !isset($context1['file']) ||
          !($context1['file'] instanceof File)
        ) {
          return;
        }

        /** @var \Drupal\file\Entity\File $c_file */
        $c_file = $context1['file'];

        // If the file link is part of a node field, suggest another template.
        if ($route_name == 'entity.node.canonical') {
          $file_id = $c_file->id();
          $node = $this->routeMatch->getParameter('node');
          // We do not know the name of the file fields. These can be custom.
          $field_definitions = $node->getFieldDefinitions();

          // Loop over all fields and target only file fields.
          foreach ($field_definitions as $field_name => $field_definition) {
            /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
            if ($field_definition->getType() === 'file') {
              $files = $node->get($field_name)->getValue();
              foreach ($files as $file) {
                if ($file['target_id'] === $file_id) {
                  $suggestions[] = 'file_link__card';
                  break 2;
                }
              }
            }
          }
        }
        // If the file link is part of a group field, suggest another template.
        if ($route_name === 'entity.group.canonical') {
          $suggestions[] = 'file_link__card';
        }

        break;

      case 'form':

        // Alter comment form.
        if ($variables['element']['#form_id'] == 'comment_comment_form') {
          if ($this->routeMatch->getRouteName() === 'entity.comment.edit_form') {
            $suggestions = [$variables['theme_hook_original'] . '__' . 'comment_edit'];
          }
          else {
            $suggestions = [$variables['theme_hook_original'] . '__' . 'comment'];
          }
        }

        if ($variables['element']['#form_id'] == 'comment_post_comment_form') {
          if ($this->routeMatch->getRouteName() === 'entity.comment.edit_form') {
            $suggestions = [$variables['theme_hook_original'] . '__' . 'comment_edit'];
          }
          else {
            $suggestions[] = $variables['theme_hook_original'] . '__comment';
          }
        }

        // Distinguish message create form from thread form.
        if ($variables['element']['#form_id'] == 'private_message_add_form') {
          if ($this->routeMatch->getRouteName() === 'entity.private_message_thread.canonical') {
            $suggestions = [$variables['theme_hook_original'] . '__' . 'private_message_thread'];
          }
          else {
            $suggestions = [$variables['theme_hook_original'] . '__' . 'private_message_create'];
          }
        }

        // Add templates for post add/edit forms.
        if ($variables['element']['#form_id'] == 'social_post_entity_form') {
          if ($this->routeMatch->getRouteName() === 'entity.post.edit_form') {
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
        $view = $variables['view'];
        if ($view instanceof ViewExecutable && $view->id() == 'who_liked_this_entity') {
          $suggestions[] = $variables['theme_hook_original'] . '__wholiked';
        }

        break;

      case 'profile':

        // Add an anonymous variant to all the default profile theme
        // suggestions.
        if ($this->currentUser->isAnonymous()) {
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
