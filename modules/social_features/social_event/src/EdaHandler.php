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
   * The Kafka topic name.
   */
  const TOPIC_NAME = 'com.getopensocial.cms.event.create';

  /**
   * The Event type.
   */
  const EVENT_TYPE = 'com.getopensocial.cms.event.create';

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

    // Get current request.
    $request = $this->requestStack->getCurrentRequest();

    // List enrollment methods.
    $enrollment_methods = ['open', 'request', 'invite'];

    // Dispatch the event to the message broker.
    $this->dispatcher->dispatch(
      topic: self::TOPIC_NAME,
      event: new CloudEvent(
        id: $this->uuid->generate(),
        source: $request ? $request->getUri() : '',
        type: self::EVENT_TYPE,
        data: [
          'event' => new EventCreateEventData(
            id: $node->get('uuid')->value,
            created: DateTime::fromTimestamp($node->getCreatedTime())->toString(),
            updated: DateTime::fromTimestamp($node->getChangedTime())->toString(),
            status: $node->get('status')->value,
            label: (string) $node->label(),
            visibility: $node->get('field_content_visibility')->value,
            group: !$node->get('groups')->isEmpty() ? Entity::fromEntity($node->get('groups')->entity) : NULL,
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
            type: $node->hasField('field_event_type') && !$node->get('field_event_type')->isEmpty() ? $node->get('field_event_type')->entity->label() : NULL,
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
      )
    );
  }

}
