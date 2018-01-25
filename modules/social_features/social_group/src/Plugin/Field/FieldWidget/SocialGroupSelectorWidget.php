<?php

namespace Drupal\social_group\Plugin\Field\FieldWidget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;

/**
 * A widget to select a group when creating an entity in a group.
 *
 * @FieldWidget(
 *   id = "social_group_selector_widget",
 *   label = @Translation("Social group select list"),
 *   field_types = {
 *     "entity_reference",
 *     "list_integer",
 *     "list_float",
 *     "list_string"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SocialGroupSelectorWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#suffix'] = '<div id="group-selection-result"></div>';
    $element['#ajax'] = [
      'callback' => __CLASS__ . '::validateGroupSelection',
      'effect' => 'fade',
      'event' => 'change',
    ];

    $config = \Drupal::configFactory()->get('social_group.settings');
    $allow_group_selection_in_node = $config->get('allow_group_selection_in_node');
    $acting_user = \Drupal::currentUser();
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();
    $author = $entity->getOwner();

    // If it is a new node lets add the current group.
    if (!$entity->id()) {
      $current_group = _social_group_get_current_group();
      if (!empty($current_group) && empty($element['#default_value'])) {
        $element['#default_value'] = [$current_group->id()];
      }
    }
    else {
      if (!$allow_group_selection_in_node && !$acting_user->hasPermission('manage all groups')) {
        $element['#disabled'] = TRUE;
        $element['#description'] = t('Moving content after creation function has been disabled. In order to move this content, please contact a site manager.');
      }
    }

    // Lets remove all the groups the author is not a member of.
    if (!$author->hasPermission('manage all groups')) {
      $author_groups = social_group_get_all_group_members($author->id());

      foreach ($element['#options'] as $option_category_key => $option_category) {
        if (is_array($option_category)) {
          foreach ($option_category as $option_key => $option) {
            if (!in_array($option_key, $author_groups)) {
              unset($element['#options'][$option_category_key][$option_key]);
            }
          }
        }
        if (empty($element['#options'][$option_category_key])) {
          unset($element['#options'][$option_category_key]);
        }
      }
    }

    return $element;
  }

  /**
   * Validate the group selection and change the visibility settings.
   *
   * @param array $form
   *   Form to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state to process.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response changing values of the visibility field and set status message.
   */
  public function validateGroupSelection(array $form, FormStateInterface $form_state) {

    $ajax_response = new AjaxResponse();

    $selected_visibility = $form_state->getValue('field_content_visibility');
    if (!empty($selected_visibility)) {
      $selected_visibility = $selected_visibility['0']['value'];
    }
    if ($selected_groups = $form_state->getValue('groups')) {
      foreach ($selected_groups as $selected_group_key => $selected_group) {
        $gid = $selected_group['target_id'];
        $group = Group::load($gid);
        $group_type_id = $group->getGroupType()->id();

        $allowed_visibility_options = social_group_get_allowed_visibility_options_per_group_type($group_type_id);
        // TODO Add support for multiple groups, for now just process 1 group.
        break;
      }
    }
    else {
      $config = \Drupal::config('entity_access_by_field.settings');
      $default_visibility = $config->get('default_visibility');
      $entity = $form_state->getFormObject()->getEntity();

      $allowed_visibility_options = social_group_get_allowed_visibility_options_per_group_type(NULL, NULL, $entity);
      $ajax_response->addCommand(new InvokeCommand('#edit-field-content-visibility-' . $default_visibility, 'prop', ['checked', 'checked']));
    }

    foreach ($allowed_visibility_options as $visibility => $allowed) {
      $ajax_response->addCommand(new InvokeCommand('#edit-field-content-visibility-' . $visibility, 'addClass', ['js--animate-enabled-form-control']));
      if ($allowed === TRUE) {
        $ajax_response->addCommand(new InvokeCommand('#edit-field-content-visibility-' . $visibility, 'removeAttr', ['disabled']));
        $ajax_response->addCommand(new InvokeCommand('#edit-field-content-visibility-' . $visibility, 'prop', ['checked', 'checked']));
      }
      else {
        if ($selected_visibility && $selected_visibility === $visibility) {
          $ajax_response->addCommand(new InvokeCommand('#edit-field-content-visibility-' . $visibility, 'removeAttr', ['checked']));
        }
        $ajax_response->addCommand(new InvokeCommand('#edit-field-content-visibility-' . $visibility, 'prop', ['disabled', 'disabled']));
      }
    }
    $text = t('Changing the group may have impact on the <strong>visibility settings</strong>.');

    drupal_set_message($text, 'info');
    $alert = ['#type' => 'status_messages'];
    $ajax_response->addCommand(new HtmlCommand('#group-selection-result', $alert));

    return $ajax_response;
  }

}
