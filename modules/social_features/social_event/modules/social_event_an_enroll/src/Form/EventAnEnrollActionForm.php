<?php

namespace Drupal\social_event_an_enroll\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\social_event\SocialEventTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EventAnEnrollActionForm.
 *
 * @package Drupal\social_event_an_enroll\Form
 */
class EventAnEnrollActionForm extends FormBase implements ContainerInjectionInterface {

  use SocialEventTrait;

  /**
   * Event anonymous enrollment service.
   *
   * @var \Drupal\social_event_an_enroll\EventAnEnrollService
   */
  protected $eventAnEnrollService;

  /**
   * Drupal\Core\TempStore\PrivateTempStoreFactory definition.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->eventAnEnrollService = $container->get('social_event_an_enroll.service');
    $instance->tempStoreFactory = $container->get('tempstore.private');
    $instance->currentUser = $container->get('current_user');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->formBuilder = $container->get('form_builder');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_an_enroll_action_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    if ($node === NULL) {
      return [];
    }
    $nid = $node->id();
    $token = $this->getRequest()->query->get('token');
    if (empty($token)) {
      $store = $this->tempStoreFactory->get('social_event_an_enroll');
      $an_enrollments = $store->get('enrollments');
      if (isset($an_enrollments[$nid])) {
        $token = $an_enrollments[$nid];
      }
    }

    $form['enroll_wrapper'] = [
      '#type' => 'container',
      '#id' => 'enroll-wrapper',
    ];

    if (!empty($token) && $this->eventAnEnrollService->tokenExists($token, $nid)) {
      $form['event'] = [
        '#type' => 'hidden',
        '#value' => $nid,
      ];

      $form['enroll_wrapper']['enroll_for_this_event'] = [
        '#type' => 'button',
        '#value' => $this->t('Enrolled'),
        '#attributes' => [
          'class' => [
            'btn',
            'btn-accent',
            'btn-lg',
            'btn-raised',
            'brand-bg-accent',
            'dropdown-toggle',
            'waves-effect',
          ],
          'autocomplete' => 'off',
          'data-toggle' => 'dropdown',
          'aria-haspopup' => 'true',
          'aria-expanded' => 'false',
          'data-caret' => 'true',
        ],
      ];

      $form['enroll_wrapper']['feedback_user_has_enrolled'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel enrollment'),
        '#prefix' => '<ul class="dropdown-menu dropdown-menu-right"><li>',
        '#suffix' => '</li></ul>',
        '#attributes' => [
          'class' => [
            'btn-link',
          ],
        ],
        '#ajax' => [
          'callback' => [$form_state->getFormObject(), 'cancelEnrollmentAjax'],
          'disable-refocus' => TRUE,
          'progress' => 'none',
        ],
      ];

      $form['#attached']['library'][] = 'social_event/form_submit';
    }
    else {
      if ($this->eventHasBeenFinished($node)) {
        $form['event_enrollment'] = [
          '#type' => 'submit',
          '#value' => $this->t('Event has passed'),
          '#disabled' => TRUE,
          '#attributes' => [
            'class' => [
              'btn',
              'btn-accent',
              'btn-lg',
              'btn-raised',
              'brand-bg-accent',
              'waves-effect',
            ],
          ],
        ];
      }
      else {
        $attributes = [
          'class' => [
            'use-ajax',
            'js-form-submit',
            'form-submit',
            'btn',
            'btn-accent',
            'btn-lg',
          ],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => json_encode([
            'title' => t('Enroll in') . ' ' . strip_tags($node->getTitle()),
            'width' => 'auto',
          ]),
        ];

        $form['enroll_wrapper']['event_enrollment'] = [
          '#type' => 'link',
          '#title' => $this->t('Enroll'),
          '#url' => Url::fromRoute('social_event_an_enroll.enroll_dialog', ['node' => $nid]),
          '#attributes' => $attributes,
        ];
      }
    }
    $form['#cache'] = ['max-age' => 0];
    return $form;
  }

  /**
   * Rebuilds form after canceling the event enroll.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancelEnrollmentAjax(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $token = NULL;
    $nid = $form_state->getValue('event');
    $uid = $this->currentUser()->id();
    $store = $this->tempStoreFactory->get('social_event_an_enroll');
    $an_enrollments = $store->get('enrollments');
    if (isset($an_enrollments[$nid])) {
      $token = $an_enrollments[$nid];
    }

    $enrollments = $this->entityTypeManager->getStorage('event_enrollment')->loadByProperties([
      'field_account' => $uid,
      'field_event' => $nid,
      'field_token' => $token,
    ]);

    // Invalidate cache for our enrollment cache tag in
    // social_event_node_view_alter().
    $cache_tags[] = 'enrollment:' . $nid . '-' . $uid;
    $cache_tags[] = 'node:' . $nid;
    Cache::invalidateTags($cache_tags);

    if ($enrollment = array_pop($enrollments)) {
      $enrollment->delete();
      unset($an_enrollments[$nid]);
      $store->set('enrollments', $an_enrollments);
      $response->addCommand(new MessageCommand(
        $this->t('You are no longer enrolled in this event. Your personal data used for the enrollment is also deleted.'),
        NULL,
        ['type' => 'status']
      ));
    }

    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    $new_form = $this->buildForm($form, $form_state, $node);
    $new_form = $this->formBuilder->doBuildForm($this->getFormId(), $new_form, $form_state);
    $response->addCommand(new ReplaceCommand('#enroll-wrapper', $new_form['enroll_wrapper']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
