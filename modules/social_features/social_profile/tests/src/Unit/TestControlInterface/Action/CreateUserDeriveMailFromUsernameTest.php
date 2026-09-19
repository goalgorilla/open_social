<?php

declare(strict_types=1);

namespace Drupal\Tests\social_profile\Unit\TestControlInterface\Action;

use Drupal\social_profile\TestControlInterface\Action\CreateUser;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for CreateUser::deriveMailFromUsername().
 *
 * @group social_profile
 *
 * @coversDefaultClass \Drupal\social_profile\TestControlInterface\Action\CreateUser
 */
final class CreateUserDeriveMailFromUsernameTest extends UnitTestCase {

  /**
   * Tests that a username maps to the expected RFC 5322-valid address.
   *
   * @covers ::deriveMailFromUsername
   * @dataProvider providerUsernames
   */
  public function testDeriveMailFromUsername(string $name, string $expected): void {
    $this->assertSame($expected, CreateUser::deriveMailFromUsername($name));
  }

  /**
   * Provides usernames and the address they must map to.
   *
   * @return array<string, array{0: string, 1: string}>
   *   Test cases keyed by description.
   */
  public static function providerUsernames(): array {
    return [
      'single word, already valid' => ['Manager', 'manager@example.com'],
      'simple space' => ['Group member 2', 'group.member.2@example.com'],
      'multiple words' => ['Nick Fury', 'nick.fury@example.com'],
      'mixed case is lowercased' => ['ALLCAPS Name', 'allcaps.name@example.com'],
      'leading/trailing/repeated whitespace is trimmed and collapsed' => [
        '  Extra   Spaces  ',
        'extra.spaces@example.com',
      ],
      'underscore is already valid atext and is preserved' => ['user_foo', 'user_foo@example.com'],
      'hyphen is already valid atext and is preserved' => ['Jean-Luc Picard', 'jean-luc.picard@example.com'],
      'apostrophe is already valid atext and is preserved' => ["O'Brien", "o'brien@example.com"],
      'punctuation outside atext collapses, consecutive dots do not appear' => [
        'Dr. Smith, Jr.',
        'dr.smith.jr@example.com',
      ],
      'a literal dot next to collapsed whitespace does not double up' => [
        'Dr.  Smith',
        'dr.smith@example.com',
      ],
    ];
  }

  /**
   * Tests that a name with no valid atext falls back to a stable address.
   *
   * @covers ::deriveMailFromUsername
   */
  public function testDeriveMailFromUsernameFallsBackForNoAtext(): void {
    $mail = CreateUser::deriveMailFromUsername('客户');

    $this->assertMatchesRegularExpression('/^user-[0-9a-f]{12}@example\.com$/', $mail);
  }

  /**
   * Tests that the mapping is deterministic across repeated calls.
   *
   * @covers ::deriveMailFromUsername
   */
  public function testDeriveMailFromUsernameIsDeterministic(): void {
    $this->assertSame(
      CreateUser::deriveMailFromUsername('Group member 2'),
      CreateUser::deriveMailFromUsername('Group member 2'),
    );
    $this->assertSame(
      CreateUser::deriveMailFromUsername('客户'),
      CreateUser::deriveMailFromUsername('客户'),
    );
  }

  /**
   * Tests that different usernames don't collapse to the same fallback.
   *
   * @covers ::deriveMailFromUsername
   */
  public function testDeriveMailFromUsernameFallbackDiffersPerName(): void {
    $this->assertNotSame(
      CreateUser::deriveMailFromUsername('客户'),
      CreateUser::deriveMailFromUsername('顧客'),
    );
  }

}
