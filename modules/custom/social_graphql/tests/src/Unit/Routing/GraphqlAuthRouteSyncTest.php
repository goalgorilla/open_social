<?php

declare(strict_types=1);

namespace Drupal\Tests\social_graphql\Unit\Routing;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\social_graphql\Routing\GraphqlAuthRouteSync;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\social_graphql\Routing\GraphqlAuthRouteSync
 * @group social_graphql
 */
final class GraphqlAuthRouteSyncTest extends UnitTestCase {

  /**
   * Route name owned by the Open Social GraphQL server.
   */
  private const ROUTE_NAME = 'graphql.query.open_social_graphql';

  /**
   * Auth option present before this subscriber runs.
   *
   * @var string[]
   */
  private const UNCHANGED_AUTH = ['cookie', 'oauth2'];

  /**
   * A collection without any GraphQL route is left alone.
   *
   * @covers ::onAlterRoutes
   */
  public function testMissingRouteReturnsWithoutChangingAuth(): void {
    $collection = new RouteCollection();
    $other = new Route('/not-graphql');
    $other->setOption('_auth', self::UNCHANGED_AUTH);
    $collection->add('not_graphql', $other);
    $this->subscriber(
      $this->moduleHandlerExpectingNoChatCheck(),
      $this->configFactoryExpectingNoRead(),
    )->onAlterRoutes(new RouteBuildEvent($collection));

    self::assertNull($collection->get(self::ROUTE_NAME));
    $untouched = $collection->get('not_graphql');
    self::assertNotNull($untouched);
    self::assertSame(self::UNCHANGED_AUTH, $untouched->getOption('_auth'));
  }

  /**
   * @covers ::onAlterRoutes
   */
  public function testChatInstalledLeavesAuthUnchanged(): void {
    $collection = $this->collectionWithGraphqlRoute();
    $this->subscriber(
      $this->moduleHandler(TRUE),
      $this->configFactoryExpectingNoRead(),
    )->onAlterRoutes(new RouteBuildEvent($collection));

    self::assertSame(self::UNCHANGED_AUTH, $this->authOption($collection));
  }

  /**
   * @covers ::onAlterRoutes
   */
  public function testOptOutLeavesAuthUnchanged(): void {
    $collection = $this->collectionWithGraphqlRoute();
    $this->subscriber(
      $this->moduleHandler(FALSE),
      $this->getConfigFactoryStub([
        'social_graphql.settings' => ['allow_session_auth' => TRUE],
      ]),
    )->onAlterRoutes(new RouteBuildEvent($collection));

    self::assertSame(self::UNCHANGED_AUTH, $this->authOption($collection));
  }

  /**
   * @covers ::onAlterRoutes
   */
  public function testLockdownSetsOauthOnly(): void {
    $collection = $this->collectionWithGraphqlRoute();
    $this->subscriber(
      $this->moduleHandler(FALSE),
      $this->getConfigFactoryStub([
        'social_graphql.settings' => ['allow_session_auth' => FALSE],
      ]),
    )->onAlterRoutes(new RouteBuildEvent($collection));

    self::assertSame(['oauth2'], $this->authOption($collection));
  }

  /**
   * Every GraphQL server route is locked; other routes keep their auth.
   *
   * @covers ::onAlterRoutes
   */
  public function testLockdownCoversEveryGraphqlRouteOnly(): void {
    $collection = $this->collectionWithGraphqlRoute();
    $other_server = new Route('/other-graphql');
    $other_server->setDefault('_graphql', TRUE);
    $other_server->setOption('_auth', self::UNCHANGED_AUTH);
    $collection->add('graphql.query.other_server', $other_server);
    $not_graphql = new Route('/not-graphql');
    $not_graphql->setOption('_auth', self::UNCHANGED_AUTH);
    $collection->add('not_graphql', $not_graphql);

    $this->subscriber(
      $this->moduleHandler(FALSE),
      $this->getConfigFactoryStub([
        'social_graphql.settings' => ['allow_session_auth' => FALSE],
      ]),
    )->onAlterRoutes(new RouteBuildEvent($collection));

    self::assertSame(['oauth2'], $this->authOption($collection));
    self::assertSame(['oauth2'], $other_server->getOption('_auth'));
    self::assertSame(self::UNCHANGED_AUTH, $not_graphql->getOption('_auth'));
  }

  /**
   * A site that has not run the update hook yet has no value to read.
   *
   * @covers ::onAlterRoutes
   */
  public function testMissingSettingLocksDown(): void {
    $collection = $this->collectionWithGraphqlRoute();
    $this->subscriber(
      $this->moduleHandler(FALSE),
      $this->getConfigFactoryStub(['social_graphql.settings' => []]),
    )->onAlterRoutes(new RouteBuildEvent($collection));

    self::assertSame(['oauth2'], $this->authOption($collection));
  }

