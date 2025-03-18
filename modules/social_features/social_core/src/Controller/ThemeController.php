<?php

namespace Drupal\social_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for theme handling.
 */
class ThemeController extends ControllerBase {

  /**
   * The theme handler service.
   */
  protected ThemeHandlerInterface $themeHandler;

  /**
   * Constructs a new ThemeController.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, FormBuilderInterface $form_builder) {
    $this->themeHandler = $theme_handler;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('theme_handler'),
      $container->get('form_builder')
    );
  }

  /**
   * Gets the theme from the request route and build the form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object containing a theme name and a valid token.
   *
   * @return array
   *   Returns the form.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws access denied when no theme is set in the request.
   */
  public function getTheme(Request $request): array {
    // Get the theme.
    $theme = $request->attributes->get('theme');

    // If the route parameter does not contain 'socialblue'.
    if ($theme !== 'socialblue') {
      // Then the user has no access.
      throw new AccessDeniedHttpException();
    }

    // The form we need.
    $form = '\Drupal\system\Form\ThemeSettingsForm';

    // Build the theme settings form with the extracted form and theme from the
    // request.
    return $this->formBuilder->getForm($form, $theme);
  }

}
