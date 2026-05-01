<?php

namespace Drupal\social_group_flexible_group;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\group\Entity\GroupMembershipInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\Actor;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\Entity;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\UuidNamespace;
use Drupal\social_group_flexible_group\Event\GroupMembershipEntityData;
use Drupal\user\UserInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles hook invocations for EDA related operations of group membership.
 */
final class EdaGroupMembershipHandler {

  /**
   * Constructs the EdaGroupMembershipHandler.
   */
  public function __construct(
    private readonly RequestStack $requestStack,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly AccountProxyInterface $currentUser,
    private readonly RouteMatchInterface $routeMatch,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly ?DispatcherInterface $dispatcher = NULL,
  ) {}

  /**
   * Returns the configured EDA namespace prefix.
   */
  private function namespace(): string {
    return $this->configFactory->get('social_eda.settings')->get('namespace') ?? 'com.getopensocial';
  }

  /**
   * Returns the Kafka topic name for group membership events.
   */
  private function topicName(): string {
    return "{$this->namespace()}.cms.group_membership.v1";
  }

  /**
   * Returns the CloudEvents source (request path).
   */
  private function source(): string {
    $request = $this->requestStack->getCurrentRequest();
    return $request ? $request->getPathInfo() : '';
  }

  /**
   * Returns the current route name.
   */
  private function routeName(): string {
    return $this->routeMatch->getRouteName() ?: '';
  }

  /**
   * Returns the request time as a Unix timestamp.
   */
  private function requestTime(): int {
    return $this->time->getRequestTime();
  }

  /**
   * Create group membership handler (direct join).
   */
  public function groupMembershipCreate(GroupMembershipInterface $membership): void {
    $this->dispatch(
      "com.getopensocial.cms.group_membership.create",
      $membership
    );
  }

  /**
   * Delete group membership handler (leave group).
   */
  public function groupMembershipDelete(GroupMembershipInterface $membership): void {
    $this->dispatch(
      "com.getopensocial.cms.group_membership.delete",
      $membership
    );
  }

  /**
   * Request to join group handler.
   */
  public function groupMembershipRequestCreate(GroupRelationshipInterface $request): void {
    $this->dispatch(
      "com.getopensocial.cms.group_membership.request.create",
      $request
    );
  }

  /**
   * Request to join group cancelled handler.
   */
  public function groupMembershipRequestDelete(GroupRelationshipInterface $request): void {
    $this->dispatch(
      "com.getopensocial.cms.group_membership.request.delete",
      $request
    );
  }

  /**
   * Request to join group accepted handler.
   */
  public function groupMembershipRequestAccept(GroupRelationshipInterface $request): void {
    $this->dispatch(
      "com.getopensocial.cms.group_membership.request.accept",
      $request
    );
  }

  /**
   * Request to join group declined handler.
   */
  public function groupMembershipRequestDecline(GroupRelationshipInterface $request): void {
    $this->dispatch(
      "com.getopensocial.cms.group_membership.request.decline",
      $request
    );
  }

  /**
   * Invite to join group handler.
   */
  public function groupMembershipInviteCreate(GroupRelationshipInterface $invitation): void {
    $this->dispatch(
      "com.getopensocial.cms.group_membership.invite.create",
      $invitation
    );
  }

  /**
   * Invite to join group cancelled handler.
   */
  public function groupMembershipInviteDelete(GroupRelationshipInterface $invitation): void {
    $this->dispatch(
      "com.getopensocial.cms.group_membership.invite.delete",
      $invitation
    );
  }

  /**
   * Invite to join group accepted handler.
   */
  public function groupMembershipInviteAccept(GroupRelationshipInterface $invitation): void {
    $this->dispatch(
      "com.getopensocial.cms.group_membership.invite.accept",
      $invitation
    );
  }

