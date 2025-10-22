<?php

namespace Drupal\social_course;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;

/**
 * Interface of 'social_course.course_wrapper' service.
 *
 * @package Drupal\social_course
 */
interface CourseWrapperInterface {

  /**
   * Course order values is 'Sequential'.
   */
  const SOCIAL_COURSE_SEQUENTIAL = 1;

  /**
   * Course order values is 'Non-sequential'.
   */
  const SOCIAL_COURSE_NOT_SEQUENTIAL = 2;

  /**
   * Set a group instance.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group entity.
   *
   * @return \Drupal\social_course\CourseWrapperInterface
   *   The CourseWrapper entity.
   */
  public function setCourse(GroupInterface $group);

  /**
   * Get a group instance.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The Group entity.
   */
  public function getCourse();

  /**
   * Set list of available to handle bundles.
   *
   * @param array $bundles
   *   Array where each element is a machine name of a bundle.
   */
  public function setAvailableBundles(array $bundles): void;

  /**
   * Get list of available to handle bundles.
   *
   * @return array
   *   Array where each element is a machine name of a bundle.
   */
  public function getAvailableBundles();

  /**
   * Returns flag which determines if course has a type "sequential".
   *
   * @return bool
   *   If TRUE, course is sequential. If FALSE, course is non-sequential.
   */
  public function courseIsSequential();

  /**
   * Returns the current status of a course.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user that will be checked.
   *
   * @return int
   *   A course status.
   *
   * @see \Drupal\social_course\CourseEnrollmentInterface::NOT_STARTED
   * @see \Drupal\social_course\CourseEnrollmentInterface::IN_PROGRESS
   * @see \Drupal\social_course\CourseEnrollmentInterface::FINISHED
   */
  public function getCourseStatus(AccountInterface $account);

  /**
   * Check whether user has access to perform operation on entity.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node instance of type "section".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   An account instance to check if it has access.
   * @param string $op
   *   An operation key.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function sectionAccess(NodeInterface $node, AccountInterface $account, $op);

  /**
   * Check whether user has access to perform operation on entity.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node instance that is a material.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   An account instance to check if it has access.
   * @param string $op
   *   An operation key.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function materialAccess(NodeInterface $node, AccountInterface $account, $op);

  /**
   * Get all sections within a course.
   *
   * @return \Drupal\node\NodeInterface[]
   *   Array with node entities.
   */
  public function getSections();

  /**
   * Get all materials within a section or a course.
   *
   * @param \Drupal\node\NodeInterface|null $node
   *   Optional parameter. If NULL method will return all materials of a course.
   *   If not NULL it should be an instance of a node type of "section". In
   *   this case method will return only materials that attached to a section.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array with node entities.
   */
  public function getMaterials(?NodeInterface $node = NULL);

  /**
   * Get all finished materials within a section.
   *
   * @param \Drupal\node\NodeInterface $node
   *   An instance of a node type of "section".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   An account instance.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array with node entities.
   */
  public function getFinishedMaterials(NodeInterface $node, AccountInterface $account);

  /**
   * Load course and set it as handleable (to self::$course).
   *
   * @param \Drupal\node\NodeInterface $node
   *   An instance of a node type of "section". Course will be loaded based on
   *   this instance.
   *
   * @return \Drupal\social_course\CourseWrapperInterface
   *   The CourseWrapper entity.
   */
  public function setCourseFromSection(NodeInterface $node);

  /**
   * Load course and set it as handleable (to self::$course).
   *
   * @param \Drupal\node\NodeInterface $node
   *   An instance of a material (node). At first this method will load parent
   *   section and then will load a course.
   *
   * @return \Drupal\social_course\CourseWrapperInterface
   *   The CourseWrapper entity.
   *
   * @see \Drupal\social_course\CourseWrapperInterface::setCourseFromSection()
   */
  public function setCourseFromMaterial(NodeInterface $node);

  /**
   * Get parent section of a material.
   *
   * @param \Drupal\node\NodeInterface $node
   *   An instance of a material (node). Section (node) will be loaded based on
   *   this instance.
   *
   * @return \Drupal\node\NodeInterface|false
   *   The Node entity.
   */
  public function getSectionFromMaterial(NodeInterface $node);

  /**
   * Get a section with offset.
   *
   * @param \Drupal\node\NodeInterface $node
   *   An instance of a material (node).
   * @param int $offset
   *   Offset parameter. For example: if set -1, method will return previous
   *   section. If 1, method will return next section.
   *
   * @return \Drupal\node\NodeInterface
   *   The Node entity.
   */
  public function getSection(NodeInterface $node, $offset);

  /**
   * Get a material with offset.
   *
   * @param \Drupal\node\NodeInterface $node
   *   An instance of a material (node).
   * @param int $offset
   *   Offset parameter. For example: if set -1, method will return previous
   *   material. If 1, method will return next material.
   *
   * @return \Drupal\node\NodeInterface
   *   The Node entity.
   */
  public function getMaterial(NodeInterface $node, $offset);

  /**
   * Get a section number within a course.
   *
   * @param \Drupal\node\NodeInterface $node
   *   An instance of a node type of "section".
   *
   * @return int
   *   A number of the section. First section will have 0 number.
   */
  public function getSectionNumber(NodeInterface $node);

  /**
   * Get a material number within a section.
   *
   * @param \Drupal\node\NodeInterface $node
   *   An instance of a material (node).
   *
   * @return int
   *   A number of the material. First material will have 0 number.
   */
  public function getMaterialNumber(NodeInterface $node);

}
