<?php

namespace Drupal\social\Behat\Mailpit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\RequestOptions;

/**
 * A client for the Mailpit API.
 */
final class Client {

  /**
   * The guzzle client used for API requests.
   */
  protected GuzzleClient $client;

  public function __construct(
    protected string $baseUrl = "http://mail:8025",
  ) {
    $this->client = new GuzzleClient([
      "base_uri" => "$this->baseUrl/api/v1/",
    ]);
  }

  /**
   * @param string|null $keywords
   *   Keywords or strings to search for.
   *   Use double quotes in case you want to match multi-word strings.
   * @param string $start
   *   The start count, used for pagination (default 0).
   * @param string $limit
   *   The limit of results to return (default 50).
   * @param string|null $tz
   *   The timezone to use for before/after queries (default: Etc/UTC).
   * @param string ...$query_parts
   *   Any query modifiers supported by Mailpit (e.g. `before`, `after`,
   *   `subject` or `from`).
   *
   * @return \Drupal\social\Behat\Mailpit\SearchResult
   *   The result of the search.
   */
  public function search(
    array|string|null $keywords = NULL,
    string $start = "0",
    string $limit = "50",
    string $tz = "Etc/UTC",
    string ...$query_parts,
  ) : SearchResult {
    $search_query = [
      "start" => $start,
      "limit" => $limit,
      "tz" => $tz,
    ];

    $query = [];
    if ($keywords !== NULL) {
      if (is_string($keywords)) {
        $keywords = [$keywords];
      }
      // Add the keywords as loose items in the query but quote the keywords
      // that contain strings so they're treated as a single item.
      $query = array_map(
        fn ($v) => str_contains($v, " ") ? "\"$v\"" : $v,
        $keywords
      );
    }

    // Add the special modifiers (e.g. 'before') to the query.
    foreach ($query_parts as $key => $value) {
      // Only escape values that contain spaces since Mailpit treats some things
      // (such as before/after) differently if they are quoted. Quotes are not
      // needed for single word values.
      if (str_contains($value, " ")) {
        $query[] = "$key:\"$value\"";
      }
      else {
        $query[] = "$key:$value";
      }
    }

    if ($query !== []) {
      $search_query["query"] = implode(" ", $query);
    }

    return SearchResult::fromArray($this->get("search", $search_query));
  }

  /**
   * Get the headers for a message.
   *
   * @param \Drupal\social\Behat\Mailpit\Message|\Drupal\social\Behat\Mailpit\MessageSummary|string $message
   *   The message object or message ID.
   *
   * @return array<string, list<string>>
   *   An array of headers for the message.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   In case of an error while making the request to Mailpit.
   *
   * @throws \JsonException
   *   In case Mailpit returned invalid JSON.
   */
  public function getMessageHeaders(Message|MessageSummary|string $message) : array {
    if ($message instanceof Message || $message instanceof MessageSummary) {
      $message = $message->id;
    }

    return $this->get("message/$message/headers");
  }

  /**
   * Get the Mailpit URL to render the message HTML.
   *
   * @param \Drupal\social\Behat\Mailpit\Message|\Drupal\social\Behat\Mailpit\MessageSummary|string $message
   *   The message object or message ID.
   *
   * @return string
   *   The Mailput URL that renders the message HTML.
   */
  public function getMessageHtmlUrl(Message|MessageSummary|string $message) : string {
    if ($message instanceof Message || $message instanceof MessageSummary) {
      $message = $message->id;
    }

    return "$this->baseUrl/view/$message.html";
  }

  /**
   * Render the message HTML.
   *
   * This method will make a request to the Mailpit API to render the message
   * HTML.
   *
   * @param \Drupal\social\Behat\Mailpit\Message|\Drupal\social\Behat\Mailpit\MessageSummary|string $message
   *   The message object or message ID.
   *
   * @return string
   *   The HTML of the rendered message.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function renderMessageHtml(Message|MessageSummary|string $message) : string {
    return $this->client->get($this->getMessageHtmlUrl($message))->getBody()->getContents();
  }

  /**
   * Get the Mailpit URL to render the message text.
   *
   * @param \Drupal\social\Behat\Mailpit\Message|\Drupal\social\Behat\Mailpit\MessageSummary|string $message
   *   The message object or message ID.
   *
   * @return string
   *   The Mailput URL that renders the message text.
   */
  public function getMessageTextUrl(Message|MessageSummary|string $message) : string {
    if ($message instanceof Message || $message instanceof MessageSummary) {
      $message = $message->id;
    }

    return "$this->baseUrl/view/$message.txt";
  }

  /**
   * Render the message text.
   *
   * This method will make a request to the Mailpit API to render the message
   * text.
   *
   * @param \Drupal\social\Behat\Mailpit\Message|\Drupal\social\Behat\Mailpit\MessageSummary|string $message
   *   The message object or message ID.
   *
   * @return string
   *   The text of the rendered message.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function renderMessageText(Message|MessageSummary|string $message) : string {
    return $this->client->get($this->getMessageTextUrl($message))->getBody()->getContents();
  }

  /**
   * Get the date of the last received email message.
   *
   * Calls /api/v1/messages.
   *
   * @return \DateTimeImmutable
   *   The date of the last received email message or 1970-01-01 if no
   *   messages were received.
   *
   * @throws \JsonException
   */
  public function getLastEmailDate() : \DateTimeImmutable {
    $response = $this->get('messages');
    if ($response['count'] === 0) {
      return new \DateTimeImmutable('1970-01-01');
    }

    return MessageSummary::fromArray($response['messages'][0])->created;
  }

  /**
   * Make a get request to the Mailpit API.
   *
   * @param string $path
   *   The API path to query.
   * @param array $query
   *   The URL query parameters to add.
   *
   * @return array
   *   The decoded JSON response.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   In case of an error.
   *
   * @throws \JsonException
   *   In case Mailpit returned invalid JSON.
   */
  protected function get(string $path, array $query = []) : array {
    $response = $this->client->get(
      $path,
      [
        RequestOptions::QUERY => $query,
      ]
    );

    return json_decode($response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
  }

}
