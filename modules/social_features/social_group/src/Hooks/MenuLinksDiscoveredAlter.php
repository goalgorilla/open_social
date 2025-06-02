<?php

namespace Drupal\social_group\Hooks;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hux\Attribute\Alter;

/**
 * Modifies existing menu links.
 */
final class MenuLinksDiscoveredAlter {

  use StringTranslationTrait;

  /**
   * Overrides the label of the "Groups" menu link to "Hubs".
   *
   * @param array $links
   *   The menu links to alter.
   */
  #[Alter('menu_links_discovered')]
  public function overrideGroupsLabelWithHubsLabel(array &$links): void {
    foreach ($links as $key => &$link) {
      if ($key === 'system.admin_group') {
        $link['title'] = $this->t('Hubs');
      }
    }
  }

}
