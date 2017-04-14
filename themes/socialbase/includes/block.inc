<?php

use Drupal\block_content\Entity\BlockContent;
use Drupal\block\Entity\Block;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Override block theme suggestions.
 *
 * @param $suggestions
 * @param $variables
 */
function socialbase_theme_suggestions_block_alter(array &$suggestions, array $variables) {

  if (isset($variables['elements']['#base_plugin_id']) && $variables['elements']['#base_plugin_id'] == 'system_menu_block') {
    $menu_name = $variables['elements']['content']['#menu_name'];
    $suggestions[] = 'block__block_menu__' . $menu_name;
  }

  if (isset($variables['elements']['content']['#block_content'])) {
    $suggestions[] = 'block__' . $variables['elements']['content']['#block_content']->bundle();
  }

  $block_id = $variables['elements']['#derivative_plugin_id'];
  $blocks_id = array(
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
  );
  if (in_array($block_id, $blocks_id)) {
    $suggestions = array($variables['theme_hook_original'] . '__' . 'views_block__sidebar');
  }
}

/**
 * Implements hook_preprocess_block().
 * @param $variables
 */
function socialbase_preprocess_block(&$variables) {
  $profile_user_id = \Drupal::routeMatch()->getParameter('user');
  $block = \Drupal\block\Entity\Block::load($variables['elements']['#id']);

  // Fetch the current active (sub)theme.
  $theme = \Drupal::theme()->getActiveTheme();
  $prefix = '';
  // If socialbase is one of the basetheme, we need a prefix for block ids.
  if (array_key_exists('socialbase', $theme->getBaseThemes())) {
    $prefix = $theme->getName();
  }

  $variables['card'] = FALSE;
  $region = $block->getRegion();
  $regions_card = [
    'complementary',
    'complementary_top',
    'complementary_bottom',
    'content_top',
    'content_bottom'
  ];


  if (in_array($region, $regions_card)) {
    $variables['card'] = TRUE;

    if (array_key_exists('socialbase', $theme->getBaseThemes())) {
      $prefix = $theme->getName() . '_';
    }

    $block_buttons = [
      $prefix . 'event_add_block',
      $prefix . 'topic_add_block',
      $prefix . 'group_add_block',
      $prefix . 'group_add_event_block',
      $prefix . 'group_add_topic_block'
    ];

    if (in_array($variables['elements']['#id'], $block_buttons)) {
      $variables['card'] = FALSE;
    }

  }

  // Show group managers block in a card
  if ($variables['elements']['#derivative_plugin_id'] == 'group_managers-block_list_managers') {
    $variables['card'] = TRUE;
  }
  // for all platform_intro blocks we want them to appear as cards.
  if (isset($variables['elements']['content']['#block_content'])) {
    if ($variables['elements']['content']['#block_content']->bundle() == 'platform_intro') {
      $variables['card'] = TRUE;
    }
  }

  if (!is_null($profile_user_id) && is_object($profile_user_id)) {
    $profile_user_id = $profile_user_id->id();
  }
  $variables['content']['#attributes']['block'] = $variables['attributes']['id'];

  // Fix label for Views exposed filter blocks.
  if (!empty($variables['configuration']['views_label']) && empty($variables['configuration']['label'])) {
    $variables['label'] = $variables['configuration']['views_label'];
  }

  // Check if the block is a views exposed form filter, add condition to add
  // classes in twig file.
  if (isset($variables['content']['#form_id']) && $variables['content']['#form_id'] == 'views_exposed_form') {
    $variables['complementary'] = TRUE;
  }

  // Add search_block to main menu.
  if ($variables['elements']['#id'] == 'mainnavigation' || $variables['elements']['#id'] == $prefix . '_mainnavigation') {
    $block_id = \Drupal::moduleHandler()
      ->moduleExists('social_geolocation') ? 'geolocation_search_content_block_header' : 'search_content_block_header';
    $block = Block::load($block_id);

    if (!empty($block)) {
      $block_output = \Drupal::entityManager()
        ->getViewBuilder('block')
        ->view($block);

      $variables['content']['links']['search_block'] = $block_output;
    }
  }

  // Preprocess search block header.

  if (isset($variables['content']['search_form'])) {
    $variables['content']['search_form']['#attributes']['role'] = 'search';
    $variables['content']['search_form']['actions']['submit']['#is_button'] = FALSE;
    $variables['content']['search_form']['actions']['#addsearchicon'] = TRUE;
    $variables['content']['search_form']['actions']['submit']['#addsearchicon'] = TRUE;

    if ($region == 'hero') {
      $variables['content']['search_form']['#attributes']['class'][] = 'hero-form';
      $variables['content']['search_form']['#region'] = 'hero';
    }
    else {
      $variables['content']['search_form']['#attributes']['class'][] = 'navbar-form';
    }
  }

  // Add Group ID for "See all groups link".
  if ($variables['attributes']['id'] === 'block-views-block-group-members-block-newest-members') {
    $group = \Drupal::routeMatch()->getParameter('group');
    $variables['group_id'] = $group->id();
  }

  // Add User ID for "See all link".
  if (
    $variables['attributes']['id'] === 'block-views-block-events-block-events-on-profile' ||
    $variables['attributes']['id'] === 'block-views-block-topics-block-user-topics' ||
    $variables['attributes']['id'] === 'block-views-block-groups-block-user-groups'
  ) {
    $profile_user_id = \Drupal::routeMatch()->getParameter('user');
    if (!is_null($profile_user_id) && is_object($profile_user_id)) {
      $profile_user_id = $profile_user_id->id();
    }
    $variables['profile_user_id'] = $profile_user_id;
  }
  // AN Homepage block.
  if (isset($variables['elements']['content']['#block_content'])) {
    if ($variables['elements']['content']['#block_content']->bundle() == 'hero_call_to_action_block') {

      $imageitem = $variables['content']['field_hero_image'][0]['#item']->getEntity();
      $imagestyle = $variables['content']['field_hero_image'][0]['#image_style'];
      $entity = BlockContent::load($imageitem->id());
      $file_id = $entity->get('field_hero_image')->target_id;

      // If it's an existing file.
      if ($file = File::load($file_id)) {
        // Style and set it in the content.
        $styled_image_url = ImageStyle::load($imagestyle)
          ->buildUrl($file->getFileUri());
        $variables['image_url'] = $styled_image_url;

        // Add extra class.
        $variables['has_image'] = TRUE;

        // Remove the origingal.
        unset($variables['content']['field_hero_image']);

      }
    }
  }
}
