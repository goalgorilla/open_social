<?php

namespace Drupal\social_demo;

use Drupal\Core\File\FileSystemInterface;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Asset\CssOptimizer;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\file\Entity\File;
use Drupal\file\FileStorageInterface;
use Drupal\social_font\Entity\Font;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DemoSystem.
 *
 * @package Drupal\social_demo
 */
abstract class DemoSystem extends DemoContent {

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockStorage;

  /**
   * The Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The file storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * The file storage.
   *
   * @var \Drupal\file\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * DemoComment constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DemoContentParserInterface $parser, EntityStorageInterface $block_storage, ConfigFactory $config_factory, FileStorageInterface $file_storage, FileSystemInterface $file_system) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->parser = $parser;
    $this->blockStorage = $block_storage;
    $this->configFactory = $config_factory;
    $this->fileStorage = $file_storage;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('social_demo.yaml_parser'),
      $container->get('entity_type.manager')->getStorage('block_content'),
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getStorage('file'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function createContent($generate = FALSE, $max = NULL) {
    // Fetch data from yml file.
    $data = $this->fetchData();
    if ($generate === TRUE) {
      $data = $this->scrambleData($data, $max);
    }

    // First let's load the active theme.
    $active_theme = \Drupal::theme()->getActiveTheme()->getName();

    // Site.
    if (isset($data['site'])) {
      $this->content['site'] = TRUE;
      // Get editable config.
      $config = $this->configFactory->getEditable('system.site');
      // Set the site name and save.
      $config->set('name', $data['site']['name'])->save();
    }

    // Homepage block.
    if (isset($data['homepage'])) {
      $this->content['homepage'] = TRUE;

      // This uuid can be used like this since it's defined
      // in the code as well (@see social_core.install).
      $block = $this->blockStorage->loadByProperties(['uuid' => '8bb9d4bb-f182-4afc-b138-8a4b802824e4']);
      $block = current($block);

      if ($block instanceof BlockContent) {

        $this->replaceAnBlock($block, $data['homepage']);
      }
    }

    // Theme settings.
    if (isset($data['theme'])) {
      $this->content['theme'] = TRUE;
      // Get theme settings.
      $config = $this->configFactory->getEditable($active_theme . '.settings');

      // Favicon.
      if (isset($data['theme']['favicon'])) {
        $favicon = [
          'mimetype' => $data['theme']['favicon']['mimetype'],
          'path' => $data['theme']['favicon']['path'],
          'url' => $data['theme']['favicon']['url'],
          'use_default' => FALSE,
        ];
        // And save it.
        $config->set('favicon', $favicon)->save();
      }

      // Logo.
      $logo = $this->fetchImage($data['theme']['logo']);
      // Must be a valid file.
      if ($logo instanceof File) {
        $theme_logo = [
          'path' => $logo->getFileUri(),
          'url' => file_create_url($logo->getFileUri()),
          'use_default' => FALSE,
        ];
        // Store the array.
        $config->set('logo', $theme_logo)->save();
      }
      // Font.
      $config->set('font_primary', $this->getOrCreateFont($data['theme']['font_primary']))->save();
      // Borders.
      $config->set('card_radius', $data['theme']['card_radius'])->save();
      $config->set('form_control_radius', $data['theme']['form_control_radius'])->save();
      $config->set('button_radius', $data['theme']['button_radius'])->save();

      // Get the colors.
      $color = $this->configFactory->getEditable('color.theme.' . $active_theme);
      // Set as a palette.
      $palette = [
        'brand-primary'  => $data['theme']['color_primary'],
        'brand-secondary'  => $data['theme']['color_secondary'],
        'brand-accent'  => $data['theme']['color_accents'],
        'brand-link'  => $data['theme']['color_link'],
        'navbar-bg' => $data['theme']['navbar-bg'],
        'navbar-text' => $data['theme']['navbar-text'],
        'navbar-active-bg' => $data['theme']['navbar-active-bg'],
        'navbar-active-text' => $data['theme']['navbar-active-text'],
        'navbar-sec-bg' => $data['theme']['navbar-sec-bg'],
        'navbar-sec-text' => $data['theme']['navbar-sec-text'],
      ];

      // Save the palette.
      $color->set('palette', $palette)->save();

      /*
       * The code below has been stolen from the color.module. This module makes
       * no use of services or any other way to make its code a bit more
       * re-usable. Therefore a rip/copy was needed.
       *
       * The code makes sure that the above enforced color configuration is
       * in fact applied.
       */

