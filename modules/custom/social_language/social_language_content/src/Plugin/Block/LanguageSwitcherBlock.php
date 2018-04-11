<?php

namespace Drupal\social_language_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;

/**
 * Provides a 'LanguageSwitcherBlock' block.
 *
 * @Block(
 *  id = "social_language_content_language_switcher_block",
 *  admin_label = @Translation("Language switcher block"),
 * )
 */
class LanguageSwitcherBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Fetch the languagemanager.
    $languageManager = \Drupal::languageManager();

    // Site must be multilingual to show this menu.
    if ($languageManager->isMultilingual() === FALSE) {
      return [];
    }

    /** @var Language $defaultLanguage */
    $defaultLanguage = $languageManager->getCurrentLanguage();

    // Build the menu.
    $links = [
      'language' => [
        'classes' => 'dropdown',
        'link_attributes' => 'data-toggle=dropdown aria-expanded=true aria-haspopup=true role=button',
        'link_classes' => 'dropdown-toggle clearfix',
        'icon_classes' => 'icon-language',
        'label' => $defaultLanguage->getId(),
        'title' => $defaultLanguage->getName() . " (" . $defaultLanguage->getId() . ")",
        'title_classes' => 'navlabel-language pull-left',
        'url' => '#',
      ],
    ];

    // Get the languages.
    $route_name = \Drupal::routeMatch()->getRouteName();
    $languagelinks = $languageManager->getLanguageSwitchLinks('language_interface', Url::fromRoute($route_name));
    $current_path = \Drupal::service('path.current')->getPath();

    // Add languages as links.
    foreach ($languagelinks->links as $iso => $languagelink) {
      $links['language']['below'][$iso] = [
        'classes' => '',
        'link_attributes' => '',
        'link_classes' => ($iso === $defaultLanguage) ? 'active' : '',
        'icon_classes' => '',
        'icon_label' => '',
        'label' => $languagelink['title'] . " (" . $iso . ")",
        'title' => $languagelink['title'] . " (" . $iso . ")",
        'title_classes' => '',
        'url' => (($iso === 'en') ? '' : '/' . $iso) . $current_path,
      ];
    }

    return [
      '#theme' => 'account_header_links',
      '#links' => $links,
      '#cache' => [
        'contexts' => ['user', 'url.path'],
      ],s
    ];
  }

}