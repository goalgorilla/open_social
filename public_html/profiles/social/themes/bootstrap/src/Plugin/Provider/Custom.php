<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Provider\Custom.
 */

namespace Drupal\bootstrap\Plugin\Provider;

use Drupal\bootstrap\Annotation\BootstrapProvider;
use Drupal\Core\Annotation\Translation;

/**
 * The "custom" CDN provider plugin.
 *
 * @BootstrapProvider(
 *   id = "custom",
 *   label = @Translation("Custom"),
 * )
 */
class Custom extends ProviderBase {

  /**
   * {@inheritdoc}
   */
  public function getAssets($types = NULL) {
    $this->assets = [];
    foreach ($types as $type) {
      if ($setting = $this->theme->getSetting('cdn_custom_' . $type)) {
        $this->assets[$type][] = $setting;
      }
      if ($setting = $this->theme->getSetting('cdn_custom_' . $type . '_min')) {
        $this->assets['min'][$type][] = $setting;
      }
    }
    return parent::getAssets($types);
  }

}
