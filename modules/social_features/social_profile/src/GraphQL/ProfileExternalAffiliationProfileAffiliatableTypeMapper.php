<?php

declare(strict_types=1);

namespace Drupal\social_profile\GraphQL;

use Drupal\paragraphs\ParagraphInterface;

/**
 * Maps other-affiliation paragraphs to ExternalProfileAffiliation.
 */
final class ProfileExternalAffiliationProfileAffiliatableTypeMapper implements ProfileAffiliatableTypeMapperInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(mixed $object): ?string {
    return $object instanceof ParagraphInterface && $object->bundle() === 'other_affiliations'
      ? 'ExternalProfileAffiliation'
      : NULL;
  }

}
