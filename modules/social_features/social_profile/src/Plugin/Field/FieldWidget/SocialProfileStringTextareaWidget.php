<?php

namespace Drupal\social_profile\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'social_profile_string_textarea' widget.
 *
 * @FieldWidget(
 *   id = "social_profile_string_textarea",
 *   label = @Translation("Textarea"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class SocialProfileStringTextareaWidget extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['value']['#type'] = 'textarea';

    return $element;
  }

}
