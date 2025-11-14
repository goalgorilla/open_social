<?php

namespace Drupal\social_core\Hooks;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\hux\Attribute\Hook;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Converts absolute same-host menu link URLs into internal: paths on save.
 */
class MenuLinkEntityPreSave {

  /**
   * The request stack.
   */
  private RequestStack $requestStack;

  /**
   * MenuLinkEntityPreSave constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * Create a new instance of the class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   *
   * @return static
   *   The instance of the class.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('request_stack'),
    );
  }

  /**
   * Converts absolute same-host URLs into internal: paths during presave.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   */
  #[Hook('entity_presave')]
  public function convertInternalAbsoluteLinks(EntityInterface $entity): void {
    if (!$entity instanceof MenuLinkContentInterface) {
      return;
    }

    $link_item = $entity->get('link')->first();
    if (!$link_item || $link_item->isEmpty()) {
      return;
    }

    $values = $link_item->getValue();
    if (empty($values['uri'])) {
      return;
    }

    $uri = $values['uri'];

    if (!UrlHelper::isExternal($uri)) {
      return;
    }

    if (!$this->isSameHost($uri)) {
      return;
    }

    $parsed = parse_url($uri);
    $path = $parsed['path'] ?? '/';

    $request = $this->requestStack->getCurrentRequest();
    $base_path = '';
    if ($request !== NULL) {
      $base_path = $request->getBasePath();
    }

    if ($base_path !== '' && str_starts_with($path, $base_path)) {
      $path = substr($path, strlen($base_path));
    }

    $path = '/' . ltrim($path, '/');

    $internal_uri = 'internal:' . $path;

    if (!empty($parsed['query'])) {
      $internal_uri .= '?' . $parsed['query'];
    }

    if (!empty($parsed['fragment'])) {
      $internal_uri .= '#' . $parsed['fragment'];
    }

    $link_item->setValue([
      'uri' => $internal_uri,
      'title' => $values['title'] ?? NULL,
    ]);
  }

  /**
   * Checks whether a given URI points to the same host.
   *
   * @param string $uri
   *   The absolute URI to evaluate.
   *
   * @return bool
   *   TRUE if the host matches the site's host, FALSE otherwise.
   */
  private function isSameHost(string $uri): bool {
    $parsed = parse_url($uri);
    if (empty($parsed['host'])) {
      return FALSE;
    }

    $request = $this->requestStack->getCurrentRequest();
    if ($request === NULL) {
      return FALSE;
    }

    $current_host = $request->getHost();

    return strcasecmp($parsed['host'], $current_host) === 0;
  }

}
