<?php

namespace Drupal\social_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'file_image_default' formatter.
 *
 * @FieldFormatter(
 *   id = "file_image_default",
 *   label = @Translation("File and Image in a table"),
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
class FileAndImageTableFormatter extends ImageFormatter {

  /**
   * Provides a factory for image objects.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected ImageFactory $imageFactory;

  /**
   * Constructs an ImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   Provides a factory for image objects.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    AccountInterface $current_user,
    EntityStorageInterface $image_style_storage,
    ImageFactory $image_factory
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings,
      $current_user,
      $image_style_storage
    );
    $this->imageFactory = $image_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('image.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Grab elements from the ImageFormatter and see if we can attach files?
    $elements = parent::viewElements($items, $langcode);

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;

      // If it's a File we render it as a file_link.
      if (!$this->isImage($file)) {
        $elements[$delta] = [
          '#theme' => 'file_link',
          '#file' => $file,
          '#cache' => [
            'tags' => $file->getCacheTags(),
          ],
        ];
        // Pass field item attributes to the theme function.
        if (isset($item->_attributes)) {
          $elements[$delta] += ['#attributes' => []];
          $elements[$delta]['#attributes'] += $item->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }
      }
      else {
        // If it's an image we need to add the image width and height
        // so Photoswipe knows what to render.
        $image = $this->getImage($file);
        $elements[$delta]['#item']->height = $image->getHeight();
        $elements[$delta]['#item']->width = $image->getWidth();
        $elements[$delta]['#item']->is_image = TRUE;
      }
    }

    return $elements;
  }

  /**
   * Check if its a file or image so we know what to render.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity. This function may resize the file affecting its size.
   *
   * @return bool
   *   TRUE when it's an image and not a file
   */
  private function isImage(FileInterface $file) {
    $image = $this->getImage($file);

    if ($image === NULL) {
      return FALSE;
    }

    return $image->isValid();
  }

  /**
   * Grab the Image if we are dealing with one.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity. This function may resize the file affecting its size.
   *
   * @return \Drupal\Core\Image\ImageInterface
   *   An Image object.
   */
  private function getImage(FileInterface $file) {
    // Make sure we deal with a file.
    return $this->imageFactory->get($file->getFileUri());
  }

}
