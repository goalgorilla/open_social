<?php

namespace Drupal\social_course_statistics;

use Drupal\Core\Database\Connection;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\social_course\CourseWrapper;
use Drupal\social_course\Entity\CourseEnrollmentInterface;

/**
 * Provide the 'social_course_statistics.course_statistics' service.
 *
 * @package Drupal\social_course_statistics
 */
class CourseStatistics {

  use StringTranslationTrait;

  /**
   * Course enrollment table.
   */
  const COURSE_ENROLLMENT_TABLE = 'course_enrollment';

  /**
   * The default database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Course wrapper.
   *
   * @var \Drupal\social_course\CourseWrapper
   */
  protected $courseWrapper;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs the course statistics service.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The current database connection.
   * @param \Drupal\social_course\CourseWrapper $course_wrapper
   *   Course wrapper.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(
    Connection $connection,
    CourseWrapper $course_wrapper,
    RouteMatchInterface $route_match,
  ) {
    $this->connection = $connection;
    $this->courseWrapper = $course_wrapper;
    $this->routeMatch = $route_match;
  }

  /**
   * Statistics available roles.
   */
  public static function statisticsRolesAvailable(): array {
    return [
      'administrator',
      'sitemanager',
      'contentmanager',
    ];
  }

  /**
   * Allowed group types.
   */
  public static function allowedGroupTypes(): array {
    return [
      'course_advanced-group_membership',
      'course_basic-group_membership',
    ];
  }

  /**
   * Course members id's.
   */
  public function getCourseMembers(GroupInterface $group): array {
    $allowed_group = $this::allowedGroupTypes();
    $uids = [];

    $table_name = 'group_relationship_field_data';
    $result = $this->connection->select($table_name)
      ->fields($table_name, ['entity_id'])
      ->condition($table_name . '.gid', (string) $group->id())
      ->condition($table_name . '.type', $allowed_group, 'IN')
      ->condition($table_name . '.entity_id', '0', '!=')
      ->isNotNull($table_name . '.label')
      ->execute();
    if (!is_null($result)) {
      $uids = $result->fetchCol();
    }

    return $uids;
  }

  /**
   * Course sections status.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|array
   *   Section status.
   */
  public function getCourseSectionsStatus(GroupInterface $group, AccountInterface $account) {
    $course_wrapper = $this->courseWrapper;
    $course_wrapper->setCourse($group);

    $sections = $course_wrapper->getSections();
    $course_sections = count($sections);

    $finished_sections = 0;
    foreach ($sections as $section) {
      $status = $course_wrapper->getSectionStatus($section, $account);
      if ($status === CourseEnrollmentInterface::FINISHED) {
        $finished_sections++;
      }
    }

    $status = $this->t('@finished_sections/@course_sections Sections finished', [
      '@finished_sections' => $finished_sections,
      '@course_sections' => $course_sections,
    ]);
    if ($course_sections && ($course_sections === $finished_sections)) {
      $status = $this->getStatusWithIcon();
    }

    return $status;
  }

  /**
   * The course start date for a specific user.
   */
  public function getCourseStartDatePerUser(GroupInterface $group, AccountInterface $account): int {
    $table_name = $this::COURSE_ENROLLMENT_TABLE;
    $create_date = 0;
    $result = $this->connection->select($table_name)
      ->fields($table_name, ['created'])
      ->condition($table_name . '.gid', (string) $group->id())
      ->condition($table_name . '.uid', (string) $account->id())
      ->orderBy($table_name . '.created')
      ->execute();
    if (!is_null($result)) {
      $create_date = $result->fetchField();
    }

    return $create_date;
  }

  /**
   * The course last active for a specific user.
   */
  public function getCourseLastActivePerUser(GroupInterface $group, AccountInterface $account, ?NodeInterface $node = NULL): int {
    $table_name = $this::COURSE_ENROLLMENT_TABLE;
    $query = $this->connection->select($table_name)
      ->fields($table_name, ['changed'])
      ->condition($table_name . '.gid', (string) $group->id())
      ->condition($table_name . '.uid', (string) $account->id());

    if (!is_null($node)) {
      $query->condition($table_name . '.sid', (string) $node->id());
    }

    $result = $query->orderBy($table_name . '.changed', 'DESC')
      ->execute();

    return $result ? $result->fetchField() : 0;
  }

  /**
   * Check if it is a statistics route.
   */
  public function isCourseStatisticsRoute(): bool {
    $statistics_routes = [
      'view.course_statistics.page_course_statistics',
      'view.course_sections_statistics.page_1',
    ];

    return in_array($this->routeMatch->getRouteName(), $statistics_routes);
  }

  /**
   * Check if it is a statistics route.
   */
  public function getStatusWithIcon(): array {
    return [
      '#type' => 'inline_template',
      '#template' => '<svg><use xlink:href="#icon-finished"></use></svg> {{ status }}',
      '#context' => [
        'status' => $this->t('Finished'),
      ],
    ];
  }

}
