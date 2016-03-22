<?php

/**
 * @file
 * Contains \Drupal\Tests\template_mapper\TemplateMapperTest.
 *
 * @todo This test class is not "good" or complete. It is here to provide
 * Travis with a unit test to be run.
 */

namespace Drupal\Tests\template_mapper;

use Drupal\template_mapper\TemplateMapper;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Test the WorkflowState Class.
 *
 * @coversDefaultClass Drupal\template_mapper\TemplateMapper
 * @group template_mapper
 */
class TemplateMapperTest extends UnitTestCase {

  /**
   * The autocomplete controller.
   *
   * @var Drupal\template_mapper\TemplateMapper;
   */
  protected $templateMapper;


  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    $this->templateMapper = new TemplateMapper($this->getMock('Drupal\Core\Entity\EntityManagerInterface'));
    $this->templateMapper->setAllMappings([
      'user__administrator' => 'user__admin',
      'node__article'  => 'node__piece',
      'views_view__homepage_articles' => 'views_view__illustrated_list',
      'node__teaser' => 'node__illustrated_list_item',
    ]);
  }

  /**
   * Tests the performMapping Method.
   */
  public function testPerformMappingMethod() {

    $existing_suggestions = [
      'node',
      'node__article',
      'node__full',
    ];
    $expect_new_suggestions = [
      'node',
      'node__piece',
      'node__full',
    ];
    $this->assertEquals($expect_new_suggestions, $this->templateMapper->performMapping($existing_suggestions, 'node'));

  }
}
