<?php

namespace Drupal\social_group\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\group\Plugin\views\field\GroupViewsBulkOperationsBulkForm;

/**
 * Defines the Views Bulk Operations field plugin.
 */
class SocialGroupViewsBulkOperationsBulkForm extends GroupViewsBulkOperationsBulkForm {

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
        case 'social_group_send_email_action':
        case 'social_group_members_export_member_action':
        case 'social_group_delete_group_content_action':
          $label = $this->t('%action selected members', [
            '%action' => $real_label,
          ]);

          break;

        case 'social_group_change_member_role_action':
          $label = $this->t('%action of selected members', [
            '%action' => $real_label,
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
    parent::viewsForm($form, $form_state);
    $wrapper = &$form['header'][$this->options['id']];

    if (isset($wrapper['multipage'])) {
      $form['#attached']['library'][] = 'social_group/views_bulk_operations.frontUi';

      $count = count($this->tempStoreData['list']);

      if ($count) {
        $title = $this->formatPlural($count, '<b>@count Member</b> is selected', '<b>@count Members</b> are selected');
      }
      else {
        $title = $this->t('<b>@count Member</b> is selected', [
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
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state) {
    if ($this->view->id() === 'group_manage_members' && $this->options['buttons']) {
      $user_input = $form_state->getUserInput();

      foreach (Element::children($form['actions']) as $action) {
        /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
        $label = $form['actions'][$action]['#value'];

        if (strip_tags($label->render()) === $user_input['op']) {
          $form_state->setTriggeringElement($form['actions'][$action]);
          break;
        }
      }
    }

    parent::viewsFormValidate($form, $form_state);
  }

}
