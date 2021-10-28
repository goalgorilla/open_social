<?php

namespace Drupal\gvbo\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gvbo\Access\GroupViewsBulkOperationsAccessTrait;
use Drupal\views\Views;
use Drupal\views_bulk_operations\Form\ConfigureAction;

/**
 * Action configuration form.
 */
class GroupViewsBulkOperationsConfigureAction extends ConfigureAction {

  use GroupViewsBulkOperationsAccessTrait;

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_data = $form_state->get('views_bulk_operations');
    $definition = $this->actionManager->getDefinition($form_data['action_id']);

    if (!empty($definition['confirm_form_route_name'])) {
      /** @var \Drupal\Core\Url $url */
      $url = $form_state->getRedirect();

      $parameters = $url->getRouteParameters();
      $view = Views::getView($parameters['view_id']);

      if ($view && $this->useGroupPermission($view, $parameters['display_id'])) {
        $url->setRouteParameter('group', $this->getRouteMatch()->getRawParameter('group'));
        $form_state->setRedirectUrl($url);
      }
    }
  }

}
