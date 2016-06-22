<?php

namespace Drupal\search_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\ServerInterface;

/**
 * Provides a form for performing common actions on a server.
 */
class ServerStatusForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_server_status';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ServerInterface $server = NULL) {
    $form['#server'] = $server;

    $form['actions']['#type'] = 'actions';
    $form['actions']['clear'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete all indexed data on this server'),
      '#button_type' => 'danger',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Redirect to the "Clear server" confirmation form.
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = $form['#server'];
    $form_state->setRedirect('entity.search_api_server.clear', array('search_api_server' => $server->id()));
  }

}
