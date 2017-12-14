<?php

namespace Drupal\social_private_message\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * A widget to select member for private message.
 *
 * @FieldWidget(
 *   id = "social_private_message_thread_member_widget",
 *   label = @Translation("Social private message members select list"),
 *   field_types = {
 *     "entity_reference",
 *     "list_integer",
 *     "list_float",
 *     "list_string"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SocialPrivateMessageThreadMemberWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if (\Drupal::currentUser()->hasPermission('access user profiles')) {
      $recipient_id = \Drupal::service('request_stack')->getCurrentRequest()->get('recipient');
      if ($recipient_id) {
        $recipient = User::load($recipient_id);
        if ($recipient) {
          $element['#default_value'] = [$recipient_id];
        }
      }
    }

    return $element;
  }

}
