<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\TestControlInterface\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_group_flexible_group\TestControlInterface\Error\GroupNotFound;
use Drupal\social_group_flexible_group\TestControlInterface\Error\GroupTypeNotFound;
use Drupal\social_group_flexible_group\TestControlInterface\Success\GroupTypeAddedToGroupSuccess;
use Drupal\taxonomy\TermInterface;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * TCI endpoint for tagging a group.
 *
 * Replaces TaggingContext::addGroupTypeToGroup().
 */
final class AddGroupTypeToGroup {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Sets an existing group_type term on an existing group.
   *
   * @return \Drupal\social_group_flexible_group\TestControlInterface\Success\GroupTypeAddedToGroupSuccess|\Drupal\social_group_flexible_group\TestControlInterface\Error\GroupNotFound|\Drupal\social_group_flexible_group\TestControlInterface\Error\GroupTypeNotFound
   *   The operation result.
   */
  #[Operation(
    id: 'group_type_tag_add',
    patterns: ['I add the group type :group_type_name to the group :group_title'],
    type: OperationType::Write,
  )]
  public function addGroupTypeToGroup(
    #[Example('Community')]
    string $group_type_name,
    #[Example('Woof Airlines')]
    string $group_title,
  ): GroupTypeAddedToGroupSuccess|GroupNotFound|GroupTypeNotFound {
    $groups = $this->entityTypeManager->getStorage('group')->loadByProperties([
      'label' => $group_title,
    ]);
    $group = reset($groups);
    if (!$group instanceof GroupInterface) {
      return new GroupNotFound($group_title);
    }

    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'name' => $group_type_name,
      'vid' => 'group_type',
    ]);
    $term = reset($terms);
    if (!$term instanceof TermInterface) {
      return new GroupTypeNotFound($group_type_name);
    }

    if (!$group->hasField('field_group_type')) {
      throw new BadRequestHttpException('Group does not have field_group_type field.');
    }

    $group->set('field_group_type', ['target_id' => $term->id()]);
    $group->save();

    return new GroupTypeAddedToGroupSuccess(groupId: (int) $group->id(), termId: (int) $term->id());
  }

}
