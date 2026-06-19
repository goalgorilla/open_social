<?php

declare(strict_types=1);

namespace Drupal\Tests\social_analytics\Unit;

use CloudEvents\V1\CloudEventInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\social_analytics\IndividualMetricsEdaHandler;
use Drupal\social_eda\DispatcherInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\social_analytics\IndividualMetricsEdaHandler
 *
 * @group social_analytics
 */
final class IndividualMetricsEdaHandlerTest extends UnitTestCase {

  private const PROJECT_ID = 'test-project-id';

  /**
   * Original Settings snapshot restored after each test.
   *
   * @var array<string, mixed>
   */
  private array $originalSettings = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    try {
      Settings::getInstance();
      $this->originalSettings = Settings::getAll();
    }
    catch (\BadMethodCallException) {
      $this->originalSettings = [];
      new Settings([]);
    }

    new Settings(array_merge($this->originalSettings, [
      'project_id' => self::PROJECT_ID,
    ]));

    $language = $this->createMock(LanguageInterface::class);
    $language->method('getId')->willReturn('en');
    $language_manager = $this->createMock(LanguageManagerInterface::class);
    $language_manager->method('getCurrentLanguage')->willReturn($language);

    $container = new ContainerBuilder();
    $container->set('language_manager', $language_manager);
    \Drupal::setContainer($container);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    new Settings($this->originalSettings);
    parent::tearDown();
  }

  /**
   * @covers ::fromPreferenceChange
   */
  public function testFromPreferenceChangeBuildsExpectedPayload(): void {
    $handler = $this->createHandler();
    $user = $this->createTargetUser('target-user-uuid');

    $event = $handler->fromPreferenceChange($user, TRUE);

    self::assertSame('com.getopensocial.cms.user.settings.analytics', $event->getType());
    self::assertSame('/my-settings', $event->getSource());
    self::assertSame('target-user-uuid', $event->getData()['user']['id']);
    self::assertTrue($event->getData()['analytics']['showInIndividualMetrics']);
    self::assertSame('actor-user-uuid', $event->getData()['actor']->user->id);
    self::assertSame('Actor User', $event->getData()['actor']->user->displayName);
  }

  /**
   * @covers ::dispatchPreferenceChange
   */
  public function testDispatchPreferenceChangeDispatchesToUserTopic(): void {
    $dispatcher = $this->createMock(DispatcherInterface::class);
    $dispatcher->expects(self::once())
      ->method('dispatch')
      ->with(
        'com.getopensocial.cms.user.v1',
        self::callback(static function (CloudEventInterface $event): bool {
          return $event->getType() === 'com.getopensocial.cms.user.settings.analytics'
            && $event->getData()['user']['id'] === 'target-user-uuid'
            && $event->getData()['analytics']['showInIndividualMetrics'] === FALSE;
        }),
      );

    $handler = $this->createHandler($dispatcher);
    $handler->dispatchPreferenceChange($this->createTargetUser('target-user-uuid'), FALSE);
  }

  /**
   * @covers ::dispatchPreferenceChange
   */
  public function testDispatchPreferenceChangeSkipsWithoutDispatcher(): void {
    $handler = $this->createHandler(with_dispatcher: FALSE);

    $dispatcher_property = new \ReflectionProperty(IndividualMetricsEdaHandler::class, 'dispatcher');
    self::assertNull($dispatcher_property->getValue($handler));

    $handler->dispatchPreferenceChange($this->createTargetUser('target-user-uuid'), TRUE);
  }

  /**
   * @covers ::dispatchPreferenceChange
   */
  public function testDispatchPreferenceChangeSkipsWhenSocialEdaDisabled(): void {
    $dispatcher = $this->createMock(DispatcherInterface::class);
    $dispatcher->expects(self::never())->method('dispatch');

    $handler = $this->createHandler($dispatcher, social_eda_enabled: FALSE);
    $handler->dispatchPreferenceChange($this->createTargetUser('target-user-uuid'), TRUE);
  }

  /**
   * Creates a handler with common mocked dependencies.
   */
  private function createHandler(
    ?DispatcherInterface $dispatcher = NULL,
    bool $social_eda_enabled = TRUE,
    int $current_user_id = 1,
    array $current_user_roles = ['authenticated'],
    bool $with_dispatcher = TRUE,
  ): IndividualMetricsEdaHandler {
    if (!$with_dispatcher) {
      $resolved_dispatcher = NULL;
    }
    elseif ($dispatcher === NULL) {
      $dispatcher = $this->createMock(DispatcherInterface::class);
      $dispatcher->method('dispatch');
      $resolved_dispatcher = $dispatcher;
    }
    else {
      $resolved_dispatcher = $dispatcher;
    }

    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->method('moduleExists')
      ->with('social_eda')
      ->willReturn($social_eda_enabled);

    $request = $this->createMock(Request::class);
    $request->method('getPathInfo')->willReturn('/my-settings');
    $request_stack = $this->createMock(RequestStack::class);
    $request_stack->method('getCurrentRequest')->willReturn($request);

    $actor_user = $this->createMock(UserInterface::class);
    $actor_user->method('uuid')->willReturn('actor-user-uuid');
    $actor_user->method('getDisplayName')->willReturn('Actor User');
    $actor_user->method('isAnonymous')->willReturn(FALSE);

    $current_user = $this->createMock(AccountProxyInterface::class);
    $current_user->method('id')->willReturn($current_user_id);
    $current_user->method('isAnonymous')->willReturn(FALSE);
    $current_user->method('getRoles')->willReturn($current_user_roles);
    $current_user->method('getAccount')->willReturn($actor_user);

    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getRouteName')->willReturn('entity.user.edit_form');

    $eda_config = $this->createMock(ImmutableConfig::class);
    $eda_config->method('get')->with('namespace')->willReturn('com.getopensocial');
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->with('social_eda.settings')->willReturn($eda_config);

    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(1_700_000_000);

    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->willReturn($this->createMock(LoggerInterface::class));

    return new IndividualMetricsEdaHandler(
      $resolved_dispatcher,
      $module_handler,
      $request_stack,
      $current_user,
      $route_match,
      $config_factory,
      $time,
      $logger_factory,
    );
  }

  /**
   * Creates a target user entity mock.
   */
  private function createTargetUser(string $uuid, int $uid = 1): UserInterface {
    $user = $this->createMock(UserInterface::class);
    $user->method('uuid')->willReturn($uuid);
    $user->method('id')->willReturn($uid);
    return $user;
  }

}
