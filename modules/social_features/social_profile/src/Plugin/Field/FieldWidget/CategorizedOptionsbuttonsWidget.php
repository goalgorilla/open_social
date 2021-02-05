<?php

namespace Drupal\social_profile\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'categorized_options_buttons' widget.
 *
 * @todo Handle submit to add categories also as tags on user profile.
 *
 * @FieldWidget(
 *   id = "categorized_options_buttons",
 *   label = @Translation("Categorized check boxes/radio buttons"),
 *   field_types = {
 *     "boolean",
 *     "entity_reference",
 *     "list_integer",
 *     "list_float",
 *     "list_string",
 *   },
 *   multiple_values = TRUE
 * )
 */
class CategorizedOptionsbuttonsWidget extends OptionsButtonsWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // We can only overwrite the default behaviour if the user is allowed to
    // select multiple elements and we have something to choose from.
    // Otherwise we just default back to default behaviour.
    if ($this->fieldDefinition->getFieldStorageDefinition()->isMultiple() && !empty($element['#options'])) {
      $grouped_options = $this->getGroupedOptions($element['#options'], $element['#default_value']);

      // Change the parent type.
      $element['#type'] = 'container';
      $element['#attributes']['class'][] = 'checkboxes--nested';
      unset($element['#options']);
      unset($element['#default_value']);

      // Used to ensure that we don't need to alter the submit handler too much.
      $element['#checkbox_categories'] = [];
      $field_name = $this->fieldDefinition->getName();

      foreach ($grouped_options as $tid => $option) {
        // If this is a top level element without children then we just treat it
        // normally.
        if (empty($option['children'])) {
          $element += [
            'label' => [
              '#type' => 'label',
              '#title' => $element['#title'],
            ],
            'checkboxes' => [
              '#type' => 'checkboxes',
              '#default_value' => [],
              '#options' => [],
            ],
          ];
          $element['checkboxes']['#options'][$tid] = $option['label'];
          if ($option['selected']) {
            $element['checkboxes']['#default_value'][] = $tid;
          }
        }
        // Otherwise we just display all children. We don't support more than
        // a single level of nesting at the moment.
        else {
          $children = $this->getTaxonomyChildren($option);
          $element['#checkbox_categories'][] = $tid;
          $element[$tid] = [
            '#type' => 'container',
            'label' => [
              '#type' => 'label',
              '#title' => $option['label'],
              '#attributes' => [
                'class' => ['checkboxes__label'],
              ],
            ],
            'checkboxes' => [
              '#type' => 'checkboxes',
              '#default_value' => $children['selected'],
              '#options' => $children['children'],
            ],
          ];
        }
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    // Only pre-process the validation if this is a categorized checkbox
    // element.
    if (isset($element['#checkbox_categories'])) {
      // This turns the tree back into a single checkbox element that our parent
      // widget can handle. We didn't disable value tree submission because it
      // gives us issues in FormValidator::performRequiredValidation.
      $element['#value'] = [];
      $element['#options'] = [];
      if (isset($element['checkboxes'])) {
        $element['#value'] += $element['checkboxes']['#value'];
        $element['#options'] += $element['checkboxes']['#options'];
      }
      foreach ($element['#checkbox_categories'] as $tid) {
        // If a value in a category was checked then those selections are added
        // but we also select the category itself.
        if (!empty($element[$tid]['checkboxes']['#value'])) {
          $element['#value'] += [$tid => $tid];
          $element['#options'] += [$tid => $tid];
          $element['#value'] += $element[$tid]['checkboxes']['#value'];
          $element['#options'] += $element[$tid]['checkboxes']['#options'];
        }
      }
    }

    parent::validateElement($element, $form_state);
  }

  /**
   * Group the options with their parent taxonomy terms.
   *
   * This makes it easier to arrange them for display later. Relies on a proper
   * ordering of parent > child from the taxonomy validation.
   *
   * @param array $options
   *   The options to group.
   * @param array $selected
   *   The currently selected options.
   *
   * @return array
   *   A tree structure with labels and selection status as leaf values.
   */
  protected function getGroupedOptions(array $options, array $selected) {
    $grouped_options = [];

    foreach ($options as $tid => $label) {
      // Start at the top for the current element.
      $group = &$grouped_options;

      // Find the position for this element based on its parent group. If it
      // contains a dash (-) then it's a child and we find its parent.
      while (strpos($label, '-') === 0) {
        // Remove the dash denoting this is a child.
        $label = substr($label, 1);
        // Move into the last parent added.
        end($group);
        $group = &$group[key($group)]['children'];
      }

      // This is now a new child to the currently selected parent.
      $group[$tid] = [
        'label' => $label,
        'selected' => in_array($tid, $selected),
      ];
    }

    return $grouped_options;
  }

  /**
   * Reaches into all children and flattens the taxonomy.
   *
   * This method is private for a reason. We don't handle extra nesting yet but
   * could want to do this in the future. Do not rely on this method existing
   * in the future.
   *
   * @param array $parent
   *   The parent whose children to recurse through.
   * @param int $hyphens
   *   The number of hyphens to use as a label prefix.
   *
   * @return array[]
   *   A flattened array of children.
   */
  private function getTaxonomyChildren(array $parent, $hyphens = 0) {
    $children = [];
    $selected = [];

    foreach ($parent['children'] as $tid => $child) {
      $children[$tid] = str_repeat('-', $hyphens) . $child['label'];
      if ($child['selected']) {
        $selected[] = $tid;
      }
      if (!empty($child['children'])) {
        $result = $this->getTaxonomyChildren($child, $hyphens + 1);
        $children += $result['children'];
        $selected += $result['selected'];
      }
    }

    return [
      'children' => $children,
      'selected' => $selected,
    ];
  }

}
