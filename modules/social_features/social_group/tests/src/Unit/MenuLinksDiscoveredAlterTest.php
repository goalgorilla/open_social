<?php

namespace Drupal\Tests\social_group\Unit\Hooks;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\social_group\Hooks\MenuLinksDiscoveredAlter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for MenuLinksDiscoveredAlter.
 *
 * @group social_group
 */
class MenuLinksDiscoveredAlterTest extends TestCase {

  use StringTranslationTrait;

  /**
   * The mocked string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected TranslationInterface|MockObject $stringTranslationMock;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->stringTranslationMock = $this->createMock(TranslationInterface::class);

    $this->stringTranslationMock->method('translate')
      ->willReturnCallback(function ($string) {
        return $string;
      });

    // Set up the service container with the mocked string translation service.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->stringTranslationMock);
    \Drupal::setContainer($container);
  }

  /**
   * Tests the overrideGroupsLabelWithHubsLabel method.
   */
  public function testOverrideGroupsLabelWithHubsLabel(): void {

    $menuLinksAlter = new MenuLinksDiscoveredAlter();

    $menuLinks = [
      'system.admin_group' => [
        'title' => 'Groups',
      ],
      'another.menu_item' => [
        'title' => 'Another Item',
      ],
    ];


    $menuLinksAlter->overrideGroupsLabelWithHubsLabel($menuLinks);

    $this->assertEquals($this->t('Hubs'), $menuLinks['system.admin_group']['title'], 'The menu item title for system.admin_group should be updated to Hubs.');
    $this->assertEquals('Another Item', $menuLinks['another.menu_item']['title'], 'Other menu items should remain unchanged.');
  }

}
