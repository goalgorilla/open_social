<?php

/**
 * @file
 *
 * Contains Drupal\message\FormElement.
 */

namespace Drupal\message\FormElement;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\message\Entity\MessageType;

class MessageTypeMultipleTextField {

  /**
   * The message type we handling.
   *
   * @var \Drupal\message\Entity\MessageType
   */
  protected $entity;

  /**
   * The name of the ajax callback.
   *
   * @var String
   *  Each form holds the text elements in a different location. When
   *  constructing this class we need to supply the name of the callback.
   *
   * @see MessageTypeConfigTranslationAddForm::addMoreAjax();
   */
  protected $callback;

  /**
   * Constructing the element.
   *
   * @param MessageType $entity
   *   A message type.
   * @param string $callback
   *   The name of the ajax callback.
   * @param string $langcode
   *   The language of the message. Used for the message translation form.
   */
  public function __construct(MessageType $entity, $callback, $langcode = '') {
    $this->entity = $entity;
    $this->callback = $callback;
    $this->langcode = $langcode ? $langcode : \Drupal::languageManager()->getCurrentLanguage()->getId();
  }

  /**
   * Return the message text element.
   */
  public function textField(&$form, FormStateInterface $form_state, $text = []) {
    // Creating the container.
    $form['text'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#theme' => 'field_multiple_value_form',
      '#caridnality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      '#cardinality_multiple' => TRUE,
      '#field_name' => 'message_text',
      '#title' => t('Message text'),
      '#description' => t('Please enter the message text.'),
      '#prefix' => '<div id="message-text">',
      '#suffix' => '</div>',
    ];

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => 'all',
        '#show_restricted' => TRUE,
      ];
    }

    $form['add_more'] = [
      '#type' => 'button',
      '#value' => t('Add another item'),
      '#href' => '',
      '#add_more' => TRUE,
      '#ajax' => [
        'callback' => $this->callback,
        'wrapper' => 'message-text',
      ],
    ];

    // Building the multiple form element; Adding first the the form existing
    // text.
    $start_key = 0;
    if (!$message_text = $text) {
      $message_text = $this->entity->getText($this->langcode) ? $this->entity->getText($this->langcode) : [];
    }

    if ($message_text) {
      foreach ($message_text as $text) {
        $form['text'][$start_key] = $this->singleElement($start_key, $start_key, $text);
        $start_key++;
      }
    }

    // Set the current elements number.
    if (!$form_state->get('elements')) {
      $form_state->set('elements', $start_key);
    }

    // Get the trigger element and check if this the add another item button.
    $trigger_element = $form_state->getTriggeringElement();

    if (!empty($trigger_element['#add_more'])) {
      // Increase the number of elements.
      $elements = $form_state->get('elements') + 1;
      $form_state->set('elements', $elements);
    }

    // Create partials from the last $start_key to the elements number.
    for ($i = $start_key; $i <= $form_state->get('elements'); $i++) {
      $form['text'][] = $this->singleElement($i, $start_key, '');
    }
  }

  /**
   * Return a single text area element.
   */
  private function singleElement($max_delta, $delta, $text = '') {
    $element = [
      '#type' => 'text_format',
      '#base_type' => 'textarea',
      '#default_value' => $text,
      '#rows' => 1,
    ];

    $element['_weight'] = [
      '#type' => 'weight',
      '#title' => t('Weight for row @number', ['@number' => $max_delta + 1]),
      '#title_display' => 'invisible',
      // Note: this 'delta' is the FAPI #type 'weight' element's property.
      '#delta' => $max_delta,
      '#default_value' => $delta,
    ];

    return $element;
  }
}