  /**
   * @covers ::onConfigSave
   */
  public function testConfigSaveOtherNameDoesNotRebuild(): void {
    $route_builder = $this->createMock(RouteBuilderInterface::class);
    $route_builder->expects($this->never())->method('setRebuildNeeded');

    $this->subscriber(
      $this->moduleHandlerExpectingNoChatCheck(),
      $this->configFactoryExpectingNoRead(),
      $route_builder,
    )->onConfigSave($this->configEvent('system.site', NULL));
  }

  /**
   * @covers ::onConfigSave
   */
  public function testConfigSaveUnchangedKeyDoesNotRebuild(): void {
    $route_builder = $this->createMock(RouteBuilderInterface::class);
    $route_builder->expects($this->never())->method('setRebuildNeeded');

    $this->subscriber(
      $this->moduleHandlerExpectingNoChatCheck(),
      $this->configFactoryExpectingNoRead(),
      $route_builder,
    )->onConfigSave($this->configEvent('social_graphql.settings', FALSE));
  }

  /**
   * @covers ::onConfigSave
   */
  public function testConfigSaveChangedKeyRebuildsOnce(): void {
    $route_builder = $this->createMock(RouteBuilderInterface::class);
    $route_builder->expects($this->once())->method('setRebuildNeeded');

    $this->subscriber(
      $this->moduleHandlerExpectingNoChatCheck(),
      $this->configFactoryExpectingNoRead(),
      $route_builder,
    )->onConfigSave($this->configEvent('social_graphql.settings', TRUE));
  }

  /**
   * @covers ::getSubscribedEvents
   */
  public function testSubscribedEventsIncludeRouteAlterAndConfigSaveAndDelete(): void {
    $events = GraphqlAuthRouteSync::getSubscribedEvents();

    self::assertSame('onAlterRoutes', $events[RoutingEvents::ALTER]);
    self::assertSame('onConfigSave', $events[ConfigEvents::SAVE]);
    self::assertSame('onConfigSave', $events[ConfigEvents::DELETE]);
  }

  /**
   * Builds the subscriber with the collaborators for one branch.
   */
  private function subscriber(
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory,
    ?RouteBuilderInterface $route_builder = NULL,
  ): GraphqlAuthRouteSync {
    return new GraphqlAuthRouteSync(
      $module_handler,
      $config_factory,
      $route_builder ?? $this->createMock(RouteBuilderInterface::class),
    );
  }

  /**
   * Module handler that must not be asked about Real-Time Chat.
   */
  private function moduleHandlerExpectingNoChatCheck(): ModuleHandlerInterface {
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->expects($this->never())->method('moduleExists');
    return $module_handler;
  }

  /**
   * Module handler whose chat answer is fixed for this test.
   */
  private function moduleHandler(bool $chat_installed): ModuleHandlerInterface {
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->expects($this->once())
      ->method('moduleExists')
      ->with('social_chat')
      ->willReturn($chat_installed);
    return $module_handler;
  }

  /**
   * Config factory that must not be read.
   */
  private function configFactoryExpectingNoRead(): ConfigFactoryInterface {
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->expects($this->never())->method('get');
    return $config_factory;
  }

  /**
   * A collection containing only the Open Social GraphQL route.
   */
  private function collectionWithGraphqlRoute(): RouteCollection {
    $route = new Route('/graphql');
    $route->setDefault('_graphql', TRUE);
    $route->setOption('_auth', self::UNCHANGED_AUTH);
    $collection = new RouteCollection();
    $collection->add(self::ROUTE_NAME, $route);
    return $collection;
  }

  /**
   * The _auth option currently stored on the GraphQL route.
   *
   * @return string[]
   *   Provider ids.
   */
  private function authOption(RouteCollection $collection): array {
    $route = $collection->get(self::ROUTE_NAME);
    self::assertNotNull($route);
    $auth = $route->getOption('_auth');
    self::assertIsArray($auth);
    return $auth;
  }

  /**
   * Config-save event for one config name and one changed flag.
   *
   * @param string $name
   *   Config object name on the event.
   * @param bool|null $allow_session_auth_changed
   *   TRUE or FALSE when the key should be checked, NULL when the name
   *   does not match and the key must not be read.
   */
  private function configEvent(string $name, ?bool $allow_session_auth_changed): ConfigCrudEvent {
    $config = $this->createMock(Config::class);
    $config->expects($this->once())->method('getName')->willReturn($name);

    $event = $this->createMock(ConfigCrudEvent::class);
    $event->expects($this->once())->method('getConfig')->willReturn($config);
    if ($allow_session_auth_changed === NULL) {
      $event->expects($this->never())->method('isChanged');
    }
    else {
      $event->expects($this->once())
        ->method('isChanged')
        ->with('allow_session_auth')
        ->willReturn($allow_session_auth_changed);
    }
    return $event;
  }

}
