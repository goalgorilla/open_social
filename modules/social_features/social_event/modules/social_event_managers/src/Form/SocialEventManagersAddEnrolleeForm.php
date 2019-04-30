<?php

namespace Drupal\social_event_managers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\social_event\Entity\EventEnrollment;
use Drupal\node\NodeInterface;

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
        $singular = '@count new member is enrolled to this event.';
        $plural = '@count new members are enrolled to this event.';

        if (social_event_manager_or_organizer(NULL, TRUE)) {
          $singular = '@count new member is enrolled to your event.';
          $plural = '@count new members are enrolled to your event.';
        }

        $message = $this->formatPlural($count, $singular, $plural);
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

    $form['name'] = [
      '#type' => 'social_enrollment_entity_autocomplete',
      '#target_type' => 'user',
      '#tags' => TRUE,
      '#description' => $this->t('To add multiple members, separate each member with a comma ( , ).'),
      '#title' => $this->t('Select members to add'),
    ];

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

}
