<?php

namespace Drupal\social_pwa;

/**
 * The payload for push notifications handled by the social_pwa service worker.
 *
 * The data in this class should be kept in sync by the data expected in the
 * event listener for the `push` event in the `sw.js` file in this module.
 */
class WebPushPayload {

  /**
   * @var string
   *   The type of the web push payload used to select a push handler in the
   *   service worker.
   */
  private $type;

  /**
   * @var array
   *   The data for the push handler in the service worker.
   */
  private $data;

  /**
   * Create a new payload for a web push notification.
   *
   * @param string $type
   *   The type of the web push payload used to select a push handler in the
   *   service worker.
   * @param array $data
   *   The data for the push handler in the service worker.
   */
  public function __construct(string $type, array $data = []) {
    $this->type = $type;
    $this->data = $data;
  }

  /**
   * Get the type of the web push payload.
   *
   * @return string
   *   The type of the web push payload.
   */
  public function getType(): string {
    return $this->type;
  }

  /**
   * Set the type of the web push payload.
   *
   * @param string $type
   *   The type of the web push payload.
   */
  public function setType(string $type): void {
    $this->type = $type;
  }

  /**
   * Get the data for the web push handler.
   *
   * @return array
   *   The data for the web push handler.
   */
  public function getData(): array {
    return $this->data;
  }

  /**
   * Set the data for the web push handler.
   *
   * @param array $data
   *   The data for the web push handler.
   */
  public function setData(array $data): void {
    $this->data = $data;
  }

  /**
   * Get the values of this payload as array.
   *
   * @return array
   *   An array containing the values desired by the service worker.
   */
  public function toArray() : array {
    // If this is a legacy message we serialize slightly differently so that we
    // still work with previously installed service workers.
    // TODO: Remove this on September 1st 2021 at which point most service
    // workers should have updated since a push notification triggers an update
    // check.
    if ($this->type === 'legacy') {
      return array_merge(
      // Support the modern format for our newer service workers.
        [
          'type' => $this->type,
          'data' => $this->data,
        ],
        // The old format was just all available fields in the root.
        $this->data
      );
    }

    return [
      'type' => $this->type,
      'data' => $this->data,
    ];
  }

  /**
   * Get the payload as a JSON string.
   *
   * @return string
   *   The JSON string that can be passed to the Web Push library.
   */
  public function toJson() : string {
    try {
      return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $e) {
      return "";
    }
  }

}
