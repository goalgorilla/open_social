<?php

declare(strict_types=1);

namespace Drupal\social_group\Hooks;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hux\Attribute\Alter;

/**
 * Alters forms for the Social Group module.
 */
final class FormAlter {

  use StringTranslationTrait;

  /**
   * Enhances the filters in the exposed form for the group members view.
   *
   * @param array $form
   *   The exposed form render array to be altered.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @see hook_form_FORM_ID_alter()
   */
  #[Alter('form_views_exposed_form')]
  public function enhanceGroupMembersFilters(array &$form, FormStateInterface $form_state): void {
    if ($form['#id'] !== 'views-exposed-form-group-members-page-group-members') {
      return;
    }

    // Enhance the "Joined" views exposed filter.
    if (isset($form['created_wrapper']['created_wrapper'])) {
      $date_filter =& $form['created_wrapper']['created_wrapper'];

      // Convert the wrapper from "fieldset" to "container".
      $date_filter['#type'] = 'container';
      $date_filter['created_op']['#title'] = $date_filter['#title'];
      $date_filter['created_op']['#title_display'] = 'before';

      // Replace "operator" options with new titles.
      $date_filter['created_op']['#options'] = [
        '<=' => $this->t('Before'),
        '>=' => $this->t('After'),
        'between' => $this->t('Between'),
      ];

      // Hide titles for date range filters.
      $date_filter['created']['min']['#title_display'] =
      $date_filter['created']['max']['#title_display'] = 'invisible';
    }
  }

}
