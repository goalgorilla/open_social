<?php

namespace Drupal\social_event_managers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\social_event\Form\EnrollActionForm;
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
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer')
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
    $form['#attributes']['class'][] = 'card card__block form--default form-wrapper form-group';

    if (empty($nid)) {
      $node = \Drupal::routeMatch()->getParameter('node');
      if ($node instanceof NodeInterface) {
        // You can get nid and anything else you need from the node object.
        $nid = $node->id();
      }
      elseif (!is_object($node)) {
        $nid = $node;
      }
    }

    // We check if the node is placed in a Group.
    if (!empty($nid)) {
      if (!is_object($nid) && !is_null($nid)) {
        $node = $this->entityTypeManager
          ->getStorage('node')
          ->load($nid);
      }
      $groups = EnrollActionForm::getGroups($node);
    }


    // Load the current Event enrollments so we can check duplicates.
    $storage = \Drupal::entityTypeManager()->getStorage('event_enrollment');
    $enrollments = $storage->loadByProperties(['field_event' => $nid]);

    $enrollmentIds = [];
    foreach ($enrollments as $enrollment) {
      $enrollmentIds[] = $enrollment->getAccount();
    }

    // If the groups are not empty, give the option to pre-fill members.
    if (!empty($groups)) {
      // Add the button to add all the current members of the group.
      $enroll = $this->t('Select all members of the group this event belongs to');
      $form['enroll_users'] = [
        '#markup' => '<div class="card__link" id="enroll_users"><a href="#" class="enroll-form-submit"><svg class="icon icon-plus">+ <use xlink:href="#icon-plus" /></svg>' . $enroll . '</a></div>',
        '#allowed_tags' => ['a', 'div'],
      ];

      // Only 1 group per content is possible now.
      foreach ($groups as $group) {
        $group_id = $group->id();
      }

      // Attach a JS library so we can populate it with group members.
      $form['#attached']['library'][] = 'social_event_managers/social_select2_populate';
      $form['#attached']['drupalSettings']['populateEnrollmentsFromGroup'] = [
        'nid' => $nid,
        'group' => $group_id,
      ];
    }

    $form['name'] = [
      '#type' => 'select2',
      '#title' => $this->t('Members'),
      '#description' => $this->t('To add multiple members, separate each member with a comma ( , ).'),
      '#multiple' => TRUE,
      '#tags' => TRUE,
      '#autocomplete' => TRUE,
      '#selection_handler' => 'social',
      '#selection_settings' => [
        'skip_entity' => $enrollmentIds,
      ],
      '#target_type' => 'user',
      '#element_validate' => [
        [$this, 'unique_members'],
      ],
    ];


    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => Url::fromRoute('view.event_manage_enrollments.page_manage_enrollments', ['node' => $nid]),
    ];

    $form['actions']['submit'] = [
      '#prefix' => '<div class="form-actions">',
      '#suffix' => '</div>',
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    $form['#cache']['contexts'][] = 'user';

    return $form;
  }

  /**
   * Public function to validate members against enrollments.
   */
  public function unique_members($element, &$form_state, $complete_form) {
    // Call the autocomplete function to make sure enrollees are unique.
    SocialEnrollmentAutocomplete::validateEntityAutocomplete($element, $form_state, $complete_form, TRUE);
  }
}
