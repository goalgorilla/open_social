<?php

namespace Drupal\social_image_copyright\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\social_image_copyright\ImageCopyrightAttributeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'image copyright' widget.
 *
 * @FieldWidget(
 *   id = "image_copyright_attributes",
 *   label = @Translation("Image copyright"),
 *   field_types = {
 *     "advanced_image"
 *   }
 * )
 */
class ImageCopyrightAttributeWidget extends ImageWidget {

  /**
   * The copyright attribute manager.
   *
   * @var \Drupal\social_image_copyright\ImageCopyrightAttributeManager
   */
  protected $imageCrManager;

  /**
   * ImageCopyrightAttributeWidget constructor.
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
   *   The element info manager.
   * @param \Drupal\social_image_copyright\ImageCopyrightAttributeManager $copyright_attribute_manager
   *   The copyright attribute manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ElementInfoManagerInterface $element_info, ImageCopyrightAttributeManager $copyright_attribute_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $element_info);
    $this->imageCrManager = $copyright_attribute_manager;
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
      $configuration['third_party_settings'],
      $container->get('plugin.manager.element_info'),
      $container->get('plugin.manager.image_copyright_attributes')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'copyright_attribute' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   *
   * This is the form display setting when this plugin is chosen as widget.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['copyright_attribute'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display copyright attribute'),
      '#default_value' => $this->getSetting('copyright_attribute'),
      '#description' => $this->t('The copyright will only be shown when it has been supplied'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * This is the message that gets displayed as the chosen plugin as a widget.
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('Copyright attribute: @bool', [
      '@bool' => $this->getSetting('copyright_attribute') ? $this->t('Yes') : $this->t('No'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Get the field settings of this widget.
    $field_settings = $this->getFieldSettings();
    $element['#image_copyright_field'] = $field_settings['image_copyright_field'];
    $element['#image_copyright_field_required'] = $field_settings['image_copyright_field_required'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $item = $element['#value'];
    $item['fids'] = $element['fids']['#value'];

    $element['copyright'] = [
      '#type' => 'textfield',
      '#title' => t('Copyright text'),
      '#default_value' => isset($item['copyright']) ? $item['copyright'] : '',
      '#description' => t('The copyright attribute is used as a tooltip when the mouse hovers over the image.'),
      '#maxlength' => 1024,
      '#weight' => -10,
      '#access' => (bool) $item['fids'] && $element['#image_copyright_field'],
      '#required' => $element['#image_copyright_field_required'],
      '#element_validate' => $element['#image_copyright_field_required'] == 1 ? [[get_called_class(), 'validateRequiredFields']] : [],
    ];

    return parent::process($element, $form_state, $form);
  }

}
