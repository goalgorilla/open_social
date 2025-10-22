<?php

namespace Drupal\social_course\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Course Enrollment entity.
 *
 * @ingroup social_course
 * @package Drupal\social_course
 */
interface CourseEnrollmentInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  const NOT_STARTED = -1;
  const IN_PROGRESS = 1;
  const FINISHED = 2;

  /**
   * Gets course object.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The Group entity.
   */
  public function getCourse();

  /**
   * Gets course id.
   *
   * @return int
   *   The Group id.
   */
  public function getCourseId();

  /**
   * Gets section object.
   *
   * @return \Drupal\node\NodeInterface
   *   The Node entity.
   */
  public function getSection();

  /**
   * Gets section id.
   *
   * @return int
   *   The Node id.
   */
  public function getSectionId();

  /**
   * Gets material object.
   *
   * @return \Drupal\node\NodeInterface
   *   The Node entity.
   */
  public function getMaterial();

  /**
   * Gets material id.
   *
   * @return int
   *   The Node id.
   */
  public function getMaterialId();

  /**
   * Gets course enrollment status.
   *
   * @return int
   *   The CourseEnrollment status.
   */
  public function getStatus();

  /**
   * Sets course enrollment status.
   *
   * @param int $status
   *   Status code.
   *
   * @return \Drupal\social_course\CourseEnrollmentInterface
   *   The CourseEnrollment entity.
   */
  public function setStatus($status);

  /**
   * Check if user has finished attempt among same user enrollments.
   *
   * @return bool
   *   Displays if user was able to finish attempt.
   */
  public function hasFinishedAttempt(): bool;

}
