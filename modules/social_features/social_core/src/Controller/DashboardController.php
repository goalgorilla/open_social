<?php

namespace Drupal\social_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements the DashboardController class.
 *
 * @package Drupal\social_core\Controller
 */
class DashboardController extends ControllerBase {

  /**
   * The theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(ThemeHandlerInterface $theme_handler) {
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('theme_handler'),
    );
  }

  /**
   * Redirect users to current active theme.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect user to current active theme.
   */
  public function getActiveTheme(): RedirectResponse {
    $theme = $this->themeHandler->getDefault();

    return new RedirectResponse(Url::fromUserInput('/admin/appearance/settings/' . $theme)->toString(), 302);
  }

}
