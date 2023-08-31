<?php

declare(strict_types=1);

namespace Drupal\social_tag_split\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * A widget that uses the top taxonomy level as categories for split fields.
 *
 * This allows users who don't have access to the Field UI to create
 * multiple related taxonomy fields on an entity. This happens by pretending
 * that one field is multiple fields, splitting them no the top taxonomy level.
 *
 * @FieldWidget(
 *   id = "social_tag_split",
 *   label = @Translation("Tag Split"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class TagSplitWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display_title' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['display_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display title'),
      '#default_value' => $this->getSetting('display_title'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Display title: @setting', ['@setting' => $this->getSetting('display_title') ? $this->t("Yes") : $this->t("No")]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $field_widget_complete_form = parent::form($items, $form, $form_state, $get_delta);

    // If the widget in the element (built with formElement) has any subfields
    // then we return the widget.
    foreach ($field_widget_complete_form['widget'] as $key => $prop_or_child) {
      if ($key[0] !== "#") {
        return $field_widget_complete_form;
      }
    }

    // If we only have properties for the container and no subfields we want to
    // make sure there's no wrapper to render.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    assert($this->getFieldSetting('target_type') === "taxonomy_term", "The " . __CLASS__ . " widget should not be used for entity reference fields that don't reference taxonomy terms.");

    $term_ids = array_keys($this->getOptions($items->getEntity()));
    $default_value = $this->getSelectedOptions($items);
    $terms = Term::loadMultiple($term_ids);

    // The element will be the container which can't be required, but we must
    // make the sub-fields required if it's requested.
    $required = $element['#required'] ?? FALSE;
    unset($element['#required']);

    $element += [
      '#type' => 'details',
      '#open' => TRUE,
    ];
    if (!$this->getSetting("display_title")) {
      unset($element['#title'], $element['#description']);
    }

    $subfields = [];

    /** @var \Drupal\taxonomy\TermInterface $term */
    // This widget assumes that only 1 or 2 levels of nesting can be made and
    // that this is enforced elsewhere.
    foreach ($terms as $term_id => $term) {
      if ((int) $term->get('parent')->target_id === 0) {
        $subfields["tagging_${term_id}"] = [
          '#type' => 'select2',
          '#title' => $term->label(),
          '#multiple' => TRUE,
          '#default_value' => $default_value,
          '#required' => $required,
          // Don't overwrite the options if a child already started filling it
          // out.
          '#options' => $subfields["tagging_${term_id}"]['#options'] ?? [],
        ];
      }
      else {
        $parent_id = $term->get('parent')->target_id;
        $subfields["tagging_${parent_id}"]['#options'][$term_id] = $term->label();
      }
    }

    // Filter out any fields that don't have children options.
    // Also keep any of the `details` configuration (`#<string>`).
    $subfields = array_filter(
      $subfields,
      fn ($el) => !empty($el['#options'])
    );

    // Make sure we don't return an empty wrapper if we don't have any options
    // to render.
    if (empty($subfields)) {
      return [];
    }

    // Merge our actual fields as children into the container.
    $element += $subfields;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) : void {
    // This uses Drupal's Form API information to get the value for all
    // sub-fields in this element regardless of where the element is nested in
    // the form. It then merges the values of each of the sub-fields into the
    // value set for the actual field.
    // Since this an entity reference field we also need to turn the value into
    // a [<delta> => [<key> => <id>], ...] array to make sure things are
    // properly stored, this is normally handled by OptionsWidgetBase but breaks
    // because our top-level details element is not an #input element.
    $form_state->setValueForElement(
      $element,
      array_map(
        fn ($tid) => [$element['#key_column'] => $tid],
        array_merge(... array_values($form_state->getValue($element['#parents']) ?? []))
      )
    );
  }

}
