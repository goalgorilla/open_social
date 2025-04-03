<?php

namespace Drupal\grequest\Entity\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grequest\MembershipRequestManager;
use Drupal\group\Plugin\Validation\Constraint\GroupRelationshipCardinality;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for requesting a group membership.
 */
class GroupMembershipRequestForm extends GroupRelationshipBaseForm {

  /**
   * Membership request manager.
   *
   * @var \Drupal\grequest\MembershipRequestManager
   */
  protected $membershipRequestManager;

  /**
   * Constructs a request membership form.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\grequest\MembershipRequestManager $membership_request_manager
   *   Membership request manager.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, MembershipRequestManager $membership_request_manager) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->membershipRequestManager = $membership_request_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('grequest.membership_request_manager')
    );
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
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Request group membership');
    $actions['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $this->getCancelUrl(),
    ];

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'core/drupal.form';
    // Make field not accessible, because we set it programmatically.
    $form['entity_id']['#access'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = parent::save($form, $form_state);

    $this->messenger()->addMessage($this->t('Your request is waiting for approval'));
    $form_state->setRedirectUrl($this->getCancelUrl());
    return $return;
  }

}
