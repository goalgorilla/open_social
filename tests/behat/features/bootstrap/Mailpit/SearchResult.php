<?php

namespace Drupal\social\Behat\Mailpit;

/**
 * Represents a search result in the Mailpit API.
 */
readonly class SearchResult {

  use AssertDataStructureTrait;

  /**
   * The search result.
   *
   * @param int $total
   *   Total number of messages in mailbox.
   * @param int $unread
   *   Total number of unread messages in mailbox.
   * @param int $messagesCount
   *   Total number of messages matching current query.
   * @param int $messagesUnread
   *   Total number of unread messages matching current query.
   * @param int $start
   *   Pagination offset.
   * @param array $tags
   *   All current tags.
   * @param \Drupal\social\Behat\Mailpit\MessageSummary[] $messages
   *   The messages matching the current query.
   */
  public function __construct(
    public int $total,
    public int $unread,
    public int $messagesCount,
    public int $messagesUnread,
    public int $start,
    public array $tags,
    public array $messages,
  ) {
  }

  /**
   * Create the data object from an array of values.
   *
   * @param array $data
   *   The array of values as provided by the API.
   *
   * @return self
   *   The data object.
   */
  public static function fromArray(array $data) : self {
    static::assertHasFields(
      $data,
      [
        "total",
        "unread",
        "messages_count",
        "messages_unread",
        "start",
        "tags",
        "messages",
      ],
      "Unexpected JSON object from Mailpit, expected search result in format from `#get-/api/v1/search` documentation."
    );

    return new self(
      total: $data['total'],
      unread: $data['unread'],
      messagesCount: $data['messages_count'],
      messagesUnread: $data['messages_unread'],
      start: $data['start'],
      tags: $data['tags'],
      messages: array_map([MessageSummary::class, "fromArray"], $data['messages']),
    );
  }

}
