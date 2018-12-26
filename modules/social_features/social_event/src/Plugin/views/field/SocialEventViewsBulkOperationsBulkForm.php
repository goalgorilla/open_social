<?php

namespace Drupal\social_event\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\views_bulk_operations\Plugin\views\field\ViewsBulkOperationsBulkForm;

/**
 * Defines the Views Bulk Operations field plugin.
 */
class SocialEventViewsBulkOperationsBulkForm extends ViewsBulkOperationsBulkForm {

  /**
   * {@inheritdoc}
   */
  public function getBulkOptions() {
    $bulk_options = parent::getBulkOptions();

    if ($this->view->id() !== 'event_manage_enrollments') {
      return $bulk_options;
    }

    foreach ($bulk_options as $id => &$label) {
      if (!empty($this->options['preconfiguration'][$id]['label_override'])) {
        $real_label = $this->options['preconfiguration'][$id]['label_override'];
      }
      else {
        $real_label = $this->actions[$id]['label'];
      }

      $label = $this->t('<b>@action</b> selected members', [
        '@action' => $real_label,
      ]);
    }

    return $bulk_options;
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    $this->view->setExposedInput(['status' => TRUE]);

    parent::viewsForm($form, $form_state);

    if ($this->view->id() !== 'event_manage_enrollments') {
      return;
    }

    // Get pager data if available.
    if (!empty($this->view->pager) && method_exists($this->view->pager, 'hasMoreRecords')) {
      $pagerData = [
        'current' => $this->view->pager->getCurrentPage(),
        'more' => $this->view->pager->hasMoreRecords(),
      ];
    }

    $display_select_all = isset($pagerData) && ($pagerData['more'] || $pagerData['current'] > 0);

    // Select all results checkbox.
    if ($display_select_all) {
      $form['header'][$this->options['id']]['select_all'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Select all @count results in this view', [
          '@count' => $this->tempStoreData['total_results'] ? ' ' . $this->tempStoreData['total_results'] : '',
        ]),
        '#attributes' => [
          'class' => ['vbo-select-all', 'form-no-label', 'checkbox'],
        ],
      ];
    }

    $wrapper = &$form['header'][$this->options['id']];
    $wrapper['#attributes']['class'][] = 'card';
    $wrapper['#attributes']['class'][] = 'card__block';

    $form['#attached']['library'][] = 'social_group/views_bulk_operations.frontUi';

    $count = count($this->tempStoreData['list']);

    if ($count) {
      $title = $this->formatPlural($count, '<b>@count Member</b> is selected', '<b>@count Members</b> are selected');
    }
    else {
      $title = $this->t('<b>no members</b> are selected', [
        '@count' => $count,
      ]);
    }

    $wrapper['multipage']['#title'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['placeholder'],
      ],
      '#value' => $title,
    ];

    $wrapper['multipage']['list']['#title'] = $this->t('See selected members on other pages');

    $actions = &$wrapper['actions'];
    $actions['#theme'] = 'links__dropbutton__operations__actions';
    $actions['#label'] = $this->t('Actions');

    unset($actions['#type']);

    $items = [];

    $weights = [
      'social_event_send_email_action' => 10,
      'social_event_enrolments_export_enrollments_action' => 20,
      'social_event_delete_event_enrollment_action' => 30,
    ];

    foreach ($weights as $key => $weight) {
      if (isset($actions[$key])) {
        $actions[$key]['#weight'] = $weight;
      }
    }

    foreach (Element::children($actions, TRUE) as $key) {
      $items[$key] = $actions[$key];
    }

    $actions['#links'] = $items;

    $form['actions']['#access'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state) {
    if ($this->view->id() === 'event_manage_enrollments' && $this->options['buttons']) {
      $user_input = $form_state->getUserInput();
      $actions = &$form['actions'];

      foreach (Element::children($actions) as $action_id) {
        $action = &$actions[$action_id];

        if (isset($action['#access']) && !$action['#access']) {
          continue;
        }

        /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
        $label = $action['#value'];

        if (strip_tags($label->render()) === $user_input['op']) {
          $form_state->setTriggeringElement($action);
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

        $url = Url::fromRoute('social_group.views_bulk_operations.execute_configurable', [
          'group' => $parameters['group'],
        ]);

        $form_state->setRedirectUrl($url);
      }
    }
  }

}
