<?php

namespace Drupal\social_branding\Wrappers;

use Spatie\Color\Hex;

/**
 * A representation of theming color.
 */
class Color {

  /**
   * The color as hexadecimal string.
   *
   * @var \Spatie\Color\Hex
   */
  private Hex $hex;

  /**
   * Create a new Color instance.
   *
   * @param string $color
   *   The color as a hexadecimal RGB string: e.g. #FF33AA.
   */
  public function __construct(string $color) {
    $this->hex = Hex::fromString($color);
  }

  /**
   * Get the Hex color.
   *
   * @return string
   *   The color as hexadecimal string.
   */
  public function hexRgb() : string {
    return $this->hex->red() . $this->hex->green() . $this->hex->blue();
  }

  /**
   * Get the CSS color.
   *
   * @return string
   *   The color representation that's valid in css style rules.
   */
  public function css() : string {
    return $this->hex->__toString();
  }

  /**
   * Get red component.
   *
   * @return int
   *   The red component value on a scale of 0-255.
   */
  public function red() : int {
    return $this->hex->toRgba()->red();
  }

  /**
   * Get green component.
   *
   * @return int
   *   The green component value on a scale of 0-255.
   */
  public function green() : int {
    return $this->hex->toRgba()->green();
  }

  /**
   * Get blue component.
   *
   * @return int
   *   The blue component value on a scale of 0-255.
   */
  public function blue() : int {
    return $this->hex->toRgba()->blue();
  }

  /**
   * Get alpha component.
   *
   * @return float
   *   The alpha component value on a scale of 0-255.
   */
  public function alpha() : float {
    return $this->hex->toRgba()->alpha();
  }

}
