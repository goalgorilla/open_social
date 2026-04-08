<?php

declare(strict_types=1);

namespace Drupal\Tests\social_magic_login\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\social_magic_login\Controller\MagicLoginController;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Tests the MagicLoginController.
 *
 * @group social_magic_login
 */
class MagicLoginControllerTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'social_magic_login',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['user', 'social_magic_login']);
  }

  /**
   * Builds the controller from the container.
   */
  protected function controller(): MagicLoginController {
    return MagicLoginController::create($this->container);
  }

  /**
   * Generates a valid magic link hash for a user at a given timestamp.
   */
  protected function hashFor(UserInterface $user, int $timestamp): string {
    return user_pass_rehash($user, $timestamp);
  }

  /* --------------------------------------------------------------------- */
  /* Defensive short-circuit: same user already logged in.                 */
  /* --------------------------------------------------------------------- */

  /**
   * Test replayed link for same logged in user redirects silently.
   *
   * When the user is already logged in as the same uid and the link is stale
   * (timestamp < lastLoginTime), the controller must redirect silently to the
   * destination instead of showing a "one-time link expired" message.
   */
  public function testReplayedLinkForSameLoggedInUserRedirectsSilently(): void {
    $user = $this->createUser([], NULL, FALSE, ['pass' => 'secret']);
    self::assertInstanceOf(UserInterface::class, $user);
    $user->setLastLoginTime(\Drupal::time()->getRequestTime());
    $user->save();

    $this->setCurrentUser($user);

    $stale_timestamp = \Drupal::time()->getRequestTime() - 60;
    $hash = $this->hashFor($user, $stale_timestamp);

    $response = $this->controller()->login(
      (int) $user->id(),
      $stale_timestamp,
      $hash,
      base64_encode('/user/' . $user->id())
    );

    self::assertInstanceOf(RedirectResponse::class, $response);
    self::assertStringContainsString('/user/' . $user->id(), $response->getTargetUrl());
    self::assertEmpty(
      \Drupal::messenger()->messagesByType('error'),
      'No "expired" error message must be set when the user is already logged in.'
    );
  }

  /**
   * Test replayed link with invalid destination falls back to front.
   *
   * The buggy mobile app replay case: destination is passed as raw "<front>"
   * instead of base64-encoded, producing garbage after base64_decode. The
   * silent redirect must fall back to the front page.
   */
  public function testReplayedLinkWithInvalidDestinationFallsBackToFront(): void {
    $user = $this->createUser([], NULL, FALSE, ['pass' => 'secret']);
    self::assertInstanceOf(UserInterface::class, $user);
    $user->setLastLoginTime(\Drupal::time()->getRequestTime());
    $user->save();

    $this->setCurrentUser($user);

    $stale_timestamp = \Drupal::time()->getRequestTime() - 60;
    $hash = $this->hashFor($user, $stale_timestamp);

    $response = $this->controller()->login(
      (int) $user->id(),
      $stale_timestamp,
      $hash,
      '<front>'
    );

    self::assertInstanceOf(RedirectResponse::class, $response);
    self::assertEmpty(
      \Drupal::messenger()->messagesByType('error'),
      'No "expired" error must surface for an idempotent replay.'
    );
  }

  /* --------------------------------------------------------------------- */
  /* Existing branches: expiry, hash mismatch, another user, invalid user. */
  /* --------------------------------------------------------------------- */

  /**
   * Test stale link for anonymous user still reports expired.
   *
   * Anonymous user with a stale link must still see the legitimate "expired"
   * error. The defensive short-circuit must not hide real expiry errors.
   */
  public function testStaleLinkForAnonymousUserStillReportsExpired(): void {
    $user = $this->createUser([], NULL, FALSE, ['pass' => 'secret']);
    self::assertInstanceOf(UserInterface::class, $user);
    $user->setLastLoginTime(\Drupal::time()->getRequestTime());
    $user->save();

    $stale_timestamp = \Drupal::time()->getRequestTime() - 60;
    $hash = $this->hashFor($user, $stale_timestamp);

    $response = $this->controller()->login(
      (int) $user->id(),
      $stale_timestamp,
      $hash,
      base64_encode('/user/' . $user->id())
    );

    self::assertInstanceOf(RedirectResponse::class, $response);
    self::assertNotEmpty(
      \Drupal::messenger()->messagesByType('error'),
      'Genuine expiry must still flash an error for anonymous users.'
    );
  }

  /**
   * A link with a bad hash must flash the "invalid" error and redirect.
   */
  public function testInvalidHashFlashesInvalidMessage(): void {
    $user = $this->createUser([], NULL, FALSE, ['pass' => 'secret']);
    self::assertInstanceOf(UserInterface::class, $user);
    // Keep lastLoginTime at 0 so timestamp check passes.
    $user->setLastLoginTime(0);
    $user->save();

    $timestamp = \Drupal::time()->getRequestTime();

    $response = $this->controller()->login(
      (int) $user->id(),
      $timestamp,
      'definitely-not-a-valid-hash',
      base64_encode('/user/' . $user->id())
    );

    self::assertInstanceOf(RedirectResponse::class, $response);
    $errors = \Drupal::messenger()->messagesByType('error');
    self::assertNotEmpty($errors, 'An error must be flashed for an invalid hash.');
    self::assertStringContainsString(
      'invalid',
      (string) reset($errors),
      'The flashed error must mention the link is invalid.'
    );
  }

  /**
   * Test different logged in user throws access denied.
   *
   * A magic link targeting a different user than the one currently logged in
   * must throw AccessDenied and flash a warning.
   */
  public function testDifferentLoggedInUserThrowsAccessDenied(): void {
    $logged_in = $this->createUser([], NULL, FALSE, ['pass' => 'secret']);
    self::assertInstanceOf(UserInterface::class, $logged_in);
    $target = $this->createUser([], NULL, FALSE, ['pass' => 'other']);
    self::assertInstanceOf(UserInterface::class, $target);
    $target->setLastLoginTime(0);
    $target->save();

    $this->setCurrentUser($logged_in);

    $timestamp = \Drupal::time()->getRequestTime();
    $hash = $this->hashFor($target, $timestamp);

    $this->expectException(AccessDeniedHttpException::class);
    $this->controller()->login(
      (int) $target->id(),
      $timestamp,
      $hash,
      base64_encode('/user/' . $target->id())
    );
  }

  /**
   * A link for a blocked/deleted user must throw AccessDenied immediately.
   */
  public function testInactiveUserThrowsAccessDenied(): void {
    $user = $this->createUser([], NULL, FALSE, ['pass' => 'secret']);
    self::assertInstanceOf(UserInterface::class, $user);
    $user->block();
    $user->save();

    $timestamp = \Drupal::time()->getRequestTime();
    $hash = $this->hashFor($user, $timestamp);

    $this->expectException(AccessDeniedHttpException::class);
    $this->controller()->login(
      (int) $user->id(),
      $timestamp,
      $hash,
      base64_encode('/user/' . $user->id())
    );
  }

  /**
   * A non-existent uid must throw AccessDenied.
   */
  public function testUnknownUserThrowsAccessDenied(): void {
    $timestamp = \Drupal::time()->getRequestTime();

    $this->expectException(AccessDeniedHttpException::class);
    $this->controller()->login(
      9999999,
      $timestamp,
      'whatever',
      base64_encode('/')
    );
  }

}
