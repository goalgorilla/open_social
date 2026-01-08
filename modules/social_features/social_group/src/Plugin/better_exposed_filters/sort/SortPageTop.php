<?php

declare(strict_types=1);

namespace Drupal\social_group\Plugin\better_exposed_filters\sort;

use Drupal\better_exposed_filters\Plugin\better_exposed_filters\sort\DefaultWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Html;

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
      '#prefix' => '<div class="sorting-options">',
      '#suffix' => '</div>',
      '#after_build' => [[static::class, 'processRadios']],
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
      $default_order = 'ASC';

      // Get the default order from the view's sort handler.
      foreach ($this->view->sort as $sort_id => $sort_handler) {
        if ($sort_handler->isExposed()
            && !empty($sort_handler->options['expose']['field_identifier'])
            && $sort_handler->options['expose']['field_identifier'] === $key) {
          $default_order = $sort_handler->options['order'] ?? 'ASC';
          break;
        }
      }

      if ($key === 'created') {
        $asc_label = $this->t('First');
        $desc_label = $this->t('Last');
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

      $field_group['sort_by']['#options_attributes'][$key]['data-sort-order-default'] = $default_order;
    }
  }

  /**
   * Processes radio button elements to add modifier classes for styling.
   *
   * @param array $element
   *   The radio element array to process. This contains child elements
   *   that will be modified with additional CSS classes.
   *
   * @return array
   *   The processed radio element array with added classes.
   */
  public static function processRadios(array $element): array {
    foreach (Element::children($element) as $key) {
      // Add the specific key (asc/desc) as a modifier class
      // to add icon in styles.
      $element[$key]['#attributes']['class'][] = Html::getClass('sb-radio--' . $key);
    }

    return $element;
  }

}
