<?php

declare(strict_types=1);

namespace Drupal\social_course_basic\Entity\group;

use Drupal\social_course\CourseGroupEntityBase;

/**
 * A base class for a basic course group bundle class.
 */
class CourseBasic extends CourseGroupEntityBase {

  /**
   * The bundle id of the current group type.
   */
  public const BUNDLE = 'course_basic';

}
