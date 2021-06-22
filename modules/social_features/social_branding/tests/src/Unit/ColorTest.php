<?php

namespace Drupal\Tests\social_branding\Unit;

use Drupal\social_branding\Wrappers\Color;
use Drupal\Tests\UnitTestCase;

/**
 * Color test.
 *
 * @coversDefaultClass \Drupal\social_branding\Wrappers\Color
 * @group social_branding
 */
class ColorTest extends UnitTestCase {

  const HEXRGB = 'ff22aa';

  /**
   * The color.
   *
   * @var \Drupal\social_branding\Wrappers\Color
   */
  protected $color;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->color = new Color('#' . self::HEXRGB);
  }

  /**
   * @covers ::hexRgb
   */
  public function testHexRgb(): void {
    $this->assertEquals(self::HEXRGB, $this->color->hexRgb());
  }

  /**
   * @covers ::css
   */
  public function testCss(): void {
    $this->assertEquals('#' . self::HEXRGB, $this->color->css());
  }

  /**
   * @covers ::red
   */
  public function testRed(): void {
    $this->assertEquals(255, $this->color->red());
  }

  /**
   * @covers ::green
   */
  public function testGreen(): void {
    $this->assertEquals(34, $this->color->green());
  }

  /**
   * @covers ::blue
   */
  public function testBlue(): void {
    $this->assertEquals(170, $this->color->blue());
  }

  /**
   * @covers ::alpha
   */
  public function testAlpha(): void {
    $this->assertEquals(1, $this->color->alpha());
  }

}
