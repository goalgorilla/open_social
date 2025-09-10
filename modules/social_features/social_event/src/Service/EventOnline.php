<?php

declare(strict_types=1);

namespace Drupal\social_event\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\meeting_api\Entity\MeetingType;
use Drupal\meeting_api\MeetingInterface;
use Drupal\meeting_api\ServerInterface;

/**
 * Service for handling event online meeting functionality.
 */
class EventOnline {

  use StringTranslationTrait;

  /**
   * Maximum allowed concurrent BigBlueButton meetings attendees.
   *
   * @var int
   */
  public const MAX_CONCURRENT_BBB_ATTENDEES = 200;

  /**
   * The default number of attendees.
   *
   * @var int
   */
  public const DEFAULT_MEETING_ATTENDEES = 2;

  /**
   * Constructs a new EventOnline object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Retrieves the backend ID for a given meeting.
   *
   * @param \Drupal\meeting_api\MeetingInterface $meeting
   *   The meeting entity for which to retrieve the backend ID.
   *
   * @return string
   *   The backend ID of the server,
   *   or an empty string if the server is invalid.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getMeetingBackendId(MeetingInterface $meeting): string {
    $server_id = $meeting->getServerId();

    // Load the server entity.
    $server = $this->entityTypeManager
      ->getStorage('meeting_api_server')
      ->load($server_id);

    if (!$server instanceof ServerInterface) {
      return '';
    }

    return $server->get('backend');
  }

  /**
   * Get available meeting types.
   *
   * This method returns all meeting types that pass validation,
   * specifically checking BigBlueButton backend configuration.
   *
   * @return \Drupal\meeting_api\Entity\MeetingType[]
   *   Array of meeting type entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAvailableMeetingTypes(): array {
    $meeting_types = $this->entityTypeManager
      ->getStorage('meeting_api_meeting_type')
      ->loadMultiple();

    foreach ($meeting_types as $meeting_type_id => $meeting_type) {
      if ($meeting_type instanceof MeetingType && $this->validateMeetingTypeUsage($meeting_type)) {
        $available_types[$meeting_type_id] = $meeting_type;
      }
    }

    return $available_types ?? [];
  }

  /**
   * Convert meeting types to options array.
   *
   * @return array
   *   Array of meeting type options keyed by ID with labels as values.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getMeetingTypesAsOptionsList(): array {
    $meeting_types = $this->getAvailableMeetingTypes();

    $options = array_map(
      callback: fn($meetingType) => $meetingType->label(),
      array: $meeting_types
    );

    asort($options, SORT_NATURAL | SORT_FLAG_CASE);

    return $options;
  }

  /**
   * Checks if at least one BigBlueButton server is properly configured.
   *
   * @return bool
   *   TRUE if at least one BigBlueButton server is properly configured.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function isBigBlueButtonServerConfigured(): bool {
    $meeting_api_servers = $this->entityTypeManager
      ->getStorage('meeting_api_server')
      ->loadMultiple();

    $bbb_servers = array_filter(
      array: $meeting_api_servers,
      callback: fn ($server) => $server instanceof ServerInterface && $server->get('backend') === 'bigbluebutton');

    foreach ($bbb_servers as $bbb_server) {
      if ($this->validateServerConfiguration($bbb_server)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Validates if a meeting type can be used.
   *
   * This method checks if the meeting type has a BigBlueButton backend
   * and if that backend is properly configured with URL and key.
   *
   * @param \Drupal\meeting_api\Entity\MeetingType|string $meeting_type
   *   The meeting type entity to validate.
   *
   * @return bool
   *   TRUE if the meeting type can be used, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function validateMeetingTypeUsage(MeetingType|string $meeting_type): bool {
    if (is_string($meeting_type)) {
      $meeting_type = $this->entityTypeManager
        ->getStorage('meeting_api_meeting_type')
        ->load($meeting_type);
    }

    if (!$meeting_type instanceof MeetingType) {
      return FALSE;
    }

    // Get the server ID from the meeting type.
    $serverId = $meeting_type->get('server_id');
    if (empty($serverId)) {
      // Allow meeting types without specific server requirements.
      return TRUE;
    }

    return $this->validateServerConfiguration($serverId);
  }

  /**
   * Validates if a server is properly configured for BigBlueButton.
   *
   * @param \Drupal\meeting_api\ServerInterface|string $server
   *   The server entity or server ID to validate.
   *
   * @return bool
   *   TRUE if the server is properly configured, FALSE otherwise.
   */
  protected function validateServerConfiguration(ServerInterface|string $server): bool {
    // Load the server entity.
    if (is_string($server)) {
      try {
        $server = $this->entityTypeManager
          ->getStorage('meeting_api_server')
          ->load($server);
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
        $this->loggerFactory->get('social_event')->error($e->getMessage());
        return FALSE;
      }

    }

    if (!$server instanceof ServerInterface) {
      return FALSE;
    }

    // Check if this is a BigBlueButton backend.
    $backend = $server->get('backend');
    if ($backend !== 'bigbluebutton') {
      // Allow non-BigBlueButton backends.
      return TRUE;
    }

    // For BigBlueButton backends, validate configuration.
    return $this->serverConfigurationIsValid($server);
  }

  /**
   * Checks whether the server configuration is valid for BigBlueButton.
   *
   * @param \Drupal\meeting_api\ServerInterface $server
   *   The server entity to validate.
   *
   * @return bool
   *   TRUE if the server configuration is invalid or incomplete,
   *   FALSE otherwise.
   */
  private function serverConfigurationIsValid(ServerInterface $server): bool {
    $backendConfig = $server->get('backend_config');

    return empty($backendConfig) ||
      empty($backendConfig['url']) ||
      empty($backendConfig['key']);
  }

}
