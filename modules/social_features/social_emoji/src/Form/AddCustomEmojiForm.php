<?php

namespace Drupal\social_emoji\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

class AddCustomEmojiForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_emoji_add_custom_emoi_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'social_emoji/emoji-picker-element';

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#attribute' => [
        'id' => 'custom-emoji-name',
      ],
      '#required' => TRUE,
    ];

    $form['shortcodes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shortcodes, split by comma'),
      '#required' => TRUE,
      '#attributes' => [
        'id' => 'custom-emoji-shortcode',
      ],
    ];

    $form['category'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Category'),
      '#attributes' => [
        'id' => 'custom-emoji-category',
      ]
    ];

    $form['custom_emoji'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Add custom emoji'),
      '#upload_location' => 'public://emojis',
      '#upload_validators' => [
        'file_validate_is_image' => array(),
        'file_validate_extensions' => array('gif png jpg jpeg'),
        'file_validate_size' => array(25600000)
      ],
      '#attribtues' => [
        'id' => 'custom-emoji-url,'
      ],
    ];

    $form['save'] = [
      '#type' => 'button',
      '#value' => $this->t('Save'),
      '#attributes' => [
        'id' => 'add-new-custom-emoji-button',
      ],
      '#submit' => [$this, 'submitForm'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_file = $form_state->getValue('custom_emoji', 0);
    if (isset($form_file[0]) && !empty($form_file[0])) {
      $file = File::load($form_file[0]);
      $file->setPermanent();
      $file->save();
    }
  }
}
