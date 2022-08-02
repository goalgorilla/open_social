<?php

namespace Drupal\social_out_of_office;

use Drupal\profile\Entity\ProfileInterface;

/**
 * Defines Out of office helper service interface.
 */
interface SocialOutOfOfficeHelperInterface {

  /**
   * Profile type.
   */
  const PROFILE_TYPE = 'profile';

  /**
   * Message max length.
   */
  const MESSAGE_MAX_LENGTH = 240;

  /**
   * Get profile out of office dates.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   Profile entity.
   *
   * @return array
   *   Out of office dates.
   */
  public function getOutOfOfficeDates(ProfileInterface $profile): array;

  /**
   * Is user out of office.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   Profile entity.
   *
   * @return bool
   *   TRUE if user is out of office, FALSE otherwise.
   */
  public function isUserOutOfOffice(ProfileInterface $profile): bool;

  /**
   * Get profile data from out of office fields.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   Profile entity.
   *
   * @return array
   *   Data from profile OoO fields.
   */
  public function getOutOfOfficeUserMessage(ProfileInterface $profile): array;

  /**
   * Show OoO status message.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   Profile entity.
   */
  public function showOutOfOfficeStatusMessage(ProfileInterface $profile): void;

}
