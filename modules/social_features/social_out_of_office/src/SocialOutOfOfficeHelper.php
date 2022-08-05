<?php

namespace Drupal\social_out_of_office;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\social_profile\SocialProfileNameService;

/**
 * Provides helper methods for Out of office functionality.
 */
class SocialOutOfOfficeHelper implements SocialOutOfOfficeHelperInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The Social Profile name service.
   *
   * @var \Drupal\social_profile\SocialProfileNameService
   */
  protected SocialProfileNameService $socialProfileNameService;

  /**
   * Constructs a new EmbedButtonForm.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The connection to the database.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\social_profile\SocialProfileNameService $social_profile_name_service
   *   The Social Profile name service.
   */
  public function __construct(
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager,
    SocialProfileNameService $social_profile_name_service
  ) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->socialProfileNameService = $social_profile_name_service;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutOfOfficeDates(ProfileInterface $profile): array {
    // Out of office start date.
    $start_date_ooo = !$profile->get('field_profile_ooo_start_date')->isEmpty()
      ? $profile->get('field_profile_ooo_start_date')->getString() : NULL;
    // Out of office end date.
    $end_date_ooo = !$profile->get('field_profile_ooo_end_date')->isEmpty()
      ? $profile->get('field_profile_ooo_end_date')->getString() : NULL;
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

    if (empty($dates)) {
      return FALSE;
    }

    return (
      $dates['start'] !== NULL &&
      $dates['end'] !== NULL &&
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

      $name = $this->socialProfileNameService->getProfileName($profile);
      $message = $profile->get('field_profile_ooo_message')->getValue();
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

    // Do nothing if we cannot retrieve message.
    if (empty($message)) {
      return;
    }

    $this->messenger()->addStatus($this->t('@name is out of office from @start_date to @end_date. @message', [
      '@name' => $message['name'],
      '@start_date' => $message['start_date'],
      '@end_date' => $message['end_date'],
      '@message' => $message['message'] ? '"' . trim(strip_tags($message['message'])) . '"' : '',
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function showOutOfOfficeStatusMessages(array $ids, bool $is_profiles = FALSE): void {
    // Do nothing if there are no IDs.
    if (empty($ids)) {
      return;
    }

    // Date today.
    $today = date('Y-m-d');

    // Load profile IDs by user IDs that are OoO.
    $query = $this->database->select('profile', 'p');
    $query->addField('p', 'profile_id');
    $query->innerJoin('profile__field_profile_ooo_start_date', 'sd', 'p.profile_id = sd.entity_id');
    $query->condition('sd.bundle', self::PROFILE_TYPE);
    $query->condition('sd.field_profile_ooo_start_date_value', $today, '<=');
    $query->innerJoin('profile__field_profile_ooo_end_date', 'ed', 'p.profile_id = ed.entity_id');
    $query->condition('ed.bundle', self::PROFILE_TYPE);
    $query->condition('ed.field_profile_ooo_end_date_value', $today, '>=');
    $query->condition($is_profiles ? 'p.profile_id' : 'p.uid', $ids, 'IN');
    $query->condition('p.status', '1');
    $result = $query->execute();

    $ooo_pids = $result !== NULL ? $result->fetchCol() : [];

    // Do nothing if there are no retrieved OoO Profile IDs.
    if (empty($ooo_pids)) {
      return;
    }

    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->entityTypeManager->getStorage('profile');
    $profiles = $profile_storage->loadMultiple($ooo_pids);

    foreach ($profiles as $profile) {
      if ($profile instanceof ProfileInterface) {
        $this->showOutOfOfficeStatusMessage($profile);
      }
    }
  }

}
