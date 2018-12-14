<?php

namespace Drupal\socialbase\Plugin\Form;

use Drupal\bootstrap\Plugin\Form\FormBase;
use Drupal\bootstrap\Utility\Element;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * @ingroup plugins_form
 *
 * @BootstrapForm("views_form_group_manage_members_page_group_manage_members_1")
 */
class GroupManageMembers extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function alterFormElement(Element $form, FormStateInterface $form_state, $form_id = NULL) {
    $actions = &$form->header->views_bulk_operations_bulk_form->actions;
    $actions->setProperty('theme_wrappers', ['buttons_group']);
    $actions->setProperty('label', $this->t('Actions'));

    $items = [];

    $weights = [
      'social_group_send_email_action' => 10,
      'social_group_members_export_member_action' => 20,
      'social_group_delete_group_content_action' => 30,
      'social_group_change_member_role_action' => 40,
    ];

    foreach ($actions->childKeys() as $key) {
      if (isset($weights[$key])) {
        $actions->{$key}->setProperty('weight', $weights[$key]);
      }
    }

    /** @var \Drupal\bootstrap\Utility\Element $action */
    foreach ($actions->children(TRUE) as $action) {
      $items[] = $action->render();
    }

    $actions->setProperty('items', $items);

    $form->actions->setProperty('access', FALSE);
  }

}
