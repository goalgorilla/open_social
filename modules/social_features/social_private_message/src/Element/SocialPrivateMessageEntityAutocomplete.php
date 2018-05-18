<?php

namespace Drupal\social_private_message\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\social_core\Entity\Element\EntityAutocomplete;

/**
 * Provides an private message member autocomplete form element.
 *
 * The #default_value accepted by this element is either an entity object or an
 * array of entity objects.
 *
 * @FormElement("social_private_message_entity_autocomplete")
 */
class SocialPrivateMessageEntityAutocomplete extends EntityAutocomplete {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo();
  }

  public static function processEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element = parent::processEntityAutocomplete($element, $form_state, $complete_form);
//    $element['#autocomplete_route_name'] = 'private_message.members_widget_callback';
    return $element;
  }

}
