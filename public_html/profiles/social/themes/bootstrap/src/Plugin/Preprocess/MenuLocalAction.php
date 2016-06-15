<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Preprocess\MenuLocalAction.
 */

namespace Drupal\bootstrap\Plugin\Preprocess;

use Drupal\bootstrap\Annotation\BootstrapPreprocess;
use Drupal\bootstrap\Bootstrap;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Pre-processes variables for the "menu_local_action" theme hook.
 *
 * @ingroup theme_preprocess
 *
 * @BootstrapPreprocess("menu_local_action")
 */
class MenuLocalAction extends PreprocessBase implements PreprocessInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocessElement(Variables $variables, $hook, array $info) {
    $link = $variables->element->getProperty('link');
    $link += ['localized_options' => []];
    $link['localized_options']['set_active_class'] = TRUE;

    $icon = Bootstrap::glyphiconFromString($link['title']);
    $options = isset($link['localized_options']) ? $link['localized_options'] : [];

    if (isset($link['url'])) {
      // Turn link into a mini-button and colorize based on title.
      $class = Bootstrap::cssClassFromString($link['title'], 'default');
      if (!isset($options['attributes']['class'])) {
        $options['attributes']['class'] = [];
      }
      $string = is_string($options['attributes']['class']);
      if ($string) {
        $options['attributes']['class'] = explode(' ', $options['attributes']['class']);
      }
      $options['attributes']['class'][] = 'btn';
      $options['attributes']['class'][] = 'btn-xs';
      $options['attributes']['class'][] = 'btn-' . $class;
      if ($string) {
        $options['attributes']['class'] = implode(' ', $options['attributes']['class']);
      }

      $variables['link'] = [
        '#type' => 'link',
        '#title' => SafeMarkup::format(\Drupal::service('renderer')
            ->render($icon) . '@text', ['@text' => $link['title']]),
        '#options' => $options,
        '#url' => $link['url'],
      ];
    }
    else {
      $variables['link'] = [
        '#type' => 'link',
        '#title' => $link['title'],
        '#options' => $options,
        '#url' => $link['url'],
      ];
    }
  }

}
