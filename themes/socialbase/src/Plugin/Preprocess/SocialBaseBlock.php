<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block\Entity\Block;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Pre-processes variables for the "block" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("block")
 */
class SocialBaseBlock extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    // Early return because block missing ID, for example because
    // Rendered in panels display
    // https://www.drupal.org/node/2873726
    if (empty($variables['elements']['#id'])) {
      return;
    }

    // Find out what the active theme is first.
    $theme = \Drupal::theme()->getActiveTheme();

    $route_name = \Drupal::routeMatch()->getRouteName();

    // Get the region of a block.
    $region = Block::load($variables['elements']['#id'])->getRegion();

    $prefix = '';
    // If socialbase is one of the basetheme, we need a prefix for block ids.
    if (array_key_exists('socialbase', $theme->getBaseThemes())) {
      $prefix = $theme->getName();
    }

    $variables['card'] = FALSE;

    $regions_card = [
      'complementary',
      'complementary_top',
      'complementary_bottom',
      'content_top',
      'content_bottom',
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
        $prefix . 'group_add_topic_block',
      ];

      if (in_array($variables['elements']['#id'], $block_buttons)) {
        $variables['card'] = FALSE;
      }

    }

    if (isset($variables['elements']['kpi_analytics'])) {
      $variables['card'] = TRUE;
    }

    // Wrap the group/membership table in a card element.
    if (isset($variables['elements']['#plugin_id']) && $variables['elements']['#plugin_id'] == 'system_main_block' && $route_name == 'entity.group_content.collection') {
      $variables['card'] = TRUE;
    }

    // Show group managers block in a card.
    if ($variables['elements']['#derivative_plugin_id'] == 'group_managers-block_list_managers') {
      $variables['card'] = TRUE;
    }

    // For all platform_intro blocks we want them to appear as cards.
    if (isset($variables['elements']['content']['#block_content'])) {
      if ($variables['elements']['content']['#block_content']->bundle() == 'platform_intro') {
        $variables['card'] = TRUE;
      }
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

        if (isset($variables['elements']['content']['field_hero_image'])) {
          $imageitem = $variables['elements']['content']['field_hero_image'][0]['#item']->getEntity();
          $imagestyle = $variables['elements']['content']['field_hero_image'][0]['#image_style'];
          $entity = BlockContent::load($imageitem->id());
          $file_id = $entity->get('field_hero_image')->target_id;

          // First filter out image_style,
          // So responsive image module doesn't break.
          if (isset($imagestyle)) {
            // If it's an existing file.
            if ($file = File::load($file_id)) {
              // Style and set it in the content.
              $styled_image_url = ImageStyle::load($imagestyle)
                ->buildUrl($file->getFileUri());
              $variables['image_url'] = $styled_image_url;

              // Add extra class.
              $variables['has_image'] = TRUE;

              // Remove the original.
              unset($variables['content']['field_hero_image']);
            }

          }

        }

      }

    }

  }

}
