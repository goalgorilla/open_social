<?php

namespace Drupal\social_language\Plugin\Block;

use Drupal\language\Plugin\Block\LanguageBlock;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;

/**
 * Provides a 'LanguageSwitcherBlock' block.
 *
 * @Block(
 *  id = "language_switcher_block",
 *  admin_label = @Translation("Language switcher block"),
 * )
 */
class LanguageSwitcherBlock extends LanguageBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {

    /** @var Language $defaultLanguage */
    $defaultLanguage = $this->languageManager->getCurrentLanguage();

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
    $languagelinks = $this->languageManager->getLanguageSwitchLinks('language_interface', Url::fromRoute($route_name));
    $current_path = \Drupal::service('path.current')->getPath();

    //TODO: use configuration for the prefix instead of assuming ISO.
    $prefixes = \Drupal::config('language.negotiation')->get('url.prefixes');

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
        'url' => (($prefixes[$iso] === '') ? '' : '/' . $prefixes[$iso]) . $current_path,
      ];
    }

    return [
      '#theme' => 'account_header_links',
      '#links' => $links,
      '#cache' => [
        'contexts' => ['user', 'url.path', 'languages'],
      ],
    ];
  }

}