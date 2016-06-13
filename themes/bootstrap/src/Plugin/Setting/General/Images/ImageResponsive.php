<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\General\Images\ImageResponsive.
 */

namespace Drupal\bootstrap\Plugin\Setting\General\Images;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;

/**
 * The "image_responsive" theme setting.
 *
 * @BootstrapSetting(
 *   id = "image_responsive",
 *   type = "checkbox",
 *   title = @Translation("Responsive Images"),
 *   description = @Translation("Images in Bootstrap 3 can be made responsive-friendly via the addition of the <code>.img-responsive</code> class. This applies <code>max-width: 100%;</code> and <code>height: auto;</code> to the image so that it scales nicely to the parent element."),
 *   defaultValue = 1,
 *   groups = {
 *     "general" = @Translation("General"),
 *     "images" = @Translation("Images"),
 *   },
 * )
 */
class ImageResponsive extends SettingBase {}
