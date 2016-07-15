<?php

namespace Drupal\webprofiler;

use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher as BaseTraceableEventDispatcher;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class TraceableEventDispatcher
 */
class TraceableEventDispatcher extends BaseTraceableEventDispatcher {

  /**
   * {@inheritdoc}
   */
  protected function preDispatch($eventName, Event $event) {
    switch ($eventName) {
      case KernelEvents::VIEW:
      case KernelEvents::RESPONSE:
        // stop only if a controller has been executed
        if ($this->stopwatch->isStarted('controller')) {
          $this->stopwatch->stop('controller');
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function postDispatch($eventName, Event $event) {
    switch ($eventName) {
      case KernelEvents::CONTROLLER:
        $this->stopwatch->start('controller', 'section');
        break;
      case KernelEvents::RESPONSE:
        $token = $event->getResponse()->headers->get('X-Debug-Token');
        try {
          $this->stopwatch->stopSection($token);
        } catch (\LogicException $e) {
        }
        break;
      case KernelEvents::TERMINATE:
        // In the special case described in the `preDispatch` method above, the `$token` section
        // does not exist, then closing it throws an exception which must be caught.
        $token = $event->getResponse()->headers->get('X-Debug-Token');
        try {
          $this->stopwatch->stopSection($token);
        } catch (\LogicException $e) {
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getListenerPriority($eventName, $listener) {
    if (!isset($this->listeners[$eventName])) {
      return;
    }
    foreach ($this->listeners[$eventName] as $priority => $listeners) {
      if (FALSE !== ($key = array_search($listener, $listeners, TRUE))) {
        return $priority;
      }
    }
  }

}
