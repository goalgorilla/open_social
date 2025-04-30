<?php

namespace Drupal\social_group_request\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grequest\Entity\Form\GroupMembershipApproveForm;
use Drupal\grequest\MembershipRequestManager;
use Drupal\group\Entity\GroupRelationshipInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for approving a group membership request.
 */
class GroupRequestMembershipApproveForm extends GroupMembershipApproveForm {

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a new GroupRequestMembershipApproveForm.
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
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, MembershipRequestManager $membership_request_manager, LoggerInterface $logger, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time, $membership_request_manager, $logger);
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('grequest.membership_request_manager'),
      $container->get('logger.factory')->get('group_relationship'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $group_relationship = $this->getEntity();
    assert($group_relationship instanceof GroupRelationshipInterface, "The GroupRequestMembershipRejectForm form is used for an entity that's not a Group Relationship, this indicates a misconfiguration in the form or a change in the group module.");
    /** @var \Drupal\user\UserInterface $user */
    $user = $group_relationship->getEntity();
    return $this->t('Are you sure you want to approve the membership request for %user?', ['%user' => $user->getDisplayName()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['#attributes']['class'][] = 'form--default';
    $form['actions']['cancel']['#attributes']['class'] = [
      'button',
      'button--flat',
      'btn',
      'btn-flat',
      'waves-effect',
      'waves-btn',
    ];

    // Remove possibility to select roles when membership request is approved.
    if (isset($form['roles'])) {
      unset($form['roles']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $group_relationship = $this->getEntity();
    assert($group_relationship instanceof GroupRelationshipInterface, "The GroupRequestMembershipRejectForm form is used for an entity that's not a Group Relationship, this indicates a misconfiguration in the form or a change in the group module.");

    $group = $group_relationship->getGroup();

    // Add cache tags for group views.
    $tags = [
      'group_list',
      'group_content_list',
      'group_view',
      'group_content_view',
    ];
    // Add cache tags that are based on group id.
    $tags[] = 'group_hero:' . $group->id();
    $tags[] = 'group_block:' . $group->id();
    // Add cache tags that are based on group content.
    $tags[] = 'group_content:' . $this->getEntity()->id();
    $this->cacheTagsInvalidator->invalidateTags($tags);
  }

}
