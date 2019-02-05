<?php

namespace Drupal\social_group_gvbo\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\gvbo\Form\GroupViewsBulkOperationsConfigureAction;

/**
 * Action configuration form.
 */
class SocialGroupViewsBulkOperationsConfigureAction extends GroupViewsBulkOperationsConfigureAction {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_id = 'group_manage_members', $display_id = 'page_group_manage_members') {
    return parent::buildForm($form, $form_state, 'group_manage_members', 'page_group_manage_members');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_data = $form_state->get('views_bulk_operations');

    if ($form_data['view_id'] === 'group_manage_members') {
      /** @var \Drupal\Core\Url $url */
      $url = $form_state->getRedirect();

      if ($url->getRouteName() === 'views_bulk_operations.confirm') {
        $parameters = $url->getRouteParameters();

        $url = Url::fromRoute('social_group_gvbo.views_bulk_operations.confirm', [
          'group' => $parameters['group'],
        ]);

        $form_state->setRedirectUrl($url);
      }
    }
  }

}
