<?php

declare(strict_types=1);

namespace Drupal\social_profile\TestControlInterface\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\profile\Entity\Profile;
use Drupal\social_profile\TestControlInterface\Dto\CreateUserRequest;
use Drupal\social_profile\TestControlInterface\Success\UsersBulkCreatedSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Drupal\user\Entity\User;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * TCI endpoint for generic user (+ default profile) creation with roles.
 */
final class CreateUser {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Creates one or more users, optionally granting roles and profile data.
   *
   * @param list<CreateUserRequest> $body
   *   The users to create.
   *
   * @return \Drupal\social_profile\TestControlInterface\Success\UsersBulkCreatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'users_create_bulk',
    patterns: ['users:'],
    type: OperationType::Write,
  )]
  public function createUsers(
    #[Example([['name' => 'user foo', 'roles' => 'verified']])]
    array $body,
  ): UsersBulkCreatedSuccess {
    if ($body === []) {
      throw new BadRequestHttpException('No user rows in request body; refusing to return success with zero users created.');
    }

    $uids = [];
    foreach ($body as $row) {
      $uids[] = $this->createSingleUser($row);
    }

    return new UsersBulkCreatedSuccess(uids: $uids);
  }

  /**
   * Creates a single user and its default profile, and returns its ID.
   */
  private function createSingleUser(CreateUserRequest $row): int {
    if ($this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $row->name]) !== []) {
      throw new BadRequestHttpException(sprintf("A user with username '%s' already exists.", $row->name));
    }
    if ($row->uid !== NULL && User::load($row->uid) !== NULL) {
      throw new BadRequestHttpException(sprintf("A user with ID '%d' already exists.", $row->uid));
    }

    $values = [
      'name' => $row->name,
      'mail' => $row->mail ?? self::deriveMailFromUsername($row->name),
      'status' => $row->status,
    ];
    if ($row->pass !== NULL) {
      $values['pass'] = $row->pass;
    }
    if ($row->timezone !== NULL) {
      $values['timezone'] = $row->timezone;
    }
    if ($row->uid !== NULL) {
      $values['uid'] = $row->uid;
    }
    if ($row->uuid !== NULL) {
      $values['uuid'] = $row->uuid;
    }
    if ($row->preferred_langcode !== NULL) {
      $values['preferred_langcode'] = $row->preferred_langcode;
    }
    $created = $row->created !== NULL ? $this->parseCreated($row->created) : NULL;
    if ($created !== NULL) {
      $values['created'] = $created;
    }

    $user = User::create($values);
    // Triggers social_profile_user_insert(), which creates this user's
    // default "profile" profile synchronously in the same request.
    $user->save();

    $roles = $row->roles();
    if ($roles !== []) {
      foreach ($roles as $role) {
        $user->addRole($this->resolveRoleId($role));
      }
      $user->save();
    }

    $this->setProfileFields($user, $row, $created);

    return (int) $user->id();
  }

  /**
   * Derives an RFC 5322-valid mail address from a username.
   *
   * Feature files routinely use a human-readable display name (which may
   * contain spaces or punctuation) as the username and rely on this method
   * to fill in `mail` when a row doesn't supply one explicitly. A raw
   * `"$name@example.com"` is not safe: a name like "Group member 2" produces
   * "Group member 2@example.com", which `symfony_mailer` refuses to send to
   * ("does not comply with addr-spec of RFC 2822").
   *
   * Any character that isn't valid unquoted `atext` (or the `.` separator)
   * is collapsed to a single `.`, so this only changes the result for a
   * username that wasn't already mapping to a valid address — a name like
   * "user_foo" (already valid: `_` is atext) still maps to
   * "user_foo@example.com", exactly as before.
   */
  public static function deriveMailFromUsername(string $name): string {
    $localPart = strtolower(trim($name));
    // Collapse each run of non-atext characters (whitespace, punctuation
    // outside RFC 5322's allowed set, non-ASCII, ...) into a single '.'.
    // preg_replace() is typed to allow a NULL return (a regex engine
    // failure, e.g. hitting the backtrack limit); fall back to the
    // pre-replace value rather than let NULL flow into the next step.
    $localPart = preg_replace('/[^a-z0-9!#$%&\'*+\/=?^_`{|}~.-]+/', '.', $localPart) ?? $localPart;
    // Dot-atom-text may not start/end with '.' or contain consecutive '.'s,
    // which the collapsing above can still produce (e.g. a literal '.'
    // immediately followed by a collapsed run).
    $localPart = trim(preg_replace('/\.{2,}/', '.', $localPart) ?? $localPart, '.');
    if ($localPart === '') {
      // The name had no atext characters at all (e.g. entirely non-Latin
      // script or punctuation outside the allowed set). Fall back to a
      // deterministic, always-valid address rather than an empty local
      // part.
      $localPart = 'user-' . substr(hash('sha256', $name), 0, 12);
    }

    return $localPart . '@example.com';
  }

  /**
   * Parses a `created` cell into a Unix timestamp.
   */
  private function parseCreated(string $created): int {
    // A bare Unix timestamp (e.g. '1731004953') is a digit string
    // strtotime() can't parse on its own — it needs an '@' prefix for
    // that — but existing `users:` tables already rely on passing one
    // directly, so accept it before falling back to strtotime() for
    // anything else.
    $timestamp = ctype_digit($created) ? (int) $created : strtotime($created);
    if ($timestamp === FALSE) {
      throw new BadRequestHttpException(sprintf("The value '%s' for 'created' is not a valid date/time string.", $created));
    }

    return $timestamp;
  }

  /**
   * Sets the profile fields provided on the request, if any.
   *
   * Besides the explicit `field_profile_*` columns this also mirrors one
   * account column that exists as a field on the profile entity too: the
   * vendor `users:` step's `@afterUserCreate` hook
   * (FeatureContext::alterUserParameters()) looped over `$profile->toArray()`
   * and copied every equally named property off the freshly created account
   * onto the profile, and of the columns this DTO supports, `created`,
   * `status`, `uid` and `uuid` collide that way — but only `created` is
   * actually worth porting:
   * - `created` matters because the newest_users view sorts on
   *   profile.created, so a row pinning an account's creation time has to pin
   *   its profile's creation time as well, or the profile keeps its real
   *   wall-clock time and the view orders the account somewhere else.
   * - `status` is deliberately NOT copied, unlike the hook: a profile's own
   *   status never actually tracks whether its account is blocked in this
   *   product — ProfileStorage::loadByUser() hardcodes `status = TRUE` in
   *   its own query, so an inactive profile becomes invisible to the normal
   *   per-user profile lookup everywhere else, not just "inactive". The
   *   vendor hook copying it was a test-scaffolding quirk, not something
   *   real account-blocking behavior in the product ever does.
   * - `uid` is already pointed at this user by social_profile_user_insert().
   * - `uuid` is deliberately not copied: the hook only did so because both
   *   entity types happen to use that key, and handing the profile the
   *   account's UUID just duplicates one identifier across two entities.
   * The fields stay individually listed rather than copied in a loop so each
   * collision is a deliberate choice; `created` is a profile base field, so
   * it always passes the hasField() check below.
   */
  private function setProfileFields(User $user, CreateUserRequest $row, ?int $created): void {
    $profileFields = [
      'field_profile_first_name' => $row->field_profile_first_name,
      'field_profile_last_name' => $row->field_profile_last_name,
      'field_profile_nick_name' => $row->field_profile_nick_name,
      'field_profile_organization_tag' => $row->field_profile_organization_tag,
    ];
    $profileFields = array_filter($profileFields, static fn ($value): bool => $value !== NULL);
    if ($created !== NULL) {
      $profileFields['created'] = $created;
    }
    if ($profileFields === []) {
      return;
    }

    /** @var \Drupal\profile\ProfileStorageInterface $profileStorage */
    $profileStorage = $this->entityTypeManager->getStorage('profile');
    $profile = $profileStorage->loadByUser($user, 'profile');
    if (!$profile instanceof Profile) {
      throw new BadRequestHttpException(sprintf("User '%s' has no default profile to set profile fields on.", $user->getAccountName()));
    }

    foreach ($profileFields as $fieldName => $value) {
      if (!$profile->hasField($fieldName)) {
        throw new BadRequestHttpException(sprintf("Profile field '%s' does not exist; is the module that provides it installed?", $fieldName));
      }
      $profile->set($fieldName, $value);
    }
    $profile->save();
  }

  /**
   * Resolves a role ID or label to its role ID.
   */
  private function resolveRoleId(string $role): string {
    $storage = $this->entityTypeManager->getStorage('user_role');
    if ($storage->load($role) !== NULL) {
      return $role;
    }
    $matches = $storage->loadByProperties(['label' => $role]);
    $match = reset($matches);
    if ($match === FALSE) {
      throw new BadRequestHttpException(sprintf("Role '%s' does not exist.", $role));
    }

    return (string) $match->id();
  }

}
