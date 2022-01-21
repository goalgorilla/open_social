<?php

namespace Drupal\social_group\Element;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\social_core\Entity\Element\EntityAutocomplete;
use Drupal\social_group\EntityMemberInterface;
use Drupal\user\UserInterface;

/**
 * Provides a Group member autocomplete form element.
 *
 * The #default_value accepted by this element is either an entity object or an
 * array of entity objects.
 *
 * @FormElement("social_group_entity_autocomplete")
 */
class SocialGroupEntityAutocomplete extends EntityAutocomplete {

  /**
   * Form element validation handler for entity_autocomplete elements.
   *
   * @param array $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   * @param bool $select2
   *   (optional) TRUE if the Select2 widget is used. Defaults to FALSE.
   */
  public static function validateEntityAutocomplete(
    array &$element,
    FormStateInterface $form_state,
    array &$complete_form,
    bool $select2 = FALSE
  ): void {
    /** @var \Drupal\Core\Entity\ContentEntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();

    if (($entity = $form_object->getEntity()) instanceof GroupContentInterface) {
      // Load the current Group, so we can see if there are existing members.
      $entity = $entity->getGroup();
    }

    // We need set "validate_reference" for element to prevent receive notice
    // Undefined index #validate_reference.
    if (!isset($element['#validate_reference'])) {
      $element['#validate_reference'] = FALSE;
    }

    if (!$entity instanceof EntityMemberInterface) {
      parent::validateEntityAutocomplete($element, $form_state, $complete_form);

      return;
    }

    if ($select2 !== TRUE) {
      $input_values = Tags::explode($element['#value']);
    }
    else {
      $input_values = $element['#value'];
    }

    $duplicated_values = $value = [];
    $storage = \Drupal::entityTypeManager()->getStorage('user');

    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $manager */
    $manager = \Drupal::service('plugin.manager.entity_reference_selection');

    foreach ($input_values as $input) {
      // If we use the select 2 widget then we already got a nice array.
      $match = $select2 ? $input : static::extractEntityIdFromAutocompleteInput($input);

      if ($match === NULL) {
        $options = $element['#selection_settings'] + [
          'target_type' => $element['#target_type'],
          'handler' => $element['#selection_handler'],
        ];

        /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler */
        $handler = $manager->getInstance($options);

        $autocreate = $element['#autocreate'] && $handler instanceof SelectionWithAutocreateInterface;

        // Try to get a match from the input string when the user didn't use
        // the autocomplete but filled in a value manually.
        // Got this from the parent::validateEntityAutocomplete.
        $match = static::matchEntityByTitle($handler, $input, $element, $form_state, !$autocreate);
      }

      if ($match !== NULL) {
        $value[$match] = ['target_id' => $match];
        $account = $storage->load($match);

        // User is already a member, add it to an array for the Form element
        // to render an error after all checks are gone.
        if ($account instanceof UserInterface && $entity->hasMember($account)) {
          $duplicated_values[] = $account->getDisplayName();
        }

        // Validate input for every single user. This way we make sure that
        // The element validates one, or more users added in the autocomplete.
        // This is because Group doesn't allow adding multiple users at once,
        // so we need to validate single users, if they all pass we can add
        // them all in the _social_group_action_form_submit.
        parent::validateEntityAutocomplete($element, $form_state, $complete_form);
      }
    }

    // If we have duplicates, provide an error message.
    if (!empty($duplicated_values)) {
      $message = \Drupal::translation()->formatPlural(count($duplicated_values),
        "@usernames is already member of the @type, you can't add them again",
        "@usernames are already members of the @type, you can't add them again",
        [
          '@usernames' => implode(', ', $duplicated_values),
          '@type' => $entity->getEntityType()->getSingularLabel(),
        ],
      );

      // We have to kick in a form set error here, or else the
      // GroupContentCardinalityValidator will kick in and show a faulty
      // error message. Alter this later when Group supports multiple members.
      $form_state->setError($element, $message);

      return;
    }

    if ($value) {
      // Select2 gives us an array back, which errors the field even though we
      // don't use it to perform the action, but we should mimic the behaviour
      // as it would be without Select2.
      if ($select2 === TRUE) {
        $form_state->setValue($element['#parents'], $match ?? NULL);
      }

      $form_state->setValue('entity_id_new', $value);
    }
  }

  /**
   * Form element validation handler for entity_autocomplete elements.
   *
   * @param array $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateEntityAutocompleteSelect2(
    array &$element,
    FormStateInterface $form_state,
    array &$complete_form
  ): void {
    static::validateEntityAutocomplete($element, $form_state, $complete_form, TRUE);
  }

}
