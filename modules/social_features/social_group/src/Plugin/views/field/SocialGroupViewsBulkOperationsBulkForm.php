<?php

namespace Drupal\social_group\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\views_bulk_operations\Plugin\views\field\ViewsBulkOperationsBulkForm;

/**
 * Defines the Groups Views Bulk Operations field plugin.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("social_views_bulk_operations_bulk_form_group")
 */
class SocialGroupViewsBulkOperationsBulkForm extends ViewsBulkOperationsBulkForm {

  /**
   * {@inheritdoc}
   */
  public function getBulkOptions() {
    $bulk_options = parent::getBulkOptions();

    if ($this->view->id() !== 'group_manage_members') {
      return $bulk_options;
    }

    foreach ($bulk_options as $id => &$label) {
      if (!empty($this->options['preconfiguration'][$id]['label_override'])) {
        $real_label = $this->options['preconfiguration'][$id]['label_override'];
      }
      else {
        $real_label = $this->actions[$id]['label'];
      }

      switch ($id) {
        case 'social_group_members_export_member_action':
        case 'social_group_delete_group_content_action':
          $label = $this->t('<b>@action</b> selected members', [
            '@action' => $real_label,
          ]);

          break;

        case 'social_group_send_email_action':
          $label = $this->t('<b>@action</b>', [
            '@action' => $real_label,
          ]);

          break;

        case 'social_group_change_member_role_action':
          $label = $this->t('<b>@action</b> of selected members', [
            '@action' => $real_label,
          ]);

          break;
      }
    }

    return $bulk_options;
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    $this->view->setExposedInput(['status' => TRUE]);

    parent::viewsForm($form, $form_state);

    // Continue, if group members as a result on the manage members view.
    if (empty($form['output'][0]['#rows']) || $this->view->id() !== 'group_manage_members') {
      return;
    }

    $group = _social_group_get_current_group();
    $tempstoreData = $this->getTempstoreData($this->view->id(), $this->view->current_display);
    // Make sure the selection is saved for the current group.
    if (!empty($tempstoreData['group_id']) && $tempstoreData['group_id'] !== $group->id()) {
      // If not we clear it right away.
      // Since we don't want to mess with cached date.
      $this->deleteTempstoreData($this->view->id(), $this->view->current_display);
      // Reset initial values.
      $this->updateTempstoreData();
      // Initialize it again.
      $tempstoreData = $this->getTempstoreData($this->view->id(), $this->view->current_display);
    }
    // Add the Group ID to the data.
    $tempstoreData['group_id'] = $group->id();
    $this->setTempstoreData($tempstoreData, $this->view->id(), $this->view->current_display);

    // Reorder the form array.
    $multipage = $form['header'][$this->options['id']]['multipage'];
    unset($form['header'][$this->options['id']]['multipage']);
    $form['header'][$this->options['id']]['multipage'] = $multipage;

    // Render proper classes for the header in VBO form.
    $wrapper = &$form['header'][$this->options['id']];

    // Styling related for the wrapper div.
    $wrapper['#attributes']['class'][] = 'card';
    $wrapper['#attributes']['class'][] = 'card__block';

    // Add some JS for altering titles and switches.
    $form['#attached']['library'][] = 'social_group/views_bulk_operations.frontUi';

    // Render select all results checkbox.
    if (!empty($wrapper['select_all'])) {
      $wrapper['select_all']['#title'] = $this->t('Select / unselect all @count members across all the pages', [
        '@count' => $this->tempStoreData['total_results'] ? ' ' . $this->tempStoreData['total_results'] : '',
      ]);
      // Styling attributes for the select box.
      $form['header'][$this->options['id']]['select_all']['#attributes']['class'][] = 'form-no-label';
      $form['header'][$this->options['id']]['select_all']['#attributes']['class'][] = 'checkbox';
    }

    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $title */
    $title = $wrapper['multipage']['#title'];
    $arguments = $title->getArguments();
    $count = empty($arguments['%count']) ? 0 : $arguments['%count'];

    $title = $this->formatPlural($count, '<b><em class="placeholder">@count</em> Member</b> is selected', '<b><em class="placeholder">@count</em> Members</b> are selected');
    $wrapper['multipage']['#title'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $title,
    ];

    // Add selector so the JS of VBO applies correctly.
    $wrapper['multipage']['#attributes']['class'][] = 'vbo-multipage-selector';

    // Get tempstore data so we know what messages to show based on the data.
    $tempstoreData = $this->getTempstoreData($this->view->id(), $this->view->current_display);
    if (!empty($wrapper['multipage']['list']['#items']) && count($wrapper['multipage']['list']['#items']) > 0) {
      $excluded = FALSE;
      if (isset($tempstoreData['exclude_mode']) && $tempstoreData['exclude_mode']) {
        $excluded = TRUE;
      }
      $wrapper['multipage']['list']['#title'] = !$excluded ? $this->t('See selected members on other pages') : $this->t('Members excluded on other pages:');
    }

    // Update the clear submit button.
    if (!empty($wrapper['multipage']['clear'])) {
      $wrapper['multipage']['clear']['#value'] = $this->t('Clear selection on all pages');
      $wrapper['multipage']['clear']['#attributes']['class'][] = 'btn-default dropdown-toggle waves-effect waves-btn margin-top-l margin-left-m';
    }

    // Add the group to the display id, so the ajax callback that is run
    // will count and select across pages correctly.
    if ($group) {
      $wrapper['multipage']['#attributes']['data-group-id'] = $group->id();
      if (!empty($wrapper['multipage']['#attributes']['data-display-id'])) {
        $current_display = $wrapper['multipage']['#attributes']['data-display-id'];
        $wrapper['multipage']['#attributes']['data-display-id'] = $current_display . '/' . $group->id();
      }
    }

    // Actions are not a select list but a dropbutton list.
    $actions = &$wrapper['actions'];
    $actions['#theme'] = 'links__dropbutton__operations__actions';
    $actions['#label'] = $this->t('Actions');
    $actions['#type'] = 'dropbutton';

    $items = [];
    foreach ($wrapper['action']['#options'] as $key => $value) {
      if (!empty($key) && array_key_exists($key, $this->bulkOptions)) {
        $items[] = [
          '#type' => 'submit',
          '#value' => $value,
        ];
      }
    }

    // Add our links to the dropdown buttondrop type.
    $actions['#links'] = $items;
    // Remove the Views select list and submit button.
    $form['actions']['#type'] = 'hidden';
    $form['header']['social_views_bulk_operations_bulk_form_group']['action']['#access'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state) {
    if ($this->view->id() === 'group_manage_members') {
      $user_input = $form_state->getUserInput();
      $available_options = $this->getBulkOptions();
      // Grab all the actions that are available.
      foreach (Element::children($this->actions) as $action) {
        // If the option is not in our selected options, next.
        if (empty($available_options[$action])) {
          continue;
        }

        /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
        $label = $available_options[$action];

        // Match the Users action from our custom dropdown.
        // Find the action from the VBO selection.
        // And set that as the chosen action in the form_state.
        if (strip_tags($label->render()) === $user_input['op']) {
          $user_input['action'] = $action;
          $form_state->setUserInput($user_input);
          $form_state->setValue('action', $action);
          $form_state->setTriggeringElement($this->actions[$action]);
          break;
        }
      }
    }

    parent::viewsFormValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    parent::viewsFormSubmit($form, $form_state);

    if ($form_state->get('step') === 'views_form_views_form' && $this->view->id() === 'group_manage_members') {
      /** @var \Drupal\Core\Url $url */
      $url = $form_state->getRedirect();

      if ($url->getRouteName() === 'views_bulk_operations.execute_configurable') {
        $parameters = $url->getRouteParameters();

        if (empty($parameters['group'])) {
          $group = _social_group_get_current_group();
          $parameters['group'] = $group->id();
        }

        $url = Url::fromRoute('social_group_gvbo.views_bulk_operations.execute_configurable', [
          'group' => $parameters['group'],
        ]);

        $form_state->setRedirectUrl($url);
      }
    }
  }

}
