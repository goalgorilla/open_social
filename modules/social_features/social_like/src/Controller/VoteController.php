<?php

namespace Drupal\social_like\Controller;

use Drupal\Core\Lock\LockBackendInterface;
use Drupal\like_and_dislike\Controller\VoteController as BaseVoteController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Vote controller to prevent race conditions.
 *
 * Wraps the like_and_dislike vote action with a per-user, per-entity lock so
 * concurrent requests (e.g. parallel or repeat clicks) result in at most one
 * vote being recorded.
 */
class VoteController extends BaseVoteController {

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected LockBackendInterface $lock;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->lock = $container->get('lock');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function vote($entity_type_id, $vote_type_id, $entity_id) {
    $lock_id = $this->getLockId($entity_type_id, $entity_id, $vote_type_id);

    if (!$this->lock->acquire($lock_id)) {
      $this->lock->wait($lock_id);
      if (!$this->lock->acquire($lock_id)) {
        return new JsonResponse([
          'message' => $this->t('Please try again.'),
          'message_type' => 'error',
        ], 503);
      }
    }

    try {
      return parent::vote($entity_type_id, $vote_type_id, $entity_id);
    }
    finally {
      $this->lock->release($lock_id);
    }
  }

  /**
   * Builds a lock id for the given vote context.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $entity_id
   *   The entity ID.
   * @param string $vote_type_id
   *   The vote type (like or dislike).
   *
   * @return string
   *   Lock id, unique per user + entity + vote type.
   */
  protected function getLockId(string $entity_type_id, string $entity_id, string $vote_type_id): string {
    $uid = $this->currentUser()->id();
    return "like_and_dislike.vote.{$uid}.{$entity_type_id}.{$entity_id}.{$vote_type_id}";
  }

}
