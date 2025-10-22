<?php

declare(strict_types=1);

namespace Drupal\social_course_advanced\Hooks;

use Drupal\hux\Attribute\Alter;
use Drupal\social_course_advanced\Entity\group\CourseAdvanced;

/**
 * Specific hooks for "course_advanced" group type definition altering.
 */
final class EntityBundleInfoAlter {

  /**
   * Alters the provided entity bundle information.
   *
   * @param array $bundles
   *   A reference to the array of entity bundles that may be altered.
   */
  #[Alter('entity_bundle_info')]
  public function applyGroupBundleClass(array &$bundles): void {
    if (isset($bundles['group'][CourseAdvanced::BUNDLE])) {
      $bundles['group'][CourseAdvanced::BUNDLE]['class'] = CourseAdvanced::class;
    }
  }

}
