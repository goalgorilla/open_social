<?php

namespace Drupal\social_demo;

use Drupal\block_content\Entity\BlockContent;
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
   * DemoComment constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DemoContentParserInterface $parser, EntityStorageInterface $block_storage, ConfigFactory $config_factory, FileStorageInterface $file_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->parser = $parser;
    $this->blockStorage = $block_storage;
    $this->configFactory = $config_factory;
    $this->fileStorage = $file_storage;
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
      $container->get('entity.manager')->getStorage('block_content'),
      $container->get('config.factory'),
      $container->get('entity.manager')->getStorage('file')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function createContent() {
    // Fetch data from yml file.
    $data = $this->fetchData();

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
      $logo = $this->preparePicture($data['theme']['logo']);
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
      // Border radius.
      $config->set('border_radius', $data['theme']['border_radius'])->save();

      // Get the colors.
      $color = $this->configFactory->getEditable('color.theme.' . $active_theme);
      // Set as a palette.
      $palette = [
        'brand-bg-primary' => $data['theme']['color_primary'],
        'brand-bg-secondary' => $data['theme']['color_secondary'],
        'brand-bg-accent' => $data['theme']['color_accents'],
        'brand-text-primary' => $data['theme']['color_link'],
      ];

      // Save the palette.
      $color->set('palette', $palette)->save();

      // Remove the already generated css files.
      // TODO: Check if isset.
      foreach ($color->get('stylesheets') as $file) {
        file_unmanaged_delete($file);
      }
    }

    // Return something.
    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntry(array $item) {
    // TODO: Implement getEntry() method.
  }

  /**
   * Prepares data about an image.
   *
   * @param string $picture
   *   The picture by uuid.
   *
   * @return array
   *   Returns an array.
   */
  protected function preparePicture($picture) {
    $value = NULL;
    $files = $this->fileStorage->loadByProperties([
      'uuid' => $picture,
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
    /* @var Font $font */
    $font = Font::create([
      'name' => $fontName,
      'user_id' => 1,
      'created' => REQUEST_TIME,
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

    /** @var File $file */
    $file = $this->preparePicture($data['image']);

    $block_image = [
      'target_id' => $file->id(),
      'alt' => "Anonymous front page image homepage'",
    ];
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
