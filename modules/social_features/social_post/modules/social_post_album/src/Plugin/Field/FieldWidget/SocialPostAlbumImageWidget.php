<?php

namespace Drupal\social_post_album\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'social_post_album_image' widget.
 *
 * @FieldWidget(
 *   id = "social_post_album_image",
 *   label = @Translation("Image"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class SocialPostAlbumImageWidget extends ImageWidget {

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs an SocialPostAlbumImageWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info manager service.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   (optional) The image factory service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   (optional) The currently active route match object.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    ElementInfoManagerInterface $element_info,
    ImageFactory $image_factory = NULL,
    RouteMatchInterface $route_match = NULL
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $third_party_settings,
      $element_info,
      $image_factory
    );

    $this->routeMatch = $route_match ?: \Drupal::routeMatch();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('social_post_album.element_info'),
      $container->get('image.factory'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
    if (
      $key === 'preview_image_style' &&
      $this->fieldDefinition->getTargetEntityTypeId() === 'post' &&
      $this->routeMatch->getRouteName() !== 'entity.post.edit_form'
    ) {
      return 'social_x_large';
    }

    return parent::getSetting($key);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#type'] = 'social_post_album_managed_file';

    return $element;
  }

}
