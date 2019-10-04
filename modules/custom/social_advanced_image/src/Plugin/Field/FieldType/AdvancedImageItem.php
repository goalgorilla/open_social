<?php

namespace Drupal\social_advanced_image\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\image\Plugin\Field\FieldType\ImageItem;

/**
 * Plugin implementation of the 'image' field type.
 *
 * @FieldType(
 *   id = "advanced_image",
 *   label = @Translation("Image Advanced"),
 *   description = @Translation("This field stores the ID of an image file as an integer value."),
 *   category = @Translation("Reference"),
 *   default_widget = "image_copyright_attributes",
 *   default_formatter = "advanced_image",
 *   column_groups = {
 *     "file" = {
 *       "label" = @Translation("File"),
 *       "columns" = {
 *         "target_id", "width", "height"
 *       },
 *       "require_all_groups_for_translation" = TRUE
 *     },
 *     "alt" = {
 *       "label" = @Translation("Alt"),
 *       "translatable" = TRUE
 *     },
 *     "title" = {
 *       "label" = @Translation("Title"),
 *       "translatable" = TRUE
 *     },
 *     "copyright" = {
 *       "label" = @Translation("Copyright"),
 *       "translatable" = TRUE
 *     },
 *   },
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class AdvancedImageItem extends ImageItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {

    $settings['image_copyright_field'] = 1;
    $settings['image_copyright_field_required'] = 0;
    $settings['default_image']['copyright'] = NULL;
    $settings += parent::defaultFieldSettings();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['copyright'] = [
      'description' => "Image copyright text, for the image's 'copyright' attribute.",
      'type' => 'varchar',
      'length' => 1024,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['copyright'] = DataDefinition::create('string')
      ->setLabel(t('Copyright'))
      ->setDescription(t("Image copyright text, for the image's 'copyright' attribute."));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);

    $settings = $this->getFieldDefinition()->getFieldStorageDefinition()->getSettings();

    $this->defaultImageForm($element, $settings);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    // Get the base from ImageItem which inherits FieldItem.
    $element = parent::fieldSettingsForm($form, $form_state);
    $settings = $this->getSettings();

    $element['image_copyright_field'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable <em>Copyright</em> field'),
      '#default_value' => $settings['image_copyright_field'],
      '#description' => t('The copyright attribute is used as a tooltip when the mouse hovers over the image.'),
      '#weight' => 13,
    ];
    $element['image_copyright_field_required'] = [
      '#type' => 'checkbox',
      '#title' => t('<em>Copyright</em> field required'),
      '#default_value' => $settings['image_copyright_field_required'],
      '#weight' => 14,
      '#states' => [
        'visible' => [
          ':input[name="settings[image_copyright_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultImageForm(array &$element, array $settings) {
    parent::defaultImageForm($element, $settings);

    // Add the copyright field.
    $element['default_image']['copyright'] = [
      '#type' => 'textfield',
      '#title' => t('Copyright text'),
      '#description' => t('The copyright attribute is used as a tooltip when the mouse hovers over the image.'),
      '#default_value' => $settings['default_image']['copyright'],
      '#maxlength' => 1024,
    ];
  }

}
