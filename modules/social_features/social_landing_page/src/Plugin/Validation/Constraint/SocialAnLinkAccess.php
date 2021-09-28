<?php

namespace Drupal\social_landing_page\Plugin\Validation\Constraint;

use Drupal\link\Plugin\Validation\Constraint\LinkAccessConstraint;

/**
 * Defines the anonymous access validation constraint for links.
 *
 * @Constraint(
 *   id = "SocialAnLinkAccess",
 *   label = @Translation("Link URI can be accessed by the anonymous user"),
 * )
 */
class SocialAnLinkAccess extends LinkAccessConstraint {

}
