<?php

/**
 * @file
 * Contains \Drupal\Tests\features\Unit\FeaturesBundleTest.
 */

namespace Drupal\Tests\features\Unit;

use Drupal\features\Entity\FeaturesBundle;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Prophecy\Prophet;

/**
 * @coversDefaultClass Drupal\features\Entity\FeaturesBundle
 * @group features
 */
class FeaturesBundleTest extends UnitTestCase {

  public function setUp() {
    parent::setUp();

    // Mock an assigner.
    $manager = new DummyPluginManager();

    // Mock the container.
    $container = $this->prophesize('\Symfony\Component\DependencyInjection\ContainerInterface');
    $container->get('plugin.manager.features_assignment_method')
      ->willReturn($manager);
    \Drupal::setContainer($container->reveal());
  }

  /**
   * @covers ::getEnabledAssignments
   * @covers ::getAssignmentWeights
   * @covers ::getAssignmentSettings
   * @covers ::setAssignmentSettings
   * @covers ::setAssignmentWeights
   * @covers ::setEnabledAssignments
   */
  public function testAssignmentSetting() {
    // Create an entity.
    $settings = [
      'foo' => [
        'enabled' => TRUE,
        'weight' => 0,
        'my_setting' => 42,
      ],
      'bar' => [
        'enabled' => FALSE,
        'weight' => 1,
        'another_setting' => 'value',
      ],
    ];
    $bundle = new FeaturesBundle([
      'assignments' => $settings,
    ], 'features_bundle');

    // Get assignments and attributes.
    $this->assertArrayEquals(
      $bundle->getEnabledAssignments(),
      ['foo' => 'foo'],
      'Can get enabled assignments'
    );
    $this->assertArrayEquals(
      $bundle->getAssignmentWeights(),
      ['foo' => 0, 'bar' => 1],
      'Can get assignment weights'
    );
    $this->assertArrayEquals(
      $bundle->getAssignmentSettings('foo'),
      $settings['foo'],
      'Can get assignment settings'
    );
    $this->assertArrayEquals(
      $bundle->getAssignmentSettings(),
      $settings,
      'Can get all assignment settings'
    );

    // Change settings.
    $settings['foo']['my_setting'] = 97;
    $bundle->setAssignmentSettings('foo', $settings['foo']);
    $this->assertArrayEquals(
      $bundle->getAssignmentSettings('foo'),
      $settings['foo'],
      'Can change assignment settings'
    );

    // Change weights.
    $settings['foo']['weight'] = 1;
    $settings['bar']['weight'] = 0;
    $bundle->setAssignmentWeights(['foo' => 1, 'bar' => 0]);
    $this->assertArrayEquals(
      $bundle->getAssignmentWeights(),
      ['foo' => 1, 'bar' => 0],
      'Can change assignment weights'
    );
    $this->assertArrayEquals(
      $bundle->getAssignmentSettings(),
      $settings,
      'Weight changes are reflected in settings'
    );

    // Enable existing assignment.
    $settings['bar']['enabled'] = TRUE;
    $bundle->setEnabledAssignments(['foo', 'bar']);
    $this->assertArrayEquals(
      $bundle->getEnabledAssignments(),
      ['foo' => 'foo', 'bar' => 'bar'],
      'Can enable assignment'
    );
    $this->assertArrayEquals(
      $bundle->getAssignmentSettings(),
      $settings,
      'Enabled assignment status is reflected in settings'
    );

    // Disable existing assignments.
    $settings['foo']['enabled'] = FALSE;
    $settings['bar']['enabled'] = FALSE;
    $bundle->setEnabledAssignments([]);
    $this->assertArrayEquals(
      $bundle->getEnabledAssignments(),
      [],
      'Can disable assignments'
    );
    $this->assertArrayEquals(
      $bundle->getAssignmentSettings(),
      $settings,
      'Disabled assignment status is reflected in settings'
    );

    // Enable a new assignment.
    $settings['foo']['enabled'] = TRUE;
    $settings['iggy'] = ['enabled' => TRUE, 'weight' => 0, 'new_setting' => 3];
    $bundle->setEnabledAssignments(['foo', 'iggy']);
    $this->assertArrayEquals(
      $bundle->getEnabledAssignments(),
      ['foo' => 'foo', 'iggy' => 'iggy'],
      'Can enable new assignment'
    );
    $bundle->setAssignmentSettings('iggy', $settings['iggy']);
    $this->assertArrayEquals(
      $bundle->getAssignmentSettings(),
      $settings,
      'New enabled assignment status is reflected in settings'
    );

  }

}

/**
 * A dummy plugin manager, to help testing.
 */
class DummyPluginManager {
  public function getDefinition($method_id) {
    $definition = [
      'enabled' => TRUE,
      'weight' => 0,
      'default_settings' => [
        'my_setting' => 42,
      ],
    ];
    return $definition;
  }

}