  /**
   * Invite to join group declined handler.
   */
  public function groupMembershipInviteDecline(GroupRelationshipInterface $invitation): void {
    $this->dispatch(
      "com.getopensocial.cms.group_membership.invite.decline",
      $invitation
    );
  }

  /**
   * Generates a deterministic UUIDv5 CloudEvent ID based on type and entity.
   *
   * @param string $event_type
   *   The event type (e.g., "com.getopensocial.cms.group_membership.create").
   * @param \Drupal\group\Entity\GroupMembershipInterface|\Drupal\group\Entity\GroupRelationshipInterface $entity
   *   The entity object (membership, request, or invitation).
   *
   * @return string
   *   A UUIDv5 string.
   */
  private function generateEventId(string $event_type, GroupMembershipInterface|GroupRelationshipInterface $entity): string {
    $project_id = Settings::get('project_id');
    if (empty($project_id)) {
      throw new \RuntimeException('The project_id must be configured to ensure deterministic UUIDs.');
    }
    $uuid = $entity->uuid();

    // All group membership events use the format: event_type:project_id:uuid.
    // The uuid is the same for membership_id, request_id, and invite_id
    // as they all refer to the same entity (membership or relationship).
    $name = "$event_type:$project_id:$uuid";

    return Uuid::uuid5(UuidNamespace::NAMESPACE_OPENSOCIAL, $name)->toString();
  }

  /**
   * Transforms a group membership or request/invitation into a CloudEvent.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function fromEntity(GroupMembershipInterface|GroupRelationshipInterface $entity, string $event_type): CloudEvent {
    // Define all status mappings.
    $status_mappings = [
      // Direct membership statuses.
      "com.getopensocial.cms.group_membership.create" => 'active',
      "com.getopensocial.cms.group_membership.delete" => 'removed',
      "com.getopensocial.cms.group_membership.request.create" => 'request_pending',
      "com.getopensocial.cms.group_membership.request.delete" => 'request_cancelled',
      "com.getopensocial.cms.group_membership.request.accept" => 'active',
      "com.getopensocial.cms.group_membership.request.decline" => 'request_declined',
      "com.getopensocial.cms.group_membership.invite.create" => 'invite_pending',
      "com.getopensocial.cms.group_membership.invite.delete" => 'invite_cancelled',
      "com.getopensocial.cms.group_membership.invite.accept" => 'active',
      "com.getopensocial.cms.group_membership.invite.decline" => 'invite_declined',
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

      // Email is expected to always be present.
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

        // We'll default to a placeholder user data if no email is provided.
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
      id: $this->generateEventId($event_type, $entity),
      source: $this->source(),
      type: $event_type,
      data: [
        'groupMembership' => new GroupMembershipEntityData(
          id: $entity->uuid() ?? '',
          created: DateTime::fromTimestamp($entity->getCreatedTime())->toString(),
          updated: DateTime::fromTimestamp($entity->getChangedTime())->toString(),
          status: $status_mappings[$event_type] ?? 'unknown',
          roles: $roles,
          group: Entity::fromEntity($group),
          user: $user_data,
        ),
        'actor' => Actor::fromContext($this->currentUser, $this->routeName()),
      ],
      dataContentType: 'application/json',
      dataSchema: NULL,
      subject: NULL,
      time: DateTime::fromTimestamp($this->requestTime())->toImmutableDateTime(),
    );
  }

  /**
   * Dispatches the event for any group membership entity.
   *
   * @param string $event_type
   *   The event type.
   * @param \Drupal\group\Entity\GroupMembershipInterface|\Drupal\group\Entity\GroupRelationshipInterface $entity
   *   The entity object (membership, request, or invitation).
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function dispatch(string $event_type, GroupMembershipInterface|GroupRelationshipInterface $entity): void {
    // Skip if required modules are not enabled.
    if (!$this->moduleHandler->moduleExists('social_eda') || !$this->dispatcher) {
      return;
    }

    $topic_name = $this->topicName();

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
