<?php

namespace Drupal\social_user_export\Plugin\views\field;

use \Drupal\user\Plugin\views\field\UserBulkForm as BaseUserBulkForm;
use \Drupal\Core\Form\FormStateInterface;

/**
 * Defines a user operations bulk form element.
 *
 * @ViewsField("social_user_export_bulk_form")
 */
class UserBulkForm extends BaseUserBulkForm {

  /**
   * {@inheritdoc}
   */
  public function viewsFormSubmit(&$form, FormStateInterface $form_state) {
    if ($form_state->get('step') == 'views_form_views_form') {
      // Filter only selected checkboxes.
      $selected = array_filter($form_state->getValue($this->options['id']));
      $entities = [];
      $action = $this->actions[$form_state->getValue('action')];
      $count = 0;

      // Check whether all the selected users.
      if ($action->getPlugin()->getPluginId() == 'social_user_export_user_action') {
        $plugin = $action->getPlugin();
        $plugin->setApplyAll((bool) $form_state->getValue('select_all'));
        $plugin->setQuery($form_state->getValue('query', []));
      }

      foreach ($selected as $bulk_form_key) {
        $entity = $this->loadEntityFromBulkFormKey($bulk_form_key);

        // Skip execution if the user did not have access.
        if (!$action->getPlugin()->access($entity, $this->view->getUser())) {
          $this->drupalSetMessage($this->t('No access to execute %action on the @entity_type_label %entity_label.', [
            '%action' => $action->label(),
            '@entity_type_label' => $entity->getEntityType()->getLabel(),
            '%entity_label' => $entity->label()
          ]), 'error');
          continue;
        }

        $count++;

        $entities[$bulk_form_key] = $entity;
      }

      $action->execute($entities);

      $operation_definition = $action->getPluginDefinition();
      if (!empty($operation_definition['confirm_form_route_name'])) {
        $options = [
          'query' => $this->getDestinationArray(),
        ];
        $form_state->setRedirect($operation_definition['confirm_form_route_name'], [], $options);
      }
      else {
        // Don't display the message unless there are some elements affected and
        // there is no confirmation form.
        if ($count) {
          drupal_set_message($this->formatPlural($count, '%action was applied to @count item.', '%action was applied to @count items.', [
            '%action' => $action->label(),
          ]));
        }
      }
    }
  }

}
