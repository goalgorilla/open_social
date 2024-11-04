<?php

namespace Drupal\social_eda\Types;

/**
 * Type class for Application data.
 */
class Application {

  /**
   * Constructs the Application type.
   *
   * @param string $id
   *   The UUID.
   * @param string $name
   *   The application name.
   */
  public function __construct(
    public readonly string $id,
    public readonly string $name,
  ) {}

  /**
   * Get Application from an ID.
   *
   * @param string $id
   *   The application ID.
   *
   * @return self
   *   The Application data object.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the application ID is not recognized.
   */
  public static function fromId(string $id): self {
    // Define the known applications with their UUIDs.
    $applications = [
      'cron' => [
        'uuid' => '123e4567-e89b-12d3-a456-426614174000',
        'name' => 'Cron',
      ],
      'graphql' => [
        'uuid' => '123e4567-e89b-12d3-a456-426614174001',
        'name' => 'GraphQL',
      ],
    ];

    // Check if the given ID exists in the known applications.
    if (!array_key_exists($id, $applications)) {
      throw new \InvalidArgumentException(sprintf('Unknown application ID: %s', $id));
    }

    // Return the corresponding application data.
    return new self(
      id: $applications[$id]['uuid'],
      name: $applications[$id]['name'],
    );
  }

}
