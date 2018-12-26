<?php

namespace Drupal\social_event_an_enroll\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event\Form\EventEnrollmentConfirmActionForm;
use Drupal\social_event_an_enroll\EventAnEnrollManager;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Event action execution confirmation form.
 */
class EventAnEnrollConfirmActionForm extends EventEnrollmentConfirmActionForm {

  /**
   * The event an enroll manager.
   *
   * @var \Drupal\social_event_an_enroll\EventAnEnrollManager
   */
  protected $socialEventAnEnrollManager;

  /**
   * Constructor.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $tempStoreFactory
   *   User private temporary storage factory.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager $actionManager
   *   Extended action manager object.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface $actionProcessor
   *   Views Bulk Operations action processor.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\social_event_an_enroll\EventAnEnrollManager $social_event_an_enroll_manager
   *   The event an enroll manager.
   */
  public function __construct(
    PrivateTempStoreFactory $tempStoreFactory,
    ViewsBulkOperationsActionManager $actionManager,
    ViewsBulkOperationsActionProcessorInterface $actionProcessor,
    EntityTypeManagerInterface $entity_type_manager,
    EventAnEnrollManager $social_event_an_enroll_manager
  ) {
    parent::__construct($tempStoreFactory, $actionManager, $actionProcessor, $entity_type_manager);

    $this->socialEventAnEnrollManager = $social_event_an_enroll_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('plugin.manager.views_bulk_operations_action'),
      $container->get('views_bulk_operations.processor'),
      $container->get('entity_type.manager'),
      $container->get('social_event_an_enroll.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_id = NULL, $display_id = NULL) {
    $form = parent::buildForm($form, $form_state, $view_id, $display_id);

    if (isset($form['list'])) {
      asort($form['list']['#items']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountName(EventEnrollmentInterface $entity) {
    if ($entity->field_account->target_id) {
      return parent::getAccountName($entity);
    }

    return $this->socialEventAnEnrollManager->getGuestName($entity);
  }

}
