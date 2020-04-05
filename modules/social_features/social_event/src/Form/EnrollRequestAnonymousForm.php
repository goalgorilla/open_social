<?php

namespace Drupal\social_event\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form to request event enrollment for anonymous.
 */
class EnrollRequestAnonymousForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'request_enrollment_modal_form_anonymous';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
    $node = \Drupal::routeMatch()->getParameter('node');
    $nid = $node->id();
    $node_url = Url::fromRoute('entity.node.canonical', ['node' => $nid])->toString();

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('In order to send your request, please first sign up or log in.'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['sign_up'] = [
      '#type' => 'link',
      '#title' => $this->t('Sign up'),
      '#attributes' => [
        'class' => [
          'btn',
          'btn-primary',
          'waves-effect',
          'waves-btn',
        ],
      ],
      '#url' => Url::fromRoute('user.register'),
    ];

    $form['actions']['log_in'] = [
      '#type' => 'link',
      '#title' => $this->t('Log in'),
      '#attributes' => [
        'class' => [
          'btn',
          'btn-default',
          'waves-effect',
          'waves-btn',
        ],
      ],
      '#url' => Url::fromRoute('user.login', [
        'destination' => $node_url . '?requested-enrollment=TRUE',
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
