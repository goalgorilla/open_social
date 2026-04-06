<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Kernel event subscriber to handle tus OPTIONS requests.
 *
 * Drupal routes do not allow listening to OPTIONS requests since Drupal core
 * tries to take care of this for us automatically based on the registered
 * methods. However, OPTIONS has special meaning in tus so we must listen to
 * Kernel events directly rather than doing this through a controller.
 */
class TusOptionsRequestSubscriber implements EventSubscriberInterface {

  /**
   * Tries to handle the options request.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequest(RequestEvent $event) : void {
    $request = $event->getRequest();
    if ($request->isMethod('OPTIONS') && $request->getPathInfo() === '/api/tus') {
      $event->setResponse(new Response(NULL, Response::HTTP_NO_CONTENT, [
        'Tus-Resumable' => '1.0.0',
        'Tus-Version' => '1.0.0',
        'Tus-Extension' => 'expiration,termination',
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // This should execute before
    // \Drupal\Core\EventSubscriberOptionsRequestSubscriber (priority 1000).
    $events[KernelEvents::REQUEST][] = ['onRequest', 1100];
    return $events;
  }

}
