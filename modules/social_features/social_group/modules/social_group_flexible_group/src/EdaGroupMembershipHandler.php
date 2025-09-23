<?php

namespace Drupal\social_group_flexible_group;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\Entity\GroupMembershipInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\Application;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\Entity;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;
use Drupal\social_group_flexible_group\Event\GroupMembershipEntityData;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles hook invocations for EDA related operations of group membership.
 */
final class EdaGroupMembershipHandler {

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
   * The community namespace.
   *
   * @var string
   */
  protected string $namespace;

  /**
   * The topic name.
   *
   * @var string
   */
  protected string $topicName;

  /**
   * The request time.
   *
   * @var int
   */
  protected int $requestTime;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    private readonly UuidInterface $uuid,
    private readonly RequestStack $requestStack,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $account,
    private readonly RouteMatchInterface $routeMatch,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly ?DispatcherInterface $dispatcher = NULL,
  ) {
    // Load the full user entity if the account is authenticated.
    $account_id = $this->account->id();
    if ($account_id && $account_id > 0) {
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

    // Set the community namespace.
    $this->namespace = $this->configFactory->get('social_eda.settings')->get('namespace') ?? 'com.getopensocial';

    // Set the topic name.
    $this->topicName = "{$this->namespace}.cms.group_membership.v1";

    // Set the request time.
    $this->requestTime = $this->time->getRequestTime();
  }

  /**
   * Create group membership handler (direct join).
   */
  public function groupMembershipCreate(GroupMembershipInterface $membership): void {
    $this->dispatch(
      $this->topicName,
      "{$this->namespace}.cms.group_membership.create",
      $membership
    );
  }

  /**
   * Delete group membership handler (leave group).
   */
  public function groupMembershipDelete(GroupMembershipInterface $membership): void {
    $this->dispatch(
      $this->topicName,
      "{$this->namespace}.cms.group_membership.delete",
      $membership
    );
  }

  /**
   * Request to join group handler.
   */
  public function groupMembershipRequestCreate(GroupRelationshipInterface $request): void {
    $this->dispatch(
      $this->topicName,
      "{$this->namespace}.cms.group_membership.request.create",
      $request
    );
  }

  /**
   * Request to join group cancelled handler.
   */
  public function groupMembershipRequestDelete(GroupRelationshipInterface $request): void {
    $this->dispatch(
      $this->topicName,
      "{$this->namespace}.cms.group_membership.request.delete",
      $request
    );
  }

  /**
   * Request to join group accepted handler.
   */
  public function groupMembershipRequestAccept(GroupRelationshipInterface $request): void {
    $this->dispatch(
      $this->topicName,
      "{$this->namespace}.cms.group_membership.request.accept",
      $request
    );
  }

  /**
   * Transforms a group membership or request/invitation into a CloudEvent.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function fromEntity(GroupMembershipInterface|GroupRelationshipInterface $entity, string $event_type): CloudEvent {
    // Determine actors.
    [$actor_application, $actor_user] = $this->determineActors();

    // Define all status mappings.
    $status_mappings = [
      // Direct membership statuses.
      "{$this->namespace}.cms.group_membership.create" => 'active',
      "{$this->namespace}.cms.group_membership.delete" => 'removed',
      "{$this->namespace}.cms.group_membership.request.create" => 'request_pending',
      "{$this->namespace}.cms.group_membership.request.delete" => 'request_cancelled',
      "{$this->namespace}.cms.group_membership.request.accept" => 'active',
    ];

    // Get group and user.
    $group = $entity->getGroup();
    $account = $entity->getEntity();

    $user_data = [];
    // Enrollee is an authenticated user.
    if ($account instanceof UserInterface && !$account->isAnonymous()) {
      $user_data = [
        'id' => (string) $account->uuid(),
        'displayName' => (string) $account->getDisplayName(),
        'email' => NULL,
        'href' => Href::fromEntity($account),
      ];
    }
    else {
      // The user is an external user.
      $email = NULL;
      if ($entity->hasField('invitee_mail') && !$entity->get('invitee_mail')->isEmpty()) {
        $email = $entity->get('invitee_mail')->value ?? NULL;
      }

      // Email is always required to be present.
      if ($email) {
        $user_data = [
          'id' => NULL,
          'displayName' => NULL,
          'email' => $email,
          'href' => NULL,
        ];
      }
      else {
        // Log warning about missing email for external invitation.
        $this->loggerFactory->get('social_group_flexible_group')
          ->warning('External invitation missing email address for entity @id', [
            '@id' => $entity->id(),
          ]);

        // Set a placeholder or throw exception based on requirements.
        $user_data = [
          'id' => NULL,
          'displayName' => NULL,
          'email' => NULL,
          'href' => NULL,
        ];
      }
    }

    // Get user roles in the group (if any).
    $roles = [];
    if ($entity->hasField('group_roles')) {
      $role_values = $entity->get('group_roles')->getValue();
      foreach ($role_values as $role_value) {
        if (isset($role_value['target_id'])) {
          $role_id = $role_value['target_id'];
          // Strip the group type prefix, we just want to keep "group_manager".
          if (strpos($role_id, '-') !== FALSE) {
            $role_parts = explode('-', $role_id, 2);
            $roles[] = $role_parts[1];
          }
          else {
            $roles[] = $role_id;
          }
        }
      }
    }

    return new CloudEvent(
      id: $this->uuid->generate(),
      source: $this->source,
      type: $event_type,
      data: [
        'groupMembership' => new GroupMembershipEntityData(
          id: (string) $entity->uuid(),
          created: DateTime::fromTimestamp($entity->getCreatedTime())->toString(),
          updated: DateTime::fromTimestamp($entity->getChangedTime())->toString(),
          status: $status_mappings[$event_type] ?? 'unknown',
          roles: $roles,
          group: Entity::fromEntity($group),
          user: $user_data,
        ),
        'actor' => [
          'application' => $actor_application ? Application::fromId($actor_application) : NULL,
          'user' => $actor_user ? User::fromEntity($actor_user) : NULL,
        ],
      ],
      dataContentType: 'application/json',
      dataSchema: NULL,
      subject: NULL,
      time: DateTime::fromTimestamp($this->requestTime)->toImmutableDateTime(),
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

    if ($this->routeName == 'entity.ultimate_cron_job.run') {
      $application = 'cron';
    }

    return [
      $application,
      $user,
    ];
  }

  /**
   * Dispatches the event for any group membership entity.
   *
   * @param string $topic_name
   *   The topic name.
   * @param string $event_type
   *   The event type.
   * @param \Drupal\group\Entity\GroupMembershipInterface|\Drupal\group\Entity\GroupRelationshipInterface $entity
   *   The entity object (membership, request, or invitation).
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function dispatch(string $topic_name, string $event_type, GroupMembershipInterface|GroupRelationshipInterface $entity): void {
    // Skip if required modules are not enabled.
    if (!$this->moduleHandler->moduleExists('social_eda') || !$this->dispatcher) {
      return;
    }

    try {
      // Build the event.
      $event = $this->fromEntity($entity, $event_type);

      // Dispatch to message broker.
      $this->dispatcher->dispatch($topic_name, $event);
    }
    catch (\Throwable $e) {
      // Log the error but don't interrupt the group membership flow.
      $logger = $this->loggerFactory->get('social_group_flexible_group');
      $logger->error('Failed to dispatch EDA event for group membership. Topic: @topic, Event type: @event_type, Group Membership ID: @membership_id, Error: @error', [
        '@topic' => $topic_name,
        '@event_type' => $event_type,
        '@membership_id' => $entity->id(),
        '@error' => $e->getMessage(),
      ]);
    }

  }

}
