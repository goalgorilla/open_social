<?php

namespace Drupal\social_out_of_office;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Provides helper methods for Out of office functionality.
 */
class SocialOutOfOfficeHelper implements SocialOutOfOfficeHelperInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getOutOfOfficeDates(ProfileInterface $profile): array {
    // Out of office start date.
    $start_date_ooo = !$profile->get('field_profile_start_date_ooo')->isEmpty()
      ? $profile->get('field_profile_start_date_ooo')->getString() : NULL;
    // Out of office end date.
    $end_date_ooo = !$profile->get('field_profile_end_date_ooo')->isEmpty()
      ? $profile->get('field_profile_end_date_ooo')->getString() : NULL;
    // Current date.
    $today = date('Y-m-d');

    return [
      'start' => $start_date_ooo,
      'end' => $end_date_ooo,
      'today' => $today,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isUserOutOfOffice(ProfileInterface $profile): bool {
    // Retrieve out of office dates.
    $dates = $this->getOutOfOfficeDates($profile);

    return (
      $dates['start'] <= $dates['today'] &&
      $dates['end'] >= $dates['today']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getOutOfOfficeUserMessage(ProfileInterface $profile): array {
    // Make sure that user is out of office.
    if ($this->isUserOutOfOffice($profile)) {
      // Retrieve out of office dates.
      $dates = $this->getOutOfOfficeDates($profile);

      $name = $profile->get('profile_name')->getString();
      $message = $profile->get('field_profile_message_ooo')->getValue();
      $message = $message[0]['value'] ?? '';

      return [
        'name' => $name,
        'start_date' => $dates['start'],
        'end_date' => $dates['end'],
        'message' => $message,
      ];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function showOutOfOfficeStatusMessage(ProfileInterface $profile): void {
    $message = $this->getOutOfOfficeUserMessage($profile);

    $this->messenger()->addStatus($this->t('@name is out of office from @start_date to @end_date. @message', [
      '@name' => $message['name'],
      '@start_date' => $message['start_date'],
      '@end_date' => $message['end_date'],
      '@message' => $message['message'] ? '"' . trim(strip_tags($message['message'])) . '"' : '',
    ]));
  }

}
