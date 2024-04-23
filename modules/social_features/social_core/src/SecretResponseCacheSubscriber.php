<?php

declare(strict_types=1);

namespace Drupal\social_core;

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
   * Set the cache max age based on image cache lifetimes.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event, which contains created response.
   */
  public function onResponse(ResponseEvent $event) {
    $response = $event->getResponse();
    $request = $event->getRequest();
    if ($response->isCacheable() && $request->attributes->has('secret_file.max_age')) {
      $secret_max_age = $request->attributes->get('secret_file.max_age');
      assert(is_int($secret_max_age) && $secret_max_age > 0, "'secret_file.max_age' was set on the request but it's not a positive integer.");

      $max_age = min($secret_max_age, $response->getMaxAge());

      $response->setCache([
        'max_age' =>  $max_age,
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::RESPONSE][] = ['onResponse', 0];

    return $events;
  }

}
