<?php

namespace Drupal\social_event_an_enroll\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\social_event_an_enroll\EventAnEnrollManager;
use Drupal\social_event_managers\Plugin\views\field\SocialEventManagersViewsBulkOperationsBulkForm;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsViewDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the Views Bulk Operations field plugin.
 */
class SocialEventAnEnrollViewsBulkOperationsBulkForm extends SocialEventManagersViewsBulkOperationsBulkForm implements ContainerFactoryPluginInterface {

  /**
   * The event an enroll manager.
   *
   * @var \Drupal\social_event_an_enroll\EventAnEnrollManager
   */
  protected $socialEventAnEnrollManager;

  /**
   * Constructs a new SocialEventAnEnrollViewsBulkOperationsBulkForm object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsViewDataInterface $viewData
   *   The VBO View Data provider service.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager $actionManager
   *   Extended action manager object.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface $actionProcessor
   *   Views Bulk Operations action processor.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   User private temporary storage factory.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\social_event_an_enroll\EventAnEnrollManager $social_event_an_enroll_manager
   *   The event an enroll manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ViewsBulkOperationsViewDataInterface $viewData,
    ViewsBulkOperationsActionManager $actionManager,
    ViewsBulkOperationsActionProcessorInterface $actionProcessor,
    PrivateTempStoreFactory $tempStoreFactory,
    AccountInterface $currentUser,
    RequestStack $requestStack,
    EntityTypeManagerInterface $entity_type_manager,
    EventAnEnrollManager $social_event_an_enroll_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $viewData, $actionManager, $actionProcessor, $tempStoreFactory, $currentUser, $requestStack, $entity_type_manager);

    $this->socialEventAnEnrollManager = $social_event_an_enroll_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('views_bulk_operations.data'),
      $container->get('plugin.manager.views_bulk_operations_action'),
      $container->get('views_bulk_operations.processor'),
      $container->get('tempstore.private'),
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('social_event_an_enroll.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityLabel(EntityInterface $entity) {
    /** @var \Drupal\social_event\EventEnrollmentInterface $entity */
    if ($this->socialEventAnEnrollManager->isGuest($entity)) {
      return $this->socialEventAnEnrollManager->getGuestName($entity);
    }

    return parent::getEntityLabel($entity);
  }

}
