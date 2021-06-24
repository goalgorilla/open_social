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

  /**
   * @covers ::hexRgb
   */
  public function testColorRepresentationAsHexRgbString(): void {
    $color1 = new Color('#ffffff');

    $this->assertEquals('ffffff', $color1->hexRgb());
    $this->assertIsString($color1->hexRgb());

    $color2 = new Color('#ff22aa');

    $this->assertEquals('ff22aa', $color2->hexRgb());
  }

  /**
   * @covers ::css
   */
  public function testColorRepresentationAsCssString(): void {
    $color1 = new Color('#ffffff');

    $this->assertEquals('#ffffff', $color1->css());
    $this->assertIsString($color1->css());

    $color2 = new Color('#ff22aa');

    $this->assertEquals('#ff22aa', $color2->css());
  }

  /**
   * @covers ::red
   */
  public function testColorRepresentationAsRedComponent(): void {
    $color1 = new Color('#ffffff');

    $this->assertEquals(255, $color1->red());
    $this->assertIsInt($color1->red());

    $color2 = new Color('#0022aa');

    $this->assertEquals(0, $color2->red());
  }

  /**
   * @covers ::green
   */
  public function testColorRepresentationAsGreenComponent(): void {
    $color1 = new Color('#ffffff');

    $this->assertEquals(255, $color1->green());
    $this->assertIsInt($color1->green());

    $color2 = new Color('#0022aa');

    $this->assertEquals(34, $color2->green());
  }

  /**
   * @covers ::blue
   */
  public function testColorRepresentationAsBlueComponent(): void {
    $color1 = new Color('#ffffff');

    $this->assertEquals(255, $color1->blue());
    $this->assertIsInt($color1->blue());

    $color2 = new Color('#0022aa');

    $this->assertEquals(170, $color2->blue());
  }

  /**
   * @covers ::alpha
   */
  public function testColorRepresentationAsAlphaComponent(): void {
    $color1 = new Color('#ffffff');

    $this->assertEquals(1, $color1->alpha());
    $this->assertIsFloat($color1->alpha());

    $color2 = new Color('#0022aa');

    $this->assertEquals(1, $color2->alpha());
  }

}
