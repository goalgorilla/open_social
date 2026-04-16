<?php

declare(strict_types=1);

namespace Drupal\Tests\social_user\Unit\Hooks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\social_user\Hooks\UserRegisterFormHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\social_user\Hooks\UserRegisterFormHooks
 * @group social_user
 */
final class UserRegisterFormHooksTest extends UnitTestCase {

  private const LOGIN_URL = '/user/login';

  /**
   * System under test.
   */
  private UserRegisterFormHooks $hooks;

  /**
   * Mocked social_user settings config.
   */
  private ImmutableConfig&MockObject $config;

  /**
   * Mocked current user used to toggle anonymous vs authenticated flows.
   */
  private AccountInterface&MockObject $currentUser;

  /**
   * Real request stack; tests push requests to simulate URL context.
   */
  private RequestStack $requestStack;

  /**
   * Mocked URL generator; default returns a stub login URL.
   */
  private UrlGeneratorInterface&MockObject $urlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // TranslatableMarkup::__toString() reaches into \Drupal::translation();
    // provide a minimal container so string casts don't blow up.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->config = $this->createMock(ImmutableConfig::class);
    $this->config->method('getCacheTags')->willReturn(['config:social_user.settings']);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')
      ->with('social_user.settings')
      ->willReturn($this->config);

    $this->currentUser = $this->createMock(AccountInterface::class);
    $this->requestStack = new RequestStack();

    $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
    $this->urlGenerator->method('generateFromRoute')
      ->willReturnCallback(
        fn (string $name, array $params, array $options): string =>
          self::LOGIN_URL . (empty($options['query']) ? '' : '?' . http_build_query($options['query'])),
      );

    $this->hooks = new UserRegisterFormHooks(
      $configFactory,
      $this->currentUser,
      $this->requestStack,
      $this->urlGenerator,
    );
  }

  /**
   * Default-opts-in the "notify" checkbox when access is granted.
   */
  public function testNotifyCheckboxDefaultsToOptedIn(): void {
    $form = [
      'account' => [
        'notify' => ['#access' => TRUE],
      ],
    ];
    $this->alter($form);

    self::assertSame(1, $form['account']['notify']['#default_value']);
  }

  /**
   * Does not touch the "notify" default when access is denied.
   */
  public function testNotifyCheckboxUntouchedWhenAccessDenied(): void {
    $form = [
      'account' => [
        'notify' => ['#access' => FALSE],
      ],
    ];
    $this->alter($form);

    self::assertArrayNotHasKey('#default_value', $form['account']['notify']);
  }

  /**
   * Turns the account container into a socialbase fieldset.
   */
  public function testAccountContainerBecomesFieldset(): void {
    $form = ['account' => []];
    $this->alter($form);

    self::assertSame('fieldset', $form['account']['#type']);
    self::assertSame(
      'Sign up with <b>email</b>',
      (string) $form['account']['#title']->getUntranslatedString(),
    );
  }

  /**
   * Attaches the configured signup help text as description.
   */
  public function testSignupHelpAttachedWhenConfigured(): void {
    $this->config->method('get')->willReturnMap([
      ['signup_help', 'Welcome to our community'],
    ]);

    $form = ['account' => []];
    $this->alter($form);

    self::assertSame('Welcome to our community', $form['account']['#description']);
  }

  /**
   * Leaves description untouched when no help text is configured.
   */
  public function testSignupHelpAbsentWhenNotConfigured(): void {
    $this->config->method('get')->willReturnMap([
      ['signup_help', ''],
    ]);

    $form = ['account' => []];
    $this->alter($form);

    self::assertArrayNotHasKey('#description', $form['account']);
  }

  /**
   * Merges the config cache tags into the form to invalidate on change.
   */
  public function testConfigCacheTagsAreMerged(): void {
    $form = [
      'account' => [],
      '#cache' => ['tags' => ['existing:tag']],
    ];
    $this->alter($form);

    self::assertContains('existing:tag', $form['#cache']['tags']);
    self::assertContains('config:social_user.settings', $form['#cache']['tags']);
  }

  /**
   * Cache tags array is created when the form has none.
   */
  public function testConfigCacheTagsAddedWhenFormHasNoCache(): void {
    $form = ['account' => []];
    $this->alter($form);

    self::assertSame(['config:social_user.settings'], $form['#cache']['tags']);
  }

  /**
   * Applies the expected weights to the core account fields.
   */
  public function testAccountFieldWeightsAreApplied(): void {
    $form = [
      'account' => [
        'mail' => [],
        'name' => [],
        'pass' => [],
      ],
    ];
    $this->alter($form);

    self::assertSame(-150, $form['account']['mail']['#weight']);
    self::assertSame(-100, $form['account']['name']['#weight']);
    self::assertSame(-50, $form['account']['pass']['#weight']);
  }

  /**
   * Missing fields are tolerated without errors.
   */
  public function testAccountFieldWeightsSkipMissingFields(): void {
    $form = ['account' => ['name' => []]];
    $this->alter($form);

    self::assertSame(-100, $form['account']['name']['#weight']);
    self::assertArrayNotHasKey('mail', $form['account']);
    self::assertArrayNotHasKey('pass', $form['account']);
  }

  /**
   * Anonymous users see a "Have an account already?" login link.
   */
  public function testLoginLinkRenderedForAnonymousUser(): void {
    $this->currentUser->method('isAnonymous')->willReturn(TRUE);
    $this->requestStack->push(Request::create('/user/register'));

    $form = ['account' => []];
    $this->alter($form);

    self::assertArrayHasKey('login-link', $form['account']);
    self::assertSame(1000, $form['account']['login-link']['#weight']);
    self::assertSame(['url.query_args'], $form['account']['login-link']['#cache']['contexts']);
  }

  /**
   * The login link preserves the incoming ?destination= query argument.
   */
  public function testLoginLinkPreservesDestinationQueryArg(): void {
    $this->currentUser->method('isAnonymous')->willReturn(TRUE);
    $this->requestStack->push(Request::create('/user/register?destination=/node/1'));

    $this->urlGenerator->expects(self::once())
      ->method('generateFromRoute')
      ->with('user.login', [], ['query' => ['destination' => '/node/1']])
      ->willReturn('/user/login?destination=/node/1');

    $form = ['account' => []];
    $this->alter($form);

    // FormattableMarkup URL-encodes the :url placeholder for XSS safety.
    self::assertStringContainsString(
      '/user/login?destination=%2Fnode%2F1',
      (string) $form['account']['login-link']['#markup'],
    );
  }

  /**
   * Authenticated users (e.g. admins) do not see the login link.
   */
  public function testLoginLinkHiddenForAuthenticatedUser(): void {
    $this->currentUser->method('isAnonymous')->willReturn(FALSE);

    $form = ['account' => []];
    $this->alter($form);

    self::assertArrayNotHasKey('login-link', $form['account']);
  }

  /**
   * The custom validate callback is always appended.
   */
  public function testValidateCallbackIsAlwaysRegistered(): void {
    $form = [];
    $this->alter($form);

    self::assertContains('social_user_register_validate', $form['#validate']);
  }

  /**
   * When there is no account container, only the validate callback is added.
   */
  public function testAlterWithoutAccountContainerOnlyRegistersValidation(): void {
    $form = [];
    $this->alter($form);

    self::assertArrayNotHasKey('account', $form);
    self::assertContains('social_user_register_validate', $form['#validate']);
  }

  /**
   * Invokes the hook with a fresh form-state mock.
   */
  private function alter(array &$form): void {
    $this->hooks->alter($form, $this->createMock(FormStateInterface::class));
  }

}
