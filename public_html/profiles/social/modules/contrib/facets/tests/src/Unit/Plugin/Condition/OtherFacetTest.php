<?php

namespace Drupal\Tests\facets\Unit\Plugin\Condition;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\Condition\OtherFacet;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for the 'other facet' condition plugin.
 *
 * @group facets
 */
class OtherFacetTest extends UnitTestCase {

  /**
   * Tests what happens when no values are passed on to the plugin.
   */
  public function testNoValue() {
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $manager = $this->getMockBuilder('\Drupal\Core\Block\BlockManager')
      ->disableOriginalConstructor()
      ->getMock();
    $user = $this->getMock('\Drupal\Core\Session\AccountProxyInterface');
    $facet_manager = $this->getMockBuilder('\Drupal\facets\FacetManager\DefaultFacetManager')
      ->disableOriginalConstructor()
      ->getMock();
    $sut = new OtherFacet($storage, $manager, $user, $facet_manager, [], 'other_facet', '');

    $evaluation = $sut->evaluate();
    $this->assertTrue($evaluation);
  }

  /**
   * Tests the return value of the plugin for a displayed facet.
   */
  public function testDisplayedFacet() {
    $block = $this->getMockBuilder('\Drupal\facets\Plugin\Block\FacetBlock')
      ->disableOriginalConstructor()
      ->getMock();
    $block->expects($this->exactly(1))
      ->method('access')
      ->willReturn(TRUE);
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $manager = $this->getMockBuilder('\Drupal\Core\Block\BlockManager')
      ->disableOriginalConstructor()
      ->getMock();
    $manager->expects($this->exactly(1))
      ->method('createInstance')
      ->willReturn($block);
    $user = $this->getMock('\Drupal\Core\Session\AccountProxyInterface');
    $facet_manager = $this->getMockBuilder('\Drupal\facets\FacetManager\DefaultFacetManager')
      ->disableOriginalConstructor()
      ->getMock();
    $sut = new OtherFacet($storage, $manager, $user, $facet_manager, ['facets' => 'test'], 'other_facet', '');

    $evaluation = $sut->evaluate();
    $this->assertTrue($evaluation);
  }

  /**
   * Tests the return value of the plugin for a hidden facet.
   */
  public function testHiddenFacet() {
    $block = $this->getMockBuilder('\Drupal\facets\Plugin\Block\FacetBlock')
      ->disableOriginalConstructor()
      ->getMock();
    $block->expects($this->exactly(1))
      ->method('access')
      ->willReturn(FALSE);
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $manager = $this->getMockBuilder('\Drupal\Core\Block\BlockManager')
      ->disableOriginalConstructor()
      ->getMock();
    $manager->expects($this->exactly(1))
      ->method('createInstance')
      ->willReturn($block);
    $user = $this->getMock('\Drupal\Core\Session\AccountProxyInterface');
    $facet_manager = $this->getMockBuilder('\Drupal\facets\FacetManager\DefaultFacetManager')
      ->disableOriginalConstructor()
      ->getMock();
    $sut = new OtherFacet($storage, $manager, $user, $facet_manager, ['facets' => 'test'], 'other_facet', '');

    $evaluation = $sut->evaluate();
    $this->assertFalse($evaluation);
  }

  /**
   * Tests the return value of the plugin for an active facet value.
   */
  public function testActiveFacetValue() {
    $facet = new Facet([], 'facets_facet');
    /** @var \Drupal\facets\Result\ResultInterface[] $results */
    $results = [
      new Result('llama', 'Llama', 1),
      new Result('kitten', 'Kitten', 5),
      new Result('puppy', 'Puppy', 3),
    ];
    $results[0]->setActiveState(TRUE);
    $facet->setResults($results);

    $block = $this->getMockBuilder('\Drupal\facets\Plugin\Block\FacetBlock')
      ->disableOriginalConstructor()
      ->getMock();
    $block->expects($this->exactly(0))->method('access');
    $block->expects($this->exactly(1))
      ->method('getPluginId')
      ->willReturn('block:id');
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->exactly(1))
      ->method('load')
      ->with('id')
      ->willReturn($facet);
    $manager = $this->getMockBuilder('\Drupal\Core\Block\BlockManager')
      ->disableOriginalConstructor()
      ->getMock();
    $manager->expects($this->exactly(1))
      ->method('createInstance')
      ->willReturn($block);
    $user = $this->getMock('\Drupal\Core\Session\AccountProxyInterface');
    $facet_manager = $this->getMockBuilder('\Drupal\facets\FacetManager\DefaultFacetManager')
      ->disableOriginalConstructor()
      ->getMock();
    $facet_manager->expects($this->exactly(1))
      ->method('returnProcessedFacet')
      ->with($facet)
      ->willReturn($facet);

    $configuration = ['facets' => 'test', 'facet_value' => 'llama'];
    $sut = new OtherFacet($storage, $manager, $user, $facet_manager, $configuration, 'other_facet', '');

    $evaluation = $sut->evaluate();
    $this->assertTrue($evaluation);
  }

  /**
   * Tests the return value of the plugin for an inactive facet value.
   */
  public function testInactiveFacetValue() {
    $facet = new Facet([], 'facets_facet');
    /** @var \Drupal\facets\Result\ResultInterface[] $results */
    $results = [
      new Result('llama', 'Llama', 1),
      new Result('kitten', 'Kitten', 5),
      new Result('puppy', 'Puppy', 3),
    ];
    $results[1]->setActiveState(TRUE);
    $facet->setResults($results);

    $block = $this->getMockBuilder('\Drupal\facets\Plugin\Block\FacetBlock')
      ->disableOriginalConstructor()
      ->getMock();
    $block->expects($this->exactly(0))->method('access');
    $block->expects($this->exactly(1))
      ->method('getPluginId')
      ->willReturn('block:id');
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->exactly(1))
      ->method('load')
      ->with('id')
      ->willReturn($facet);
    $manager = $this->getMockBuilder('\Drupal\Core\Block\BlockManager')
      ->disableOriginalConstructor()
      ->getMock();
    $manager->expects($this->exactly(1))
      ->method('createInstance')
      ->willReturn($block);
    $user = $this->getMock('\Drupal\Core\Session\AccountProxyInterface');
    $facet_manager = $this->getMockBuilder('\Drupal\facets\FacetManager\DefaultFacetManager')
      ->disableOriginalConstructor()
      ->getMock();
    $facet_manager->expects($this->exactly(1))
      ->method('returnProcessedFacet')
      ->with($facet)
      ->willReturn($facet);

    $configuration = ['facets' => 'test', 'facet_value' => 'llama'];
    $sut = new OtherFacet($storage, $manager, $user, $facet_manager, $configuration, 'other_facet', '');

    $evaluation = $sut->evaluate();
    $this->assertFalse($evaluation);
  }

}
