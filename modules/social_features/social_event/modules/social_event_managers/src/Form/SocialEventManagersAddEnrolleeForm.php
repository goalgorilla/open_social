<?php

namespace Drupal\social_event_managers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Utility\Token;
use Drupal\file\Entity\File;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\social_event\Entity\EventEnrollment;
use Drupal\node\NodeInterface;
use Drupal\social_event_managers\Element\SocialEnrollmentAutocomplete;

/**
 * Class SocialEventTypeSettings.
 *
 * @package Drupal\social_event_managers\Form
 */
class SocialEventManagersAddEnrolleeForm extends FormBase {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new GroupContentController.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, ConfigFactoryInterface $config_factory, Token $token) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->configFactory = $config_factory;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('config.factory'),
      $container->get('token')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'social_event_managers_enrollment_add';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enroll_uid = $form_state->getValue('entity_id_new');
    $event = $form_state->getValue('node_id');
    $count = 0;

    if (!empty($event) && !empty($enroll_uid)) {
      // Create a new enrollment for the event.
      foreach ($enroll_uid as $uid => $target_id) {
        $enrollment = EventEnrollment::create([
          'user_id' => \Drupal::currentUser()->id(),
          'field_event' => $event,
          'field_enrollment_status' => '1',
          'field_account' => $uid,
        ]);
        $enrollment->save();

        $count++;
      }

      // Add nice messages.
      if (!empty($count)) {
        $message = $this->formatPlural($count, '@count new member is enrolled to this event.', '@count new members are enrolled to this event.');

        if (social_event_manager_or_organizer(NULL, TRUE)) {
          $message = $this->formatPlural($count, '@count new member is enrolled to your event.', '@count new members are enrolled to your event.');
        }
        \Drupal::messenger()->addMessage($message, 'status');
      }

      // Redirect to management overview.
      $url = Url::fromRoute('view.event_manage_enrollments.page_manage_enrollments', [
        'node' => $event,
      ]);

      $form_state->setRedirectUrl($url);
    }
  }

  /**
   * Defines the settings form for Post entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'form--default';
    $nid = $this->routeMatch->getRawParameter('node');

    if (empty($nid)) {
      $node = $this->routeMatch->getParameter('node');
      if ($node instanceof NodeInterface) {
        // You can get nid and anything else you need from the node object.
        $nid = $node->id();
      }
      elseif (!is_object($node)) {
        $nid = $node;
      }
    }

    // Load the current Event enrollments so we can check duplicates.
    $storage = $this->entityTypeManager->getStorage('event_enrollment');
    $enrollments = $storage->loadByProperties(['field_event' => $nid]);

    $enrollmentIds = [];
    foreach ($enrollments as $enrollment) {
      $enrollmentIds[] = $enrollment->getAccount();
    }
    $form['users_fieldset'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'class' => [
          'form-horizontal',
        ],
      ],
    ];

    // @todo Validation should go on the element and return a nice list.
    $form['users_fieldset']['user'] = [
      '#title' => $this->t('Find people by name or email address'),
      '#type' => 'select2',
      '#multiple' => TRUE,
      '#tags' => TRUE,
      '#autocomplete' => TRUE,
      '#select2' => [
        'placeholder' => t('Jane Doe'),
        'tokenSeparators' => [',', ';'],
      ],
      '#selection_handler' => 'social',
      '#selection_settings' => [
        'skip_entity' => $enrollmentIds,
      ],
      '#target_type' => 'user',
      '#element_validate' => [
        [$this, 'uniqueMembers'],
      ],
    ];

    // Add the params that the email preview needs.
    $params = [
      'user' => $this->currentUser(),
      'node' => $this->entityTypeManager->getStorage('node')->load($nid),
    ];

    $variables = [
      '%site_name' => \Drupal::config('system.site')->get('name'),
    ];

    // Load event invite configuration.
    $add_directly_config = $this->configFactory->get('message.template.member_added_by_event_organiser')->getRawData();
    $invite_config = $this->configFactory->get('social_event_invite.settings');

    // Replace the tokens with similar ones since these rely
    // on the message object which we don't have in the preview.
    $add_directly_config['text'][2]['value'] = str_replace('[message:author:display-name]', '[user:display-name]', $add_directly_config['text'][2]['value']);
    $add_directly_config['text'][2]['value'] = str_replace('[social_event:event_iam_organizing]', '[node:title]', $add_directly_config['text'][2]['value']);

    // Cleanup message body and replace any links on invite preview page.
    $body = $this->token->replace($add_directly_config['text'][2]['value'], $params);
    $body = preg_replace('/href="([^"]*)"/', 'href="#"', $body);

    // Get default logo image and replace if it overridden with email settings.
    $theme_id = $this->configFactory->get('system.theme')->get('default');
    $logo = $this->getRequest()->getBaseUrl() . theme_get_setting('logo.url', $theme_id);
    $email_logo = theme_get_setting('email_logo', $theme_id);

    if (is_array($email_logo) && !empty($email_logo)) {
      $file = File::load(reset($email_logo));

      if ($file instanceof File) {
        $logo = file_create_url($file->getFileUri());
      }
    }

    $form['email_preview'] = [
      '#type' => 'fieldset',
      '#title' => [
        'text' => [
          '#markup' => t('Preview your email'),
        ],
        'icon' => [
          '#markup' => '<svg class="icon icon-expand_more"><use xlink:href="#icon-expand_more" /></svg>',
          '#allowed_tags' => ['svg', 'use'],
        ],
      ],
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#attributes' => [
        'class' => [
          'form-horizontal',
          'form-preview-email',
        ],
      ],
    ];

    $form['email_preview']['preview'] = [
      '#theme' => 'invite_email_preview',
      '#title' => $this->t('Message'),
      '#logo' => $logo,
      '#subject' => $this->t('Notification from %site_name', $variables),
      '#body' => $body,
      '#helper' => $this->token->replace($invite_config->get('invite_helper'), $params),
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => Url::fromRoute('view.event_manage_enrollments.page_manage_enrollments', ['node' => $nid]),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    // Ensure form actions are nicely wrapped.
    $form['actions']['#prefix'] = '<div class="form-actions">';
    $form['actions']['#suffix'] = '</div>';
    // Add some classes to make it consistent with GroupMember add.
    $form['actions']['submit']['#attributes']['class'] = ['button button--primary js-form-submit form-submit btn js-form-submit btn-raised btn-primary waves-effect waves-btn waves-light'];
    $form['actions']['cancel']['#attributes']['class'] = ['button button--danger btn btn-flat waves-effect waves-btn'];

    $form['#cache']['contexts'][] = 'user';

    return $form;
  }

  /**
   * Public function to validate members against enrollments.
   */
  public function uniqueMembers($element, &$form_state, $complete_form) {
    // Call the autocomplete function to make sure enrollees are unique.
    SocialEnrollmentAutocomplete::validateEntityAutocomplete($element, $form_state, $complete_form, TRUE);
  }

}
