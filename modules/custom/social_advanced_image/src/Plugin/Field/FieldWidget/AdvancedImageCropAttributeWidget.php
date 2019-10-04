<?php

namespace Drupal\social_advanced_image\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image_widget_crop\Plugin\Field\FieldWidget\ImageCropWidget;

/**
 * Plugin implementation of the 'image crop copyright' attribute.
 *
 * @FieldWidget(
 *   id = "advance_image_crop_attributes",
 *   label = @Translation("Advanced Crop Image"),
 *   field_types = {
 *     "advanced_image"
 *   }
 * )
 */
class AdvancedImageCropAttributeWidget extends ImageCropWidget {

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
