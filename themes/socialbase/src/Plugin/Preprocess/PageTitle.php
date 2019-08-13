<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Pre-processes variables for the "page_title" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("page_title")
 */
class PageTitle extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    // Get the current path and if is it stream return a variable.
    $current_url = Url::fromRoute('<current>');
    $current_path = $current_url->toString();
    $route_name = \Drupal::routeMatch()->getRouteName();

    if ($route_name === 'entity.profile.type.user_profile_form') {
      if ($variables['title'] instanceof TranslatableMarkup) {
        $profile_type = $variables['title']->getArguments();
      }

      if (!empty($profile_type['@label'])) {
        $variables['title'] = t('Edit @label', ['@label' => $profile_type['@label']]);
      }
    }

    if ($route_name === 'entity.user.edit_form' && isset($variables['title']['#markup'])) {
      $variables['title'] = t('<em>Configure account settings:</em> @label', ['@label' => $variables['title']['#markup']]);
    }

    if (strpos($current_path, 'stream') !== FALSE || strpos($current_path, 'explore') !== FALSE) {
      $variables['stream'] = TRUE;
    }

    // Check if it is a node.
    if (strpos($current_path, 'node') !== FALSE) {
      $variables['node'] = TRUE;
    }

    // Check if it is the edit/add/delete.
    if (in_array($route_name, [
      'entity.node.edit_form',
      'entity.node.delete_form',
      'entity.node.add_form',
    ])) {
      $variables['edit'] = TRUE;
    }

  }

}
