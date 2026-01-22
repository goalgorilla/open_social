<?php

declare(strict_types=1);

namespace Drupal\social_profile_organization_tag\Service;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides functionality for managing and displaying organization tags.
 */
class SocialProfileOrganizationTag {

  use StringTranslationTrait;

  /**
   * SocialProfileOrganizationTag constructor.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   */
  public function __construct(
    protected EntityRepositoryInterface $entityRepository,
  ) {}

  /**
   * Function that fetches the extra organizational info for a profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile entity.
   * @param bool $return_html
   *   Whether to return HTML or plain text.
   *
   * @return \Drupal\Component\Render\FormattableMarkup|string
   *   The formatted organizational info.
   */
  public function getTagText(ProfileInterface $profile, bool $return_html = TRUE): string|FormattableMarkup {
    if (
      !$profile->hasField('field_profile_organization_tag') ||
      $profile->get('field_profile_organization_tag')->isEmpty()
    ) {
      return '';
    }

    $organization = $profile->get('field_profile_organization_tag')->entity;
    if (!$organization instanceof Term) {
      return '';
    }

    // Get a translation from the page context.
    $organization = $this->entityRepository->getTranslationFromContext($organization);

    $value = $return_html
      ? '<span class="social-profile-tag" data-social-tooltip="@org"></span>'
      // phpcs:ignore
      : $this->t(' at @org')->render();

    return new FormattableMarkup($value, [
      '@org' => $organization->label(),
    ]);
  }

}
