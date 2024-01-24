<?php

namespace Drupal\grequest\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\group\Entity\GroupRelationship;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Group request routes.
 */
class GrequestController extends ControllerBase {

  /**
   * The entity form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(EntityFormBuilderInterface $entity_form_builder, MessengerInterface $messenger) {
    $this->entityFormBuilder = $entity_form_builder;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.form_builder'),
      $container->get('messenger')
    );
  }

  /**
   * Builds the form to create new membership on membership request approve.
   */
  public function approveRequest(GroupInterface $group, GroupRelationshipInterface $group_content) {

    $relation_type_id = $this->entityTypeManager
      ->getStorage('group_content_type')
      ->getRelationshipTypeId($group->getGroupType()->id(), 'group_membership_request');

    // Pre-populate a group membership from Membership request.
    $group_content = GroupRelationship::create([
      'type' => $relation_type_id,
      'gid' => $group->id(),
      'entity_id' => $group_content->get('entity_id')->getString(),
    ]);

    return $this->entityFormBuilder->getForm($group_content, 'add');
  }

}
