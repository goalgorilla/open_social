<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Drupal\user_segments\Entity\UserSegment;

/**
 * Trait for handling user segment operations in Behat tests.
 *
 * This trait provides methods for converting segment labels to IDs,
 * validating segment uniqueness, and managing user segments for Behat test contexts.
 */
trait UserSegmentTrait {

  /**
   * Get the newest user segment ID from a label.
   *
   * @param string $label
   *   The label of the user segment.
   *
   * @return int|null
   *   The user segment ID or NULL if not found.
   */
  protected function getNewestUserSegmentIdFromLabel(string $label): ?int {
    $query = \Drupal::entityQuery('user_segment')
      ->condition('label', $label)
      ->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->range(0, 1);

    $ids = $query->execute();

    return !empty($ids) ? (int) reset($ids) : NULL;
  }

  /**
   * Get a user segment by label.
   *
   * @param string $label
   *   The label of the user segment.
   *
   * @return \Drupal\user_segments\Entity\UserSegment|null
   *   The user segment entity or NULL if not found.
   */
  protected function getUserSegmentByLabel(string $label): ?UserSegment {
    $id = $this->getNewestUserSegmentIdFromLabel($label);
    return $id ? UserSegment::load($id) : NULL;
  }

  /**
   * Get all user segments with a specific label.
   *
   * @param string $label
   *   The label of the user segments.
   *
   * @return \Drupal\user_segments\Entity\UserSegment[]
   *   Array of user segment entities.
   */
  protected function getUserSegmentsByLabel(string $label): array {
    $query = \Drupal::entityQuery('user_segment')
      ->condition('label', $label)
      ->accessCheck(FALSE);

    $ids = $query->execute();

    return !empty($ids) ? UserSegment::loadMultiple($ids) : [];
  }

  /**
   * Check if a user segment exists with the given label.
   *
   * @param string $label
   *   The label of the user segment.
   *
   * @return bool
   *   TRUE if the user segment exists, FALSE otherwise.
   */
  protected function userSegmentExists(string $label): bool {
    return $this->getNewestUserSegmentIdFromLabel($label) !== NULL;
  }

  /**
   * Delete a user segment by label.
   *
   * @param string $label
   *   The label of the user segment to delete.
   *
   * @return bool
   *   TRUE if the user segment was deleted, FALSE if it didn't exist.
   */
  protected function deleteUserSegmentByLabel(string $label): bool {
    $userSegment = $this->getUserSegmentByLabel($label);
    if ($userSegment) {
      $userSegment->delete();
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get user segments owned by a specific user.
   *
   * @param int $userId
   *   The user ID.
   *
   * @return \Drupal\user_segments\Entity\UserSegment[]
   *   Array of user segment entities owned by the user.
   */
  protected function getUserSegmentsByOwner(int $userId): array {
    $query = \Drupal::entityQuery('user_segment')
      ->condition('uid', $userId)
      ->accessCheck(FALSE);

    $ids = $query->execute();

    return !empty($ids) ? UserSegment::loadMultiple($ids) : [];
  }

  /**
   * Get enabled user segments.
   *
   * @return \Drupal\user_segments\Entity\UserSegment[]
   *   Array of enabled user segment entities.
   */
  protected function getEnabledUserSegments(): array {
    $query = \Drupal::entityQuery('user_segment')
      ->condition('status', TRUE)
      ->accessCheck(FALSE);

    $ids = $query->execute();

    return !empty($ids) ? UserSegment::loadMultiple($ids) : [];
  }

  /**
   * Get user segments that are used as visibility options.
   *
   * @return \Drupal\user_segments\Entity\UserSegment[]
   *   Array of user segment entities that are visibility options.
   */
  protected function getVisibilityOptionUserSegments(): array {
    $query = \Drupal::entityQuery('user_segment')
      ->condition('visibility_option', TRUE)
      ->accessCheck(FALSE);

    $ids = $query->execute();

    return !empty($ids) ? UserSegment::loadMultiple($ids) : [];
  }

}