      // Define the library name(s).
      $libraries = [
        'assets/css/brand.css',
      ];

      $id = $active_theme . '-' . substr(hash('sha256', serialize($palette) . microtime()), 0, 8);
      $paths['color'] = 'public://color';
      $paths['target'] = $paths['color'] . '/' . $id;

      foreach ($paths as $path) {
        \Drupal::service('file_system')->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);
      }

      $paths['target'] = $paths['target'] . '/';
      $paths['id'] = $id;
      $paths['source'] = drupal_get_path('theme', $active_theme) . '/';
      $paths['files'] = $paths['map'] = [];

      $css = [];
      foreach ($libraries as $stylesheet) {
        // Build a temporary array with CSS files.
        $files = [];
        if (file_exists($paths['source'] . $stylesheet)) {
          $files[] = $stylesheet;
        }

        foreach ($files as $file) {
          $css_optimizer = new CssOptimizer();
          // Aggregate @imports recursively for each configured top level
          // CSS file without optimization.
          // Aggregation and optimization will be handled by
          // drupal_build_css_cache() only.
          $style = $css_optimizer->loadFile($paths['source'] . $file, FALSE);

          // Return the path to where this CSS file originated from, stripping
          // off the name of the file at the end of the path.
          $css_optimizer->rewriteFileURIBasePath = base_path() . dirname($paths['source'] . $file) . '/';

          // Prefix all paths within this CSS file, ignoring absolute paths.
          $style = preg_replace_callback('/url\([\'"]?(?![a-z]+:|\/+)([^\'")]+)[\'"]?\)/i', [$css_optimizer, 'rewriteFileURI'], $style);
          // Rewrite stylesheet with new colors.
          $style = _color_rewrite_stylesheet($active_theme, $info, $paths, $palette, $style);
          $base_file = $this->fileSystem->basename($file);
          $css[] = $paths['target'] . $base_file;

          _color_save_stylesheet($paths['target'] . $base_file, $style, $paths);
        }
      }

      // Maintain list of files.
      $color
        ->set('stylesheets', $css)
        ->set('files', $paths['files'])
        ->save();
    }

    // Return something.
    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntry(array $item) {
    // @todo Implement getEntry() method.
  }

  /**
   * Prepares data about an image.
   *
   * @param string $image
   *   The image by uuid.
   *
   * @return array
   *   Returns an array.
   */
  protected function fetchImage($image) {
    $value = NULL;
    $files = $this->fileStorage->loadByProperties([
      'uuid' => $image,
    ]);

    if ($files) {
      return current($files);
    }

    return $value;
  }

  /**
   * Get or create the font.
   *
   * @param string $fontName
   *   The font name.
   *
   * @return int|mixed|null|string
   *   Return the font.
   */
  private function getOrCreateFont($fontName) {
    /** @var \Drupal\social_font\Entity\Font $font_entities */
    foreach (Font::loadMultiple() as $font_entities) {
      if ($fontName == $font_entities->get('name')->value) {
        return $font_entities->id();
      }
    }

    // Ok, so it doesn't exist.
    /** @var \Drupal\social_font\Entity\Font $font */
    $font = Font::create([
      'name' => $fontName,
      'user_id' => 1,
      'created' => \Drupal::time()->getRequestTime(),
      'field_fallback' => '0',
    ]);
    $font->save();
    // Return the id.
    return $font->id();

  }

  /**
   * Function to replace the AN homepage Block.
   *
   * @param \Drupal\block_content\Entity\BlockContent $block
   *   The block.
   * @param array $data
   *   The data.
   */
  private function replaceAnBlock(BlockContent $block, array $data) {

    $block->field_text_block = [
      'value' => $data['textblock'],
      'format' => 'full_html',
    ];

    /** @var \Drupal\file\Entity\File $file */
    $block_image = $this->prepareImage($data['image'], 'Anonymous front page image homepage');
    // Insert is in the hero image field.
    $block->field_hero_image = $block_image;

    // Set the links.
    $action_links = [
      [
        'uri' => 'internal:' . $data['cta1']['url'],
        'title' => $data['cta1']['text'],
      ],
      [
        'uri' => 'internal:' . $data['cta2']['url'],
        'title' => $data['cta2']['text'],
      ],
    ];
    $itemList = new FieldItemList($block->field_call_to_action_link->getFieldDefinition());
    $itemList->setValue($action_links);
    $block->field_call_to_action_link = $itemList;
    $block->save();
  }

}
