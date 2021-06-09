<?php

namespace Drupal\social_private_message\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\private_message\Plugin\Field\FieldWidget\PrivateMessageThreadMemberWidget;

/**
 * A widget to select member for private message.
 *
 * @FieldWidget(
 *   id = "social_private_message_thread_member_widget",
 *   label = @Translation("Social private message members select list"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SocialPrivateMessageThreadMemberWidget extends PrivateMessageThreadMemberWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#selection_handler'] = 'social_private_message';
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['target_id']['#type'] = 'social_private_message_entity_autocomplete';
    $element['target_id']['#tags'] = TRUE;
    unset($element['#attached']);
    return $element;
  }

}
