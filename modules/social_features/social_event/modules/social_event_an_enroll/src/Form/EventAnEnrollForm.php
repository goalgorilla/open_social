<?php

namespace Drupal\social_event_an_enroll\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\social_event\Form\EnrollActionForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class EventAnEnrollForm.
 *
 * @package Drupal\social_event_an_enroll\Form
 */
class EventAnEnrollForm extends EnrollActionForm {

  /**
   * The Data Policy consent manager.
   *
   * @var \Drupal\data_policy\DataPolicyConsentManagerInterface
   */
  protected $dataPolicyConsentManager;

  /**
   * Drupal\Core\TempStore\PrivateTempStoreFactory definition.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    if ($instance->moduleHandler->moduleExists('data_policy')) {
      $instance->dataPolicyConsentManager = $container->get('data_policy.manager');
    }
    $instance->tempStoreFactory = $container->get('tempstore.private');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_an_enroll_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node = $this->routeMatch->getRawParameter('node');

    // Load node object in case it's not converted for us.
    if (is_numeric($node)) {
      $node = Node::load($node);
    }

    // Do nothing if we don't have the 'node' param in the URL.
    if (!$node instanceof NodeInterface) {
      return [];
    }

    $form['error_wrapper'] = [
      '#type' => 'container',
      '#id' => 'enroll-an-error-wrapper',
    ];

    // Set hidden values.
    $form['field_event'] = [
      '#type' => 'hidden',
      '#value' => $node->id(),
    ];

    $form['field_first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First name'),
    ];

    $form['field_last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last name'),
    ];

    $form['field_email'] = [
      '#type' => 'email',
      '#required' => TRUE,
      '#title' => $this->t('Email address'),
      '#description' => $this->t('Enter your email, so we can send you event updates.'),
    ];

    if ($this->moduleHandler->moduleExists('data_policy')) {
      if (!$this->dataPolicyConsentManager->isDataPolicy()) {
        return $form;
      }

      // We are not saving this data to the database, but simply just showing
      // it, as data_policy is set to use user_id, which is not unique if the
      // user is anonymous.
      $this->dataPolicyConsentManager->addCheckbox($form);
    }

    $submit_text = $this->t('Enroll in event');
    $enrollment_open = TRUE;

    // Add the enrollment closed label.
    if ($this->eventHasBeenFinished($node)) {
      $submit_text = $this->t('Event has passed');
      $enrollment_open = FALSE;
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#button_level' => 'raised',
      '#value' => $submit_text,
      '#disabled' => !$enrollment_open,
      '#ajax' => [
        'callback' => '::ajaxSubmitAnEnrollForm',
        'disable-refocus' => TRUE,
        'progress' => 'none',
        'url' => Url::fromRoute('social_event_an_enroll.enroll_form', ['node' => $node->id()]),
        'options' => [
          'query' => [
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Rebuilds form after ajax submit.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function ajaxSubmitAnEnrollForm(array &$form, FormStateInterface $form_state): Response {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#enroll-an-error-wrapper', $form['error_wrapper']));
    if ($form_state->hasAnyErrors()) {
      foreach ($form_state->getErrors() as $error) {
        $response->addCommand(new MessageCommand(
          $error,
          '#enroll-an-error-wrapper',
          ['type' => 'error']
        ));
      }
      $form_state->clearErrors();
      $this->messenger()->deleteByType('error');
      return $response;
    }
    $token = NULL;
    $nid = $form_state->getValue('field_event');
    $uid = $this->currentUser()->id();
    $store = $this->tempStoreFactory->get('social_event_an_enroll');
    $an_enrollments = $store->get('enrollments');
    $values = $form_state->getValues();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    // Check if there is enrollment with the same email.
    /** @var \Drupal\social_event\EventEnrollmentInterface[] $enrollments */
    $enrollments = $this->enrollmentStorage->loadByProperties([
      'field_event' => $nid,
      'field_email' => $form_state->getValue('field_email'),
    ]);

    if ($enrollment = array_pop($enrollments)) {
      $token = $enrollment->get('field_token')->getString();
      $response->addCommand(new CloseDialogCommand());
      $response->addCommand(new MessageCommand(
        $this->t('You have been already enrolled to this event. You have also received a notification via email.'),
        NULL,
        ['type' => 'status']
      ));
    }
    else {
      $token = Crypt::randomBytesBase64();

      $values['user_id'] = $uid;
      $values['field_account'] = $uid;
      $values['field_enrollment_status'] = '1';
      $values['field_token'] = $token;

      $this->enrollmentStorage->create($values)->save();
      $enroll_confirmation = $this->entityTypeManager->getViewBuilder('node')->view($node, 'teaser');
      $enroll_confirmation['#theme'] = 'event_enrollment_confirmation';
      $response->addCommand(new OpenModalDialogCommand(
        $this->t('Thanks for enrolling!'),
        $enroll_confirmation,
        [
          'width' => '479px',
          'dialogClass' => 'social-dialog--event-addtocal',
        ]
      ));
    }

    $an_enrollments[$nid] = $token;
    $store->set('enrollments', $an_enrollments);

    // Send email if the setting is enabled.
    $event_an_enroll_config = $this->config('social_event_an_enroll.settings');
    if ($event_an_enroll_config->get('event_an_enroll_email_notify')) {
      social_event_an_enroll_send_mail($values);
    }

    $an_enroll_form = $this->formBuilder->getForm(EventAnEnrollActionForm::class, $node);
    $response->addCommand(new ReplaceCommand('#enroll-wrapper', $an_enroll_form['enroll_wrapper']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
