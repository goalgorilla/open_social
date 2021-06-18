<?php

namespace Drupal\social_branding\Wrappers;

use Spatie\Color\Hex;
use Spatie\Color\Rgba;

/**
 * A color representation with red, green, blue, and alpha components.
 */
class RGBAColor {

  /**
   * The color object.
   *
   * @var \Spatie\Color\Rgba
   */
  private Rgba $color;

  /**
   * Create a new RGBAColor instance.
   *
   * @param string $color
   *   The determined color.
   */
  public function __construct(string $color) {
    $this->color = Hex::fromString($color)->toRgba();
  }

  /**
   * Get red component.
   *
   * @return int
   *   The red component value on a scale of 0-255.
   */
  public function red() : int {
    return $this->color->red();
  }

  /**
   * Get green component.
   *
   * @return int
   *   The green component value on a scale of 0-255.
   */
  public function green() : int {
    return $this->color->green();
  }

  /**
   * Get blue component.
   *
   * @return int
   *   The blue component value on a scale of 0-255.
   */
  public function blue() : int {
    return $this->color->blue();
  }

  /**
   * Get alpha component.
   *
   * @return int
   *   The alpha component value on a scale of 0-255.
   */
  public function alpha() : int {
    return $this->color->alpha();
  }

}
