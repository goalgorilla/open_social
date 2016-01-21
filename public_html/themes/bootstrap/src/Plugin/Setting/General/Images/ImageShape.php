<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\General\Images\ImageShape.
 */

namespace Drupal\bootstrap\Plugin\Setting\General\Images;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;

/**
 * The "image_shape" theme setting.
 *
 * @BootstrapSetting(
 *   id = "image_shape",
 *   type = "select",
 *   title = @Translation("Default image shape"),
 *   description = @Translation("Add classes to an <code>&lt;img&gt;</code> element to easily style images in any project."),
 *   defaultValue = "",
 *   empty_option = @Translation("None"),
 *   groups = {
 *     "general" = @Translation("General"),
 *     "images" = @Translation("Images"),
 *   },
 *   options = {
 *     "img-rounded" = @Translation("Rounded"),
 *     "img-circle" = @Translation("Circle"),
 *     "img-thumbnail" = @Translation("Thumbnail"),
 *   },
 *   see = {
 *     "http://getbootstrap.com/css/#images-shapes" = @Translation("Image Shapes"),
 *   },
 * )
 */
class ImageShape extends SettingBase {}
