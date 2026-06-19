<?php

declare(strict_types=1);

namespace Drupal\Tests\social_analytics\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Drupal\social_analytics\AnalyticsSettingsEdaHandler;
use Drupal\social_analytics\Form\SocialAnalyticsSettingsForm;
use Drupal\social_eda\DispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests individual metrics platform settings for site managers.
 *
 * @group social_analytics
 * @coversDefaultClass \Drupal\social_analytics\Form\SocialAnalyticsSettingsForm
 */
final class SocialAnalyticsSettingsFormTest extends KernelTestBase {

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
  protected static $modules = [
    'system',
    'user',
    'social_analytics',
  ];

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
    }

    new Settings(array_merge($this->originalSettings, [
      'project_id' => self::PROJECT_ID,
    ]));

    $this->installConfig(['social_analytics']);

    $request = Request::create('/admin/config/opensocial/analytics');
    $this->container->get('request_stack')->push($request);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    new Settings($this->originalSettings);
    parent::tearDown();
  }

  /**
   * @covers ::buildForm
   */
  public function testDefaultPlatformSettingsAreDisabled(): void {
    $config = $this->config('social_analytics.settings');
    self::assertFalse($config->get('individual_metrics_preference_visibility_enabled'));
    self::assertFalse($config->get('individual_metrics_show_by_default'));
  }

  /**
   * @covers ::submitForm
   */
  public function testSettingsFormPersistsPlatformSettings(): void {
    $this->submitSettingsForm([
      'individual_metrics_preference_visibility_enabled' => TRUE,
      'individual_metrics_show_by_default' => TRUE,
    ]);

    $config = $this->config('social_analytics.settings');
    self::assertTrue($config->get('individual_metrics_preference_visibility_enabled'));
    self::assertTrue($config->get('individual_metrics_show_by_default'));
  }

  /**
   * @covers ::submitForm
   */
  public function testSubmitFormDispatchesWhenDefaultValueChanges(): void {
    $dispatcher = $this->createMock(DispatcherInterface::class);
    $dispatcher->expects(self::once())
      ->method('dispatch')
      ->with(
        'com.getopensocial.cms.analytics.v1',
        self::callback(static fn ($event): bool => $event->getType() === 'com.getopensocial.cms.analytics.settings'),
      );
    $this->replaceAnalyticsSettingsEdaHandler($dispatcher);

    $this->submitSettingsForm([
      'individual_metrics_preference_visibility_enabled' => FALSE,
      'individual_metrics_show_by_default' => TRUE,
    ]);
  }

  /**
   * @covers ::submitForm
   */
  public function testSubmitFormDoesNotDispatchWhenDefaultUnchanged(): void {
    $this->config('social_analytics.settings')
      ->set('individual_metrics_show_by_default', TRUE)
      ->save();

    $dispatcher = $this->createMock(DispatcherInterface::class);
    $dispatcher->expects(self::never())->method('dispatch');
    $this->replaceAnalyticsSettingsEdaHandler($dispatcher);

    $this->submitSettingsForm([
      'individual_metrics_preference_visibility_enabled' => TRUE,
      'individual_metrics_show_by_default' => TRUE,
    ]);
  }

  /**
   * @covers ::submitForm
   */
  public function testSubmitFormDoesNotDispatchWhenOnlyVisibilityChanges(): void {
    $dispatcher = $this->createMock(DispatcherInterface::class);
    $dispatcher->expects(self::never())->method('dispatch');
    $this->replaceAnalyticsSettingsEdaHandler($dispatcher);

    $this->submitSettingsForm([
      'individual_metrics_preference_visibility_enabled' => TRUE,
      'individual_metrics_show_by_default' => FALSE,
    ]);
  }

  /**
   * Submits the platform settings form with the given values.
   *
   * @param array<string, bool> $values
   *   Form values keyed by element name.
   */
  private function submitSettingsForm(array $values): void {
    $form_state = (new FormState())->setValues($values);
    $form = [];
    SocialAnalyticsSettingsForm::create($this->container)->submitForm($form, $form_state);
  }

  /**
   * Replaces the analytics settings EDA handler with one using a test dispatcher.
   */
  private function replaceAnalyticsSettingsEdaHandler(DispatcherInterface $dispatcher): void {
    $module_handler = $this->createMock(\Drupal\Core\Extension\ModuleHandlerInterface::class);
    $module_handler->method('moduleExists')
      ->willReturnCallback(static fn (string $module): bool => $module === 'social_eda');

    $handler = new AnalyticsSettingsEdaHandler(
      $dispatcher,
      $module_handler,
      $this->container->get('request_stack'),
      $this->container->get('current_user'),
      $this->container->get('current_route_match'),
      $this->container->get('config.factory'),
      $this->container->get('datetime.time'),
      $this->container->get('logger.factory'),
    );
    $this->container->set('social_analytics.analytics_settings_eda_handler', $handler);
  }

}
