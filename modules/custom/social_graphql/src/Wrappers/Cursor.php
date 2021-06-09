<?php

namespace Drupal\social_graphql\Wrappers;

/**
 * GraphQL cursor for pagination.
 *
 * The cursor is used to find the current position in the connection result
 * set.
 *
 * A critical feature of the cursor is that you can continue to paginate even
 * if the node that you grabbed the cursor from ceases to exist (or is
 * modified), so effectively it details the "value" it's sorted by.
 */
class Cursor {

  /**
   * The internal type of the object that provided the cursor.
   *
   * @var string
   */
  protected string $backingType;

  /**
   * The internal identifier for the object that provided the cursor.
   *
   * @var int
   */
  protected int $backingId;

  /**
   * The sort key that this cursor was created for.
   *
   * How this maps to object values is determined by the connection responsible
   * for the edge.
   *
   * @var string
   */
  protected string $sortKey;

  /**
   * The sort value that this cursor was created with.
   *
   * @var mixed
   */
  protected $sortValue;

  /**
   * Create a new GraphQL cursor.
   *
   * A cursor should provide a stable point in pagination results, even if the
   * type that backs it is removed or altered.
   *
   * @param string $backing_type
   *   The internal type of the object the cursor is for.
   * @param int $backing_id
   *   The internal ID of the object the cursor is for. Must be unique for the
   *   backing_type as it may be used for stable sorting when there are
   *   duplicates in the value for the sort field.
   * @param string $sort_key
   *   The key to sort by. How this maps to object values is determined by the
   *   connection responsible for the edge.
   * @param mixed $sort_value
   *   The value to sort by.
   */
  public function __construct(string $backing_type, int $backing_id, string $sort_key, $sort_value) {
    $this->backingType = $backing_type;
    $this->backingId = $backing_id;
    $this->sortKey = $sort_key;
    $this->sortValue = $sort_value;
  }

  /**
   * Hydrate a cursor into a queryable object.
   *
   * Classes overwriting this method should also overwrite toCursorString
   * since the transformation between cursor class and string is considered to
   * be an implementation detail.
   *
   * @param string $cursor
   *   The cursor string as returned by self::toCursorString().
   *
   * @return static|null
   *   An instance of the cursor class or null in case of an invalid cursor.
   */
  public static function fromCursorString(string $cursor) : ?self {
    $serialized_object = base64_decode($cursor, TRUE);
    if ($serialized_object === FALSE) {
      return NULL;
    }

    $deserialized_object = unserialize($serialized_object, ['allowed_classes' => [static::class]]);
    return $deserialized_object instanceof static ? $deserialized_object : NULL;
  }

  /**
   * Convert the Cursor to a string.
   *
   * Classes overwriting this method should also overwrite fromCurosrString
   * since the transformation between cursor class and string is considered to
   * be an implementation detail.
   *
   * @return string
   *   The string that can be returned in GraphQL responses.
   */
  public function toCursorString() : string {
    return base64_encode(serialize($this));
  }

  /**
   * Whether the cursor is valid for the specified key and type.
   *
   * @param string $sort_key
   *   The sort key that this cursor should be for.
   * @param string|null $backing_type
   *   If provided will require the backing type to match as well.
   *
   * @return bool
   *   Whether this cursor is valid for the provided arguments.
   */
  public function isValidFor(string $sort_key, string $backing_type = NULL) : bool {
    return $this->sortKey === $sort_key && (is_null($backing_type) || $this->backingType === $backing_type);
  }

  /**
   * Magic method to stringify this object.
   *
   * @return string
   *   The string that can be returned in GraphQL responses.
   *
   * @see toCursorString()
   */
  public function __toString() : string {
    return $this->toCursorString();
  }

  /**
   * Get the backing ID for this cursor.
   *
   * @return int
   *   The internal identifier for the object that created this cursor.
   */
  public function getBackingId() : int {
    return $this->backingId;
  }

  /**
   * Get the sort value for this cursor.
   *
   * @return mixed
   *   The sort value for this cursor.
   */
  public function getSortValue() {
    return $this->sortValue;
  }

}
