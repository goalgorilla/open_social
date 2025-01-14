<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\block_content\Entity\BlockContent;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pre-processes variables for the "block" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("block")
 */
class Block extends PreprocessBase implements ContainerFactoryPluginInterface {

  /**
   * Route Match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHander;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

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
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    ThemeManagerInterface $theme_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->moduleHander = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('theme.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info): void {
    parent::preprocess($variables, $hook, $info);

    $region = '';

    // Blocks don't work well without an id. Unfortunately layout builder blocks
    // don't have one by default, so we generate one.
    if (empty($variables['elements']['#id']) && !empty($variables['content']['_layout_builder'])) {
      $region = '_LAYOUT_BUILDER_DO_NOT_CHANGE';
      $variables['elements']['#id'] = Html::getUniqueId('_LAYOUT_BUILDER_DO_NOT_CHANGE');
      $variables['attributes']['id'] = $variables['elements']['#id'];
    }

    // Early return because block missing ID, for example because
    // Rendered in panels display
    // https://www.drupal.org/node/2873726
    if (empty($variables['elements']['#id'])) {
      return;
    }

    // Find out what the active theme is first.
    $theme = $this->themeManager->getActiveTheme();

    $route_name = $this->routeMatch->getRouteName();

    // Get the region of a block.
    $block_entity = $this->entityTypeManager->getStorage('block')->load($variables['elements']['#id']);
    if ($block_entity) {
      $region = $block_entity->getRegion();
    }

    $prefix = '';
    // If socialbase is one of the basetheme, we need a prefix for block ids.
    if (array_key_exists('socialbase', $theme->getBaseThemeExtensions())) {
      $prefix = $theme->getName();
    }

    $variables['card'] = FALSE;

    $regions_card = [
      'complementary',
      'complementary_top',
      'complementary_bottom',
      'content_top',
      'content_bottom',
      '',
    ];

    if (in_array($region, $regions_card)) {
      $variables['card'] = TRUE;

      if (array_key_exists('socialbase', $theme->getBaseThemeExtensions())) {
        $prefix = $theme->getName() . '_';
      }

      $block_buttons = [
        $prefix . 'event_add_block',
        $prefix . 'topic_add_block',
        $prefix . 'group_add_block',
        $prefix . 'group_add_event_block',
        $prefix . 'group_add_topic_block',
        $prefix . 'add_data_policy_revision',
      ];

      if (in_array($variables['elements']['#id'], $block_buttons)) {
        $variables['card'] = FALSE;
      }

    }

    if (isset($variables['elements']['kpi_analytics'])) {
      $variables['card'] = TRUE;
    }

    // Wrap the main content block of some pages in a card element.
    if (isset($variables['elements']['#plugin_id']) && $variables['elements']['#plugin_id'] == 'system_main_block') {
      $route_names = [
        'entity.group_content.collection' => FALSE,
        'data_policy.data_policy' => FALSE,
        'social_gdpr.data_policy.revision' => TRUE,
        'social_gdpr.data_policy.revisions' => FALSE,
        'social_album.post' => TRUE,
      ];

      if (isset($route_names[$route_name])) {
        $variables['card'] = TRUE;

        if ($route_names[$route_name]) {
          $variables['attributes']['class'][] = 'card__body';
        }
      }
    }

    // Show group tags block in a card.
    if ($variables['elements']['#plugin_id'] === 'social_group_tags_block') {
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

    // Don't add attributes for blocks that use lazy builder.
    if (!isset($variables['content']['#lazy_builder'])) {
      $variables['content']['#attributes']['block'] = $variables['attributes']['id'];
    }

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
    if ($this->moduleHander->moduleExists('social_search') && ($variables['elements']['#id'] == 'mainnavigation' || $variables['elements']['#id'] == $prefix . '_mainnavigation')) {
      $block = $this->entityTypeManager->getStorage('block')->load('search_content_block_header');

      if (!empty($block)) {
        $block_output = $this->entityTypeManager
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
      if ($region == 'hero') {
        $variables['content']['search_form']['#attributes']['class'][] = 'hero-form';
        $variables['content']['search_form']['#region'] = 'hero';
        $variables['content']['search_form']['actions']['submit']['#addsearchicon'] = TRUE;
      }
      elseif ($region == 'content_top') {
        $variables['content']['search_form']['#region'] = 'content-top';
        $variables['content']['search_form']['search_input_content']['#attributes']['placeholder'] = $this->t('What are you looking for ?');
        $variables['content']['search_form']['search_input_content']['#attributes']['autocomplete'] = 'off';
      }
      else {
        $variables['content']['search_form']['#attributes']['class'][] = 'navbar-form';
      }
    }

    // Add Group ID for "See all groups link".
    if ($variables['attributes']['id'] === 'block-views-block-group-members-block-newest-members') {
      $group = $this->routeMatch->getParameter('group');
      $variables['group_id'] = $group->id();
    }

    // Add User ID for "See all link".
    if (
      $variables['attributes']['id'] === 'block-views-block-events-block-events-on-profile' ||
      $variables['attributes']['id'] === 'block-views-block-topics-block-user-topics' ||
      $variables['attributes']['id'] === 'block-views-block-groups-block-user-groups'
    ) {
      $profile_user_id = $this->routeMatch->getParameter('user');
      if (is_object($profile_user_id)) {
        $profile_user_id = $profile_user_id->id();
      }
      $variables['profile_user_id'] = $profile_user_id;
    }

    // AN Homepage block.
    if (isset($variables['elements']['content']['#block_content'])) {
      if ($variables['elements']['content']['#block_content']->bundle() == 'hero_call_to_action_block') {
        if (isset($variables['elements']['content']['field_hero_image']) && isset($variables['elements']['content']['field_hero_image'][0])) {
          $image_item = $variables['elements']['content']['field_hero_image'][0]['#item'];
          $file_id = NULL;

          if ($image_item instanceof ImageItem) {
            $block_content = $image_item->getEntity();
            if ($block_content instanceof BlockContent) {
              $file_id = $block_content->get('field_hero_image')->target_id;
            }
          }
          $image_style = $variables['elements']['content']['field_hero_image'][0]['#image_style'];

          // First filter out image_style,
          // So responsive image module doesn't break.
          if (isset($image_style)) {
            // If it's an existing file.
            if ($file = $this->entityTypeManager->getStorage('file')->load($file_id)) {
              // Style and set it in the content.
              $styled_image = $this->entityTypeManager->getStorage('image_style')->load($image_style);
              if ($styled_image instanceof ImageStyle) {
                $variables['image_url'] = $styled_image->buildUrl($file->getFileUri());
              }

              // Add extra class.
              $variables['has_image'] = TRUE;

              // Remove the original.
              unset($variables['content']['field_hero_image']);
            }

          }

        }

      }

    }

    // Remove our workaround ids so they aren't actually rendered.
    if ($region === '_LAYOUT_BUILDER_DO_NOT_CHANGE') {
      unset(
        $variables['elements']['#id'],
        $variables['attributes']['id']
      );
    }

  }

}
