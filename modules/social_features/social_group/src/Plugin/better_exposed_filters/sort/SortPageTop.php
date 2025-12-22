<?php

declare(strict_types=1);

namespace Drupal\social_group\Plugin\better_exposed_filters\sort;

use Drupal\better_exposed_filters\Plugin\better_exposed_filters\sort\DefaultWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays the sort widget on the page top.
 *
 * @see \Drupal\social_group\Plugin\Block\TitleWithSortsBlock
 *
 * @BetterExposedFiltersSortWidget(
 *   id = "bef_sort_page_top",
 *   label = @Translation("Sort Page Top"),
 * )
 */
class SortPageTop extends DefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    parent::exposedFormAlter($form, $form_state);

    // Not compatible with combined sort.
    if ($this->configuration['advanced']['combine']) {
      return;
    }

    // Required both sort and order elements.
    if (!isset($form['sort_by'], $form['sort_order'])) {
      return;
    }

    $form['sorting_group'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['sorting-group'],
      ],
    ];

    $field_group =& $form['sorting_group'];

    // Add the element to the container.
    $field_group['sort_by'] = $form['sort_by'];

    // Change the sort order element to the radio buttons.
    $field_group['sort_order'] = [
      '#type' => 'radios',
      '#options' => $form['sort_order']['#options'],
      '#default_value' => $form['sort_order']['#default_value'],
    ];

    // Add the form ID to the attributes to link with the form element.
    // JS will move these fields to the page top.
    $field_group['sort_by']['#attributes']['form'] =
    $field_group['sort_order']['#attributes']['form'] = $form['#id'];

    // Remove the element from the main form build.
    unset($form['sort_by'], $form['sort_order']);

    // Add the labels to the options.
    // It is used in JS to display the labels dynamically
    // for each sort order option.
    foreach ($field_group['sort_by']['#options'] as $key => $value) {
      $asc_label = $desc_label = '';

      if ($key === 'created') {
        $asc_label = $this->t('Last');
        $desc_label = $this->t('First');
      }

      if ($key === 'label') {
        $asc_label = $this->t('A-Z');
        $desc_label = $this->t('Z-A');
      }

      if ($asc_label) {
        $field_group['sort_by']['#options_attributes'][$key]['data-sort-order-label-asc'] = $asc_label;
      }

      if ($desc_label) {
        $field_group['sort_by']['#options_attributes'][$key]['data-sort-order-label-desc'] = $desc_label;
      }
    }
  }

}
