<?php

declare(strict_types=1);

namespace Drupal\social_profile\TestControlInterface\Dto;

/**
 * Single user row for TCI user (+ default profile) creation.
 *
 * Covers core account fields plus the profile fields actually used by the
 * existing `users:` step across the suite. This is not every possible
 * `field_profile_*` field (the `profile` bundle has dozens); it's scoped to
 * what real feature files rely on, so extend it if a new one is needed
 * rather than trying to support the full field list speculatively.
 */
final readonly class CreateUserRequest {

  /**
   * Constructs a CreateUserRequest object.
   *
   * @param string $name
   *   The username.
   * @param string|null $mail
   *   The email address, or NULL to derive one from the username.
   * @param bool $status
   *   Whether the account is active.
   * @param string|null $pass
   *   The password, or NULL to create the account without a password.
   * @param string|null $timezone
   *   The account timezone.
   * @param string|null $created
   *   A date/time string for the account creation time.
   * @param int|null $uid
   *   An explicit user ID, or NULL to let Drupal assign the next one.
   * @param string|null $uuid
   *   An explicit UUID, or NULL to let Drupal generate one.
   * @param string|null $preferred_langcode
   *   The account's preferred language code.
   * @param string|null $roles
   *   Comma-separated role IDs or labels to grant, in addition to
   *   "authenticated" — a Behat table cell is always a flat string, so this
   *   can't be a list; use roles() to get it parsed. `role` (singular) is
   *   also accepted as an alias, matching a handful of existing feature
   *   files; both are merged by roles().
   * @param string|null $role
   *   Alias for $roles for a single role; see $roles.
   * @param string|null $field_profile_first_name
   *   The profile's first name.
   * @param string|null $field_profile_last_name
   *   The profile's last name.
   * @param string|null $field_profile_nick_name
   *   The profile's nickname. Requires the social_profile_fields module.
   * @param int|null $field_profile_organization_tag
   *   The profile's organization tag, as a "profile_organization_tag" term
   *   ID. Requires the social_profile_organization_tag module.
   */
  public function __construct(
    public string $name,
    public ?string $mail = NULL,
    public bool $status = TRUE,
    public ?string $pass = NULL,
    public ?string $timezone = NULL,
    public ?string $created = NULL,
    public ?int $uid = NULL,
    public ?string $uuid = NULL,
    public ?string $preferred_langcode = NULL,
    public ?string $roles = NULL,
    public ?string $role = NULL,
    public ?string $field_profile_first_name = NULL,
    public ?string $field_profile_last_name = NULL,
    public ?string $field_profile_nick_name = NULL,
    public ?int $field_profile_organization_tag = NULL,
  ) {}

  /**
   * Parses the comma-separated `roles`/`role` cells into role IDs/labels.
   *
   * A Test Control Interface DTO property can't be a list — Behat table
   * cells are always flat strings, and non-scalar values can't be used in
   * Behat test steps — so `roles`/`role` are stored as-is and only turned
   * into a list here, mirroring DrupalContext::createUsers()'s own
   * `explode(',', ...)` parsing of the same column.
   *
   * @return list<string>
   *   Trimmed, non-empty role IDs or labels.
   */
  public function roles(): array {
    $combined = trim(($this->roles ?? '') . ',' . ($this->role ?? ''), ',');
    if ($combined === '') {
      return [];
    }

    return array_values(array_filter(array_map('trim', explode(',', $combined)), static fn (string $role): bool => $role !== ''));
  }

}
