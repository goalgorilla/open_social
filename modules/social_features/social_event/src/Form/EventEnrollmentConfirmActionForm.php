<?php

namespace Drupal\social_event\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\views_bulk_operations\Form\ConfirmAction;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Event action execution confirmation form.
 */
class EventEnrollmentConfirmActionForm extends ConfirmAction {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   */
  public function __construct(
    PrivateTempStoreFactory $tempStoreFactory,
    ViewsBulkOperationsActionManager $actionManager,
    ViewsBulkOperationsActionProcessorInterface $actionProcessor,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($tempStoreFactory, $actionManager, $actionProcessor);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('plugin.manager.views_bulk_operations_action'),
      $container->get('views_bulk_operations.processor'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_id = NULL, $display_id = NULL) {
    $form = parent::buildForm($form, $form_state, $view_id, $display_id);

    if (isset($form['list'])) {
      $data = $form_state->get('views_bulk_operations');
      $storage = $this->entityTypeManager->getStorage('event_enrollment');
      $id = 0;

      foreach ($data['list'] as $item) {
        $entity = $storage->load($item[0]);
        $form['list']['#items'][$id++] = $this->getAccountName($entity);
      }
    }

    return $form;
  }

  /**
   * Returns enrollee name.
   *
   * @param \Drupal\social_event\EventEnrollmentInterface $entity
   *   The event enrollment.
   *
   * @return string
   *   The account name.
   */
  public function getAccountName(EventEnrollmentInterface $entity) {
    $profiles = $this->entityTypeManager->getStorage('profile')
      ->loadByProperties([
        'uid' => $entity->field_account->target_id,
      ]);

    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = reset($profiles);

    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
    $label = $profile->label();

    return $label->getArguments()['@name'];
  }

}
