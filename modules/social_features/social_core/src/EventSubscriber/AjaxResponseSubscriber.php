<?php

declare(strict_types=1);

namespace Drupal\social_core\EventSubscriber;

use Drupal\better_exposed_filters\Plugin\views\exposed_form\BetterExposedFilters;
use Drupal\views\Ajax\ViewAjaxResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response subscriber to handle AJAX responses.
 */
class AjaxResponseSubscriber implements EventSubscriberInterface {

  /**
   * Renders the ajax commands right before preparing the result.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event, which contains the possible AjaxResponse object.
   */
  public function disableScrollToTopAjaxCommand(ResponseEvent $event): void {
    $response = $event->getResponse();

    // Only alter views ajax responses.
    if (!($response instanceof ViewAjaxResponse)) {
      return;
    }

    $view = $response->getView();

    $plugin = $view->getDisplay()->getPlugin('exposed_form');
    if (!$plugin instanceof BetterExposedFilters) {
      return;
    }

    $exposed_form_settings = $view->getDisplay()->getPlugin('exposed_form')->options ?? [];
    if (
      empty($exposed_form_settings['bef']['sort']['plugin_id']) ||
      $exposed_form_settings['bef']['sort']['plugin_id'] !== 'bef_sort_page_top'
    ) {
      return;
    }

    // We need to unset all scrollTop commands to prevent the page
    // from scrolling to the top.
    $commands = &$response->getCommands();
    foreach ($commands as $delta => &$command) {
      // Stop the view from scrolling to the top of the page.
      if ($command['command'] === 'scrollTop') {
        unset($commands[$delta]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::RESPONSE][] = ['disableScrollToTopAjaxCommand'];

    return $events;
  }

}
