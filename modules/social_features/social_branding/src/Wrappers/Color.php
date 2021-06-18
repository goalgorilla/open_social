<?php

namespace Drupal\social_branding\Wrappers;

use Spatie\Color\Hex;

/**
 * A representation of theming color.
 */
class Color {

  /**
   * The color as hexadecimal string..
   *
   * @var string
   */
  private string $hex;

  /**
   * The color representation as individual RGBA components.
   *
   * @var \Drupal\social_branding\Wrappers\RGBAColor
   */
  private RGBAColor $rgba;

  /**
   * The color representation that's valid in css style rules.
   *
   * @var string
   */
  private string $css;

  /**
   * Create a new Color instance.
   *
   * @param string $color
   *   The determined color.
   */
  public function __construct(string $color) {
    $hexColor = Hex::fromString($color);

    $this->hex = $hexColor->red() . $hexColor->green() . $hexColor->blue();
    $this->rgba = new RGBAColor($color);
    $this->css = $hexColor->__toString();
  }

  /**
   * Get the Hex color.
   *
   * @return string
   *   The color as hexadecimal string.
   */
  public function getHex() : string {
    return $this->hex;
  }

  /**
   * Get the RGBA color.
   *
   * @return \Drupal\social_branding\Wrappers\RGBAColor
   *   The color representation as individual RGBA components.
   */
  public function getRgba() : RGBAColor {
    return $this->rgba;
  }

  /**
   * Get the CSS color.
   *
   * @return string
   *   The color representation that's valid in css style rules.
   */
  public function getCss() : string {
    return $this->css;
  }

}
