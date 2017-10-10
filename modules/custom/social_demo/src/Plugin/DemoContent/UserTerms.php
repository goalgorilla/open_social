<?php

namespace Drupal\social_demo\Plugin\DemoContent;

use Drupal\social_demo\DemoTaxonomyTerm;

/**
 * Profile terms Plugin for demo content.
 *
 * @DemoContent(
 *   id = "user_terms",
 *   label = @Translation("User Terms"),
 *   source = "content/entity/user-terms.yml",
 *   entity_type = "taxonomy_term"
 * )
 */
class UserTerms extends DemoTaxonomyTerm {

}
