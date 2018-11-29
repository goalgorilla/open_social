<?php

namespace Drupal\social_language\Plugin\Block;

use Drupal\language\Plugin\Block\LanguageBlock;
use Drupal\Core\Url;

/**
 * Provides a 'LanguageSwitcherBlock' block.
 *
 * This replaces the Drupal core language switcher block because that block
 * breaks with the SocialLanguageMetadataBubblingUrlGenerator which is needed to
 * keep users within the same language when viewing content (such as posts) in
 * a language other than their current.
 *
 * It also customises the look to match that of the Open Social menubar.
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
    /** @var \Drupal\Core\Language\Language $currentLanguage */
    $currentLanguage = $this->languageManager->getCurrentLanguage();

    // Build the menu.
    $links = [
      'language' => [
        'classes' => 'dropdown',
        'link_attributes' => 'data-toggle=dropdown aria-expanded=true aria-haspopup=true role=button',
        'link_classes' => 'dropdown-toggle clearfix',
        'icon_classes' => 'icon-language',
        'label' => $currentLanguage->getName(),
        'title' => $currentLanguage->getName() . " (" . $currentLanguage->getId() . ")",
        'title_classes' => 'navlabel-language pull-left',
        'url' => '#',
      ],
    ];

    // Generate the routes for the current page.
    $route_name = $this->pathMatcher->isFrontPage() ? '<front>' : '<current>';
    $type = $this->getDerivativeId();
    $switchLinks = $this->languageManager->getLanguageSwitchLinks($type, Url::fromRoute($route_name));

    // Use the default URL generator that does not rewrite the language.
    $url_generator = \Drupal::service('drupal_core_url_generator');

    // Add languages as links.
    foreach ($switchLinks->links as $langcode => $link) {
      $link['url']->setOption('language', $this->languageManager->getLanguage($langcode));
      $link['url']->setUrlGenerator($url_generator);

      $links['language']['below'][$langcode] = [
        'classes' => '',
        'link_attributes' => '',
        'link_classes' => ($langcode === $currentLanguage->getId()) ? 'active' : '',
        'icon_classes' => '',
        'icon_label' => '',
        'label' => $link['title'] . " (" . $langcode . ")",
        'title' => $link['title'] . " (" . $langcode . ")",
        'title_classes' => '',
        'url' => $link['url'],
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
