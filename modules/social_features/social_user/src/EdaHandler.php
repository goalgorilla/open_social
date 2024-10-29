<?php

namespace Drupal\social_user;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\Address;
use Drupal\social_eda\Types\Application;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;
use Drupal\social_user\Event\UserEventData;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles hook invocations for EDA related operations of the user entity.
 */
final class EdaHandler {

  /**
   * The current logged-in user.
   *
   * @var \Drupal\user\UserInterface|null
   */
  protected ?UserInterface $currentUser = NULL;

  /**
   * The source.
   *
   * @var string
   */
  protected string $source;

  /**
   * The current route name.
   *
   * @var string
   */
  protected string $routeName;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    private readonly ?DispatcherInterface $dispatcher,
    private readonly UuidInterface $uuid,
    private readonly RequestStack $requestStack,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $account,
    private readonly RouteMatchInterface $routeMatch,
  ) {
    // Load the full user entity if the account is authenticated.
    $account_id = $this->account->id();
    if ($account_id && $account_id !== 0) {
      $user = $this->entityTypeManager->getStorage('user')->load($account_id);
      if ($user instanceof UserInterface) {
        $this->currentUser = $user;
      }
    }

    // Set source.
    $request = $this->requestStack->getCurrentRequest();
    $this->source = $request ? $request->getPathInfo() : '';

    // Set route name.
    $this->routeName = $this->routeMatch->getRouteName() ?: '';
  }

  /**
   * Create user handler.
   */
  public function userCreate(UserInterface $user): void {
    $event_type = 'com.getopensocial.cms.user.create';
    $topic_name = 'com.getopensocial.cms.user.create';
    $this->dispatch($topic_name, $event_type, $user);
  }

  /**
   * Transforms a NodeInterface into a CloudEvent.
   */
  public function fromEntity(UserInterface $user, string $event_type): CloudEvent {
    // Determine actors.
    [$actor_application, $actor_user] = $this->determineActors();

    // Query for the user's profile based on the user ID and profile type.
    $profileStorage = $this->entityTypeManager->getStorage('profile');

    /** @var \Drupal\profile\Entity\ProfileInterface[] $profiles */
    $profiles = $profileStorage->loadByProperties([
      'uid' => $user->id(),
      'type' => 'profile',
    ]);

    // If there is a profile, retrieve the first one.
    $profile = !empty($profiles) ? reset($profiles) : NULL;

    if ($profile) {
      $first_name = $profile->get('field_profile_first_name')->value;
      $last_name = $profile->get('field_profile_last_name')->value;
      $address = $profile->get('field_profile_address')->value;
      $phone_number = $profile->get('field_profile_phone_number')->value;
      $function = $profile->get('field_profile_function')->value;
      $organization = $profile->get('field_profile_organization')->value;
    }

    return new CloudEvent(
      id: $this->uuid->generate(),
      source: $this->source,
      type: $event_type,
      data: [
        'user' => new UserEventData(
          id: $user->get('uuid')->value,
          created: DateTime::fromTimestamp($user->getCreatedTime())->toString(),
          updated: DateTime::fromTimestamp($user->getChangedTime())->toString(),
          status: $user->isActive() ? 'active' : 'blocked',
          displayName: $user->getDisplayName(),
          firstName: $first_name ?? '',
          lastName: $last_name ?? '',
          email: (string) $user->getEmail(),
          roles: array_values($user->getRoles()),
          timezone: $user->getTimeZone(),
          language: $user->getPreferredLangcode(),
          address: Address::fromFieldItem(
            item: $address ?? NULL,
          ),
          phone: $phone_number ?? '',
          function: $function ?? '',
          organization: $organization ?? '',
          href: Href::fromEntity($user),
        ),
        'actor' => [
          'application' => $actor_application ? Application::fromId($actor_application) : NULL,
          'user' => $actor_user ? User::fromEntity($actor_user) : NULL,
        ],
      ],
      dataContentType: 'application/json',
      dataSchema: NULL,
      subject: NULL,
      time: DateTime::fromTimestamp($user->getCreatedTime())->toImmutableDateTime(),
    );
  }

  /**
   * Determines the actor (application and user) for the CloudEvent.
   *
   * @return array
   *   An array with two elements: the application and the user.
   */
  private function determineActors(): array {
    $application = NULL;
    $user = NULL;

    if ($this->currentUser instanceof UserInterface) {
      $user = $this->currentUser;
    }

    return [
      $application,
      $user,
    ];
  }

  /**
   * Dispatches the event.
   *
   * @param string $topic_name
   *   The topic name.
   * @param string $event_type
   *   The event type.
   * @param \Drupal\user\UserInterface $user
   *   The user object.
   */
  private function dispatch(string $topic_name, string $event_type, UserInterface $user): void {
    // Skip if required modules are not enabled.
    if (!$this->moduleHandler->moduleExists('social_eda') || !$this->dispatcher) {
      return;
    }

    // Build the event.
    $event = $this->fromEntity($user, $event_type);

    // Dispatch to message broker.
    $this->dispatcher->dispatch($topic_name, $event);
  }

}
