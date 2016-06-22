<?php

namespace Drupal\search_api\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirm form for disabling a server.
 */
class ServerDisableConfirmForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to disable the search server %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Disabling a server will also disable all attached indexes, clearing their tracking tables and indexed data. When re-enabling the server and its indexes, all data will have to be re-indexed. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.search_api_server.canonical', array('search_api_server' => $this->entity->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Disable');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = $this->entity;
    $server->setStatus(FALSE)->save();

    drupal_set_message($this->t('The search server %name has been disabled.', array('%name' => $this->entity->label())));
    $form_state->setRedirect('search_api.overview');
  }

}
