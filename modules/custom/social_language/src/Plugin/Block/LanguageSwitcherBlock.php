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
 *
 * @deprecated in 8.x will be removed in 9.x.
 * @see https://www.drupal.org/project/social/issues/3098046
 */
class LanguageSwitcherBlock extends LanguageBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\Core\Language\Language $currentLanguage */
    $currentLanguage = $this->languageManager->getCurrentLanguage();

    // Build the menu.
    $block = [
      '#attributes' => [
        'class' => ['navbar-user'],
      ],
      'menu_items' => [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#attributes' => [
          'class' => ['nav', 'navbar-nav'],
        ],
        '#items' => [],
      ],
    ];

    // Add `'#icon' => 'language',` to this array to replace the text
    // with an icon.
    $block['menu_items']['#items']['language'] = [
      '#type' => 'account_header_element',
      '#title' => $currentLanguage->getName() . " (" . $currentLanguage->getId() . ")",
      '#label' => $currentLanguage->getName(),
      '#url' => Url::fromRoute('<none>'),
      '#wrapper_attributes' => [
        'class' => ['dropdown'],
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

      $block['menu_items']['#items']['language'][$langcode] = [
        '#type' => 'link',
        '#label' => $link['title'] . " (" . $langcode . ")",
        '#title' => $link['title'] . " (" . $langcode . ")",
        '#url' => $link['url'],
        '#attributes' => [
          'class' => [($langcode === $currentLanguage->getId()) ? 'active' : NULL],
        ],
      ];
    }

    return $block;
  }

}
