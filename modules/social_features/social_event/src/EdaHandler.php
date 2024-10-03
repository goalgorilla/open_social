<?php

namespace Drupal\social_event;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\node\NodeInterface;
use Drupal\social_eda\Types\Address;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\Entity;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;
use Drupal\social_eda_dispatcher\Dispatcher as SocialEdaDispatcher;
use Drupal\social_event\Event\EventCreateEventData;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles hook invocations for EDA related operations of the event entity.
 */
final class EdaHandler {

  /**
   * The Kafka create topic name.
   */
  const TOPIC_CREATE_NAME = 'com.getopensocial.cms.event.create';

  /**
   * The Kafka update topic name.
   */
  const TOPIC_UPDATE_NAME = 'com.getopensocial.cms.event.update';

  /**
   * The Event create type.
   */
  const EVENT_CREATE_TYPE = 'com.getopensocial.cms.event.create';

  /**
   * The Event update type.
   */
  const EVENT_UPDATE_TYPE = 'com.getopensocial.cms.event.update';

  /**
   * {@inheritDoc}
   */
  public function __construct(
    private readonly ?SocialEdaDispatcher $dispatcher,
    private readonly UuidInterface $uuid,
    private readonly RequestStack $requestStack,
    private readonly ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Create event handler.
   */
  public function eventCreate(NodeInterface $node): void {
    // Skip if required modules are not enabled.
    if (!$this->moduleHandler->moduleExists('social_eda') || !$this->dispatcher) {
      return;
    }

    // Transform the node into a CloudEvent.
    $event = $this->fromEntity($node, self::EVENT_CREATE_TYPE);

    // Dispatch the event to the message broker.
    $this->dispatcher->dispatch(self::TOPIC_CREATE_NAME, $event);
  }

  /**
   * Create update event handler.
   */
  public function eventUpdate(NodeInterface $node): void {
    // Skip if required modules are not enabled.
    if (!$this->moduleHandler->moduleExists('social_eda') || !$this->dispatcher) {
      return;
    }

    // Transform the node into a CloudEvent.
    $event = $this->fromEntity($node, self::EVENT_UPDATE_TYPE);

    // Dispatch the event to the message broker.
    $this->dispatcher->dispatch(self::TOPIC_UPDATE_NAME, $event);
  }

  /**
   * Transforms a NodeInterface into a CloudEvent.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function fromEntity(NodeInterface $node, string $type): CloudEvent {
    // Get current request.
    $request = $this->requestStack->getCurrentRequest();

    // List enrollment methods.
    $enrollment_methods = ['open', 'request', 'invite'];

    return new CloudEvent(
      id: $this->uuid->generate(),
      source: $request ? $request->getUri() : '/node/add/event',
      type: $type,
      data: [
        'event' => new EventCreateEventData(
          id: $node->get('uuid')->value,
          created: DateTime::fromTimestamp($node->getCreatedTime())->toString(),
          updated: DateTime::fromTimestamp($node->getChangedTime())->toString(),
          status: $node->get('status')->value,
          label: (string) $node->label(),
          visibility: $node->get('field_content_visibility')->value,
          group: !$node->get('groups')->isEmpty() ? Entity::fromEntity($node->get('groups')->getEntity()) : NULL,
          author: User::fromEntity($node->get('uid')->entity),
          allDay: $node->get('field_event_all_day')->value,
          start: $node->get('field_event_date')->value,
          end: $node->get('field_event_date_end')->value,
          timezone: date_default_timezone_get(),
          address: Address::fromFieldItem(
            item: $node->get('field_event_address')->first(),
            label: $node->get('field_event_location')->value
          ),
          enrollment: [
            'enabled' => (bool) $node->get('field_event_enroll')->value,
            'method' => $enrollment_methods[$node->get('field_enroll_method')->value],
          ],
          href: Href::fromEntity($node),
          type: $node->hasField('field_event_type') && !$node->get('field_event_type')->isEmpty() ? $node->get('field_event_type')->getEntity()->label() : NULL,
        ),
        'actor' => [
          'application' => NULL,
          'user' => User::fromEntity($node->get('uid')->entity),
        ],
      ],
      dataContentType: 'application/json',
      dataSchema: NULL,
      subject: NULL,
      time: DateTime::fromTimestamp($node->getCreatedTime())->toImmutableDateTime(),
    );
  }

}
