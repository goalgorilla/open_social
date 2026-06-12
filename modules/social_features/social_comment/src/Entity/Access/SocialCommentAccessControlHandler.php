<?php

declare(strict_types=1);

namespace Drupal\social_comment\Entity\Access;

use Drupal\comment\CommentInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group_core_comments\GroupCommentAccessControlHandler;
use Drupal\social_comment\HiddenCommentFieldAccessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Comment access control with hidden comment field revocations.
 */
final class SocialCommentAccessControlHandler extends GroupCommentAccessControlHandler {

  public function __construct(
    EntityTypeInterface $entity_type,
    EntityTypeManagerInterface $entity_type_manager,
    protected HiddenCommentFieldAccessInterface $hiddenCommentFieldAccess,
  ) {
    parent::__construct($entity_type, $entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('social_comment.hidden_comment_field_access'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $result = parent::checkAccess($entity, $operation, $account);
    if ($operation !== 'view' || !$entity instanceof CommentInterface) {
      return $result;
    }
    $hidden_result = $this->hiddenCommentFieldAccess->accessView($entity, $account);
    if ($hidden_result->isForbidden()) {
      return $result->andIf($hidden_result);
    }
    // Neutral hidden-field checks add a parent node dependency so allowed
    // responses invalidate when the comment field is hidden. Skip that merge
    // when view access is not allowed (permissions, status, etc.).
    if ($result->isAllowed() && $result instanceof RefinableCacheableDependencyInterface) {
      $result->addCacheableDependency($hidden_result);
    }
    return $result;
  }

}
