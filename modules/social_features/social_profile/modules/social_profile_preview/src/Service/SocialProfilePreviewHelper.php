<?php

namespace Drupal\social_profile_preview\Service;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Defines the helper service.
 */
class SocialProfilePreviewHelper implements SocialProfilePreviewHelperInterface {

  /**
   * The configuration factory.
   */
  private ConfigFactoryInterface $configFactory;

  /**
   * The theme manager.
   */
  private ThemeManagerInterface $themeManager;

  /**
   * The module handler.
   */
  private ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ThemeManagerInterface $theme_manager,
    ModuleHandlerInterface $module_handler
  ) {
    $this->configFactory = $config_factory;
    $this->themeManager = $theme_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function alter(
    ProfileInterface $profile,
    array &$variables,
    $path = 'attributes',
    bool $return_as_object = FALSE,
    string $base_field = NULL,
    string $extra_field = NULL
  ): void {
    if (
      $profile->access('view') &&
      $this->configFactory->get('system.theme')->get('default') === $this->themeManager->getActiveTheme()->getName()
    ) {
      if ($extra_field !== NULL && !empty($variables[$extra_field])) {
        $variables[$base_field] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $variables[$base_field],
        ];

        $path = [$base_field, '#attributes'];
        $return_as_object = FALSE;
      }

      if (!NestedArray::keyExists($variables, $path = (array) $path)) {
        NestedArray::setValue($variables, $path, []);
      }

      $attributes = &NestedArray::getValue($variables, $path);

      if ($is_object = $attributes instanceof Attribute) {
        $attributes = $attributes->toArray();
      }

      $attributes['class'][] = 'profile-preview';
      $attributes['data-profile'] = $profile->id();

      if ($is_object || $return_as_object) {
        $attributes = new Attribute($attributes);
      }

      $variables['#attached']['library'][] = 'social_profile_preview/base';

      if ($this->moduleHandler->moduleExists('social_follow_user')) {
        $variables['#attached']['library'][] = 'social_follow_user/counter';
      }
    }
  }

}
