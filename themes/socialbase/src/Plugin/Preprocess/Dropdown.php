<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Utility\Variables;
use Drupal\bootstrap\Plugin\Preprocess\BootstrapDropdown;

/**
 * Pre-processes variables for the "bootstrap_dropdown" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("bootstrap_dropdown")
 */
class Dropdown extends BootstrapDropdown {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    $operations = !!mb_strpos($variables['theme_hook_original'], 'operations');
    $route = \Drupal::routeMatch()->getRouteName();

    if ($operations &&  ($route === 'view.event_manage_enrollments.page_manage_enrollments' || $route === 'view.group_manage_members.page_group_manage_members')) {
      $variables['default_button'] = FALSE;
      $variables['toggle_label'] = $this->t('Actions');
    }

    parent::preprocess($variables, $hook, $info);

    if (isset($variables['items']['#items']['publish']['element']['#button_type']) && $variables['items']['#items']['publish']['element']['#button_type'] === 'primary') {
      $variables['alignment'] = 'right';

      if (isset($variables['toggle'])) {
        $variables['toggle']['#button_type'] = 'primary';
        $variables['toggle']['#button_level'] = 'raised';

      }

    }
  }

  /**
   * Function to preprocess the links.
   */
  protected function preprocessLinks(Variables $variables) {
    parent::preprocessLinks($variables);

    $operations = !!mb_strpos($variables->theme_hook_original, 'operations');

    // Make operations button small, not smaller ;).
    // Bootstrap basetheme override.
    if ($operations) {
      $variables->toggle['#attributes']['class'] = ['btn-sm'];
      $variables['btn_context'] = 'operations';
      $variables['alignment'] = 'right';
    }

  }

}
