<?php

declare(strict_types=1);

namespace Drupal\social_course_advanced\Entity\group;

use Drupal\social_course\CourseGroupEntityBase;

/**
 * A base class for an advanced course group bundle class.
 */
class CourseAdvanced extends CourseGroupEntityBase {

  /**
   * The bundle id of the current group type.
   */
  public const BUNDLE = 'course_advanced';

}
