<?php

namespace Drupal\grequest\Entity\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grequest\MembershipRequestManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for rejecting a group membership request.
 */
class GroupMembershipRejectForm extends GroupRelationshipBaseConfirmForm {

  /**
   * Membership request manager.
   *
   * @var \Drupal\grequest\MembershipRequestManager
   */
  protected $membershipRequestManager;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a reject membership form.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\grequest\MembershipRequestManager $membership_request_manager
   *   Membership request manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, MembershipRequestManager $membership_request_manager, LoggerInterface $logger) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->membershipRequestManager = $membership_request_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('grequest.membership_request_manager'),
      $container->get('logger.factory')->get('group_relationship')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to reject a request for %user?', ['%user' => $this->getEntity()->getEntity()->getDisplayName()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->getGroup()->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Reject');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $group_relationship = $this->getEntity();

    $result = $this->membershipRequestManager->reject($group_relationship);

    if ($result) {
      $this->messenger()->addStatus($this->t('Membership request rejected'));
    }
    else {
      $this->messenger()->addError($this->t('Error updating request'));
    }

    $this->logger->notice('@type: rejected %title.', [
      '@type' => $group_relationship->bundle(),
      '%title' => $group_relationship->label(),
    ]);

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
