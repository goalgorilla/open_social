<?php

declare(strict_types=1);

namespace Drupal\social_course;

use Drupal\Core\Url;
use Drupal\social_group\Entity\Group;

/**
 * A base class for course group bundle classes.
 */
class CourseGroupEntityBase extends Group {

  /**
   * A canonical route for courses.
   */
  const CANONICAL_ROUTE = 'social_group.stream';

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    $url = parent::toUrl($rel, $options);

    if ($rel === 'canonical') {
      $canonical_url = new Url(static::CANONICAL_ROUTE, $url->getRouteParameters(), $url->getOptions());
      if ($canonical_url->access()) {
        return $canonical_url;
      }
    }

    return $url;
  }

}
