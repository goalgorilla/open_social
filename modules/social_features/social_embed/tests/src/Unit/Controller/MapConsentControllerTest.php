<?php

namespace Drupal\Tests\social_embed\Unit\Controller;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\social_embed\Controller\MapConsentController;
use Drupal\Tests\UnitTestCase;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \Drupal\social_embed\Controller\MapConsentController
 * @group social_embed
 */
class MapConsentControllerTest extends UnitTestCase {

  /**
   * The renderer mock.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * The module handler mock.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The entity type manager mock.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The view storage mock.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $viewStorage;

  /**
   * The class under test.
   */
  protected MapConsentController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->renderer = $this->createMock(RendererInterface::class);
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->viewStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('view')
      ->willReturn($this->viewStorage);

    $this->controller = new MapConsentController(
      $this->renderer,
      $this->moduleHandler,
      $this->entityTypeManager,
    );
  }

  /**
   * Tests NotFoundHttpException when plugin_id is missing.
   *
   * @covers ::generate
   */
  public function testGenerateThrowsNotFoundWhenPluginIdMissing(): void {
    $request = Request::create('/social-embed/map-consent', 'GET', [
      'uuid' => 'some-uuid',
    ]);

    $this->expectException(NotFoundHttpException::class);
    $this->controller->generate($request);
  }

  /**
   * Tests NotFoundHttpException when uuid is missing.
   *
   * @covers ::generate
   */
  public function testGenerateThrowsNotFoundWhenUuidMissing(): void {
    $request = Request::create('/social-embed/map-consent', 'GET', [
      'plugin_id' => 'views_block:social_geolocation_members-members_map_block',
    ]);

    $this->expectException(NotFoundHttpException::class);
    $this->controller->generate($request);
  }

  /**
   * Tests AccessDeniedHttpException for a disallowed plugin ID.
   *
   * @covers ::generate
   */
  public function testGenerateThrowsAccessDeniedForDisallowedPlugin(): void {
    $request = Request::create('/social-embed/map-consent', 'GET', [
      'plugin_id' => 'views_block:some_other_view-some_display',
      'uuid' => 'some-uuid',
    ]);

    $this->expectException(AccessDeniedHttpException::class);
    $this->controller->generate($request);
  }

  /**
   * Tests NotFoundHttpException when the module is not enabled.
   *
   * @covers ::generate
   */
  public function testGenerateThrowsNotFoundWhenModuleDisabled(): void {
    $this->moduleHandler
      ->method('moduleExists')
      ->with('social_geolocation_maps')
      ->willReturn(FALSE);

    $request = Request::create('/social-embed/map-consent', 'GET', [
      'plugin_id' => 'views_block:social_geolocation_members-members_map_block',
      'uuid' => 'some-uuid',
    ]);

    $this->expectException(NotFoundHttpException::class);
    $this->controller->generate($request);
  }

  /**
   * Tests NotFoundHttpException when the view entity does not exist.
   *
   * @covers ::generate
   */
  public function testGenerateThrowsNotFoundWhenViewMissing(): void {
    $this->moduleHandler
      ->method('moduleExists')
      ->willReturn(TRUE);

    $this->viewStorage
      ->method('load')
      ->with('social_geolocation_members')
      ->willReturn(NULL);

    $request = Request::create('/social-embed/map-consent', 'GET', [
      'plugin_id' => 'views_block:social_geolocation_members-members_map_block',
      'uuid' => 'some-uuid',
    ]);

    $this->expectException(NotFoundHttpException::class);
    $this->controller->generate($request);
  }

  /**
   * Tests the success path returns an AjaxResponse with a ReplaceCommand.
   *
   * @covers ::generate
   */
  public function testGenerateReturnsAjaxResponseOnSuccess(): void {
    $this->moduleHandler
      ->method('moduleExists')
      ->willReturn(TRUE);

    $view_executable = $this->createMock(ViewExecutable::class);
    $build = [
      '#markup' => '<div>Map content</div>',
      '#attached' => [
        'library' => ['some/library'],
      ],
    ];
    $view_executable
      ->method('setDisplay')
      ->with('members_map_block')
      ->willReturn(TRUE);
    $view_executable
      ->method('setExposedInput');
    $view_executable
      ->method('buildRenderable')
      ->with('members_map_block')
      ->willReturn($build);

    $view_entity = $this->createMock(ViewEntityInterface::class);
    $view_entity
      ->method('getExecutable')
      ->willReturn($view_executable);

    $this->viewStorage
      ->method('load')
      ->with('social_geolocation_members')
      ->willReturn($view_entity);

    $this->renderer
      ->method('renderRoot')
      ->willReturn('<div>Rendered map</div>');

    $uuid = 'test-uuid-5678';
    $request = Request::create('/social-embed/map-consent', 'GET', [
      'plugin_id' => 'views_block:social_geolocation_members-members_map_block',
      'uuid' => $uuid,
    ]);

    $response = $this->controller->generate($request);

    $commands = $response->getCommands();
    $this->assertCount(1, $commands);
    $this->assertEquals('insert', $commands[0]['command']);
    $this->assertEquals('#social-map-placeholder-' . $uuid, $commands[0]['selector']);
    $attachments = $response->getAttachments();
    $this->assertContains('some/library', $attachments['library']);
  }

  /**
   * Tests that no attachments are set when the build has none.
   *
   * @covers ::generate
   */
  public function testGenerateSuccessWithoutAttachments(): void {
    $this->moduleHandler
      ->method('moduleExists')
      ->willReturn(TRUE);

    $view_executable = $this->createMock(ViewExecutable::class);
    $build = ['#markup' => '<div>Map</div>'];
    $view_executable
      ->method('setDisplay')
      ->willReturn(TRUE);
    $view_executable
      ->method('setExposedInput');
    $view_executable
      ->method('buildRenderable')
      ->willReturn($build);

    $view_entity = $this->createMock(ViewEntityInterface::class);
    $view_entity
      ->method('getExecutable')
      ->willReturn($view_executable);

    $this->viewStorage
      ->method('load')
      ->willReturn($view_entity);

    $this->renderer
      ->method('renderRoot')
      ->willReturn('<div>Map</div>');

    $request = Request::create('/social-embed/map-consent', 'GET', [
      'plugin_id' => 'views_block:social_geolocation_groups-groups_map_block',
      'uuid' => 'uuid-no-attach',
    ]);

    $response = $this->controller->generate($request);

    $attachments = $response->getAttachments();
    $this->assertEmpty($attachments['library'] ?? []);
  }

}
