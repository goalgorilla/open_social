<?php

declare(strict_types=1);

namespace Drupal\social_event\Wrappers\Input;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_graphql\Wrappers\InputBase;
use Drupal\taxonomy\TermInterface;

/**
 * Base class for event input wrappers.
 *
 * Provides shared validation logic and common fields for creating and updating
 * events.
 */
abstract class EventInputBase extends InputBase {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity repository.
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * The title of the event.
   */
  protected ?string $title = NULL;

  /**
   * The event type.
   */
  protected ?TermInterface $eventType = NULL;

  /**
   * The visibility setting.
   */
  protected ?string $visibility = NULL;

  /**
   * The start date as a unix timestamp.
   */
  protected ?int $startDate = NULL;

  /**
   * The end date as a unix timestamp.
   */
  protected ?int $endDate = NULL;

  /**
   * The location of the event.
   */
  protected ?string $location = NULL;

  /**
   * Load event type taxonomy terms by their UUIDs.
   *
   * Loading by vocabulary guarantees terms are from the event_types bundle,
   * so callers need not check bundle() or instanceof.
   *
   * @param array $uuids
   *   The UUIDs of the terms to load.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An array of terms indexed by their UUIDs. Returns an empty array
   *   if no matching entities are found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @todo Remove when https://www.drupal.org/project/drupal/issues/3214923 lands.
   */
  protected function loadEventTypesByUuids(array $uuids): array {
    if (empty($uuids)) {
      return [];
    }
    /** @var \Drupal\taxonomy\TermInterface[] $terms_by_id */
    $terms_by_id = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'uuid' => $uuids,
        'vid' => 'event_types',
      ]);
    $terms = [];
    foreach ($terms_by_id as $term) {
      $terms[$term->uuid()] = $term;
    }
    return $terms;
  }

  /**
   * Get the title.
   *
   * @return string
   *   The title.
   */
  public function getTitle(): string {
    assert($this->title !== NULL, __FUNCTION__ . " called but title was not set.");
    return $this->title;
  }

  /**
   * Get the event type.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The event type.
   */
  public function getEventType(): TermInterface {
    assert($this->eventType !== NULL, __FUNCTION__ . " called but event type was not set.");
    return $this->eventType;
  }

  /**
   * Get the visibility.
   *
   * @return string
   *   The visibility setting.
   */
  public function getVisibility(): string {
    assert($this->visibility !== NULL, __FUNCTION__ . " called but visibility was not set.");
    return $this->visibility;
  }

  /**
   * Get the start date as a unix timestamp.
   *
   * @return int
   *   The start date timestamp.
   */
  public function getStartDate(): int {
    assert($this->startDate !== NULL, __FUNCTION__ . " called but start date was not set.");
    return $this->startDate;
  }

  /**
   * Get the end date as a unix timestamp.
   *
   * @return int
   *   The end date timestamp.
   */
  public function getEndDate(): int {
    assert($this->endDate !== NULL, __FUNCTION__ . " called but end date was not set.");
    return $this->endDate;
  }

  /**
   * Get the location.
   *
   * @return string|null
   *   The location or NULL if not provided.
   */
  public function getLocation(): ?string {
    return $this->location;
  }

}
