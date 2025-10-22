<?php

declare(strict_types=1);

namespace Drupal\social_course_basic\Hooks;

use Drupal\hux\Attribute\Alter;
use Drupal\social_course_basic\Entity\group\CourseBasic;

/**
 * Specific hooks for "course_basic" group type definition altering.
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
    if (isset($bundles['group'][CourseBasic::BUNDLE])) {
      $bundles['group'][CourseBasic::BUNDLE]['class'] = CourseBasic::class;
    }
  }

}
