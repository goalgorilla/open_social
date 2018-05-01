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

    // Get the language links.
    $languagelinks = $this->languageManager->getLanguageSwitchLinks('language_interface', Url::createFromRequest(\Drupal::request()));
    // Use the default URL generator that does not rewrite the language.
    $url_generator = \Drupal::service('drupal8_url_generator');

    // Add languages as links.
    foreach ($languagelinks->links as $iso => $languagelink) {
      $url_options = [];
      $url_options['query'] = $languagelink['query'];
      $url_options['language'] = $languagelink['language'];
      $languagelink['url']->setOptions($url_options);
      $languagelink['url']->setUrlGenerator($url_generator);
      $url_string = $languagelink['url']->toString();

      $links['language']['below'][$iso] = [
        'classes' => '',
        'link_attributes' => '',
        'link_classes' => ($iso === $defaultLanguage) ? 'active' : '',
        'icon_classes' => '',
        'icon_label' => '',
        'label' => $languagelink['title'] . " (" . $iso . ")",
        'title' => $languagelink['title'] . " (" . $iso . ")",
        'title_classes' => '',
        'url' =>  $url_string,
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