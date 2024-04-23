<?php

declare(strict_types=1);

namespace Drupal\secret_file_system;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Modifies the cache lifecycle for responses that contain secret files.
 *
 * There's a disconnect between the cache information for a secret file link and
 * the rest of a Drupal page. To solve this, we manually set the page cache
 * time.
 */
class SecretResponseCacheSubscriber implements EventSubscriberInterface {

  /**
   * Create a new subscriber instance.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The Drupal time service.
   */
  public function __construct(
    protected TimeInterface $time,
  ) {
  }

  /**
   * Set the cache max age based on image cache lifetimes.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event, which contains created response.
   */
  public function onResponse(ResponseEvent $event) : void {
    $response = $event->getResponse();

    // We limit our response to responses which can have images attached.
    if (!$response instanceof AttachmentsInterface) {
      return;
    }

    $attachments = $response->getAttachments();
    if (!isset($attachments['drupalSettings']['secretFiles'])) {
      return;
    }

    // Don't send the secretFiles attachment to the browser.
    $attachedFiles = $attachments['drupalSettings']['secretFiles'];
    unset($attachments['drupalSettings']['secretFiles']);
    $response->setAttachments($attachments);

    // Find the lowest maximum age.
    $expires_at = NULL;
    foreach ($attachedFiles as $file_expires_at) {
      $expires_at = min($expires_at ?? $file_expires_at, $file_expires_at);
    }

    // Propagate the maximum age from the contained images to the response.
    // This is needed until Drupal properly propagates max ages from components
    // to the page: https://www.drupal.org/node/2352009.
    // https://www.lullabot.com/articles/common-max-age-pitfalls-with-drupal-cache
    // for a solid explanation.
    $response->setCache([
      'max_age' => $expires_at - $this->time->getCurrentTime(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::RESPONSE][] = ['onResponse', 0];

    return $events;
  }

}
