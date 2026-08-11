<?php

namespace Drupal\social\Behat\Loki;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\RequestOptions;

/**
 * A client for Loki's HTTP query API.
 */
final class Client {

  /**
   * The guzzle client used for API requests.
   */
  protected GuzzleClient $client;

  public function __construct(
    protected string $baseUrl = "http://observability:3100",
  ) {
    $this->client = new GuzzleClient([
      "base_uri" => "$this->baseUrl/loki/api/v1/",
      // Guzzle defaults both to 0 (wait indefinitely). Without these, an
      // unreachable Loki hangs the entire Behat run instead of failing fast.
      // The connect timeout covers DNS resolution too, which Docker's embedded
      // DNS has been seen to take more than 2 seconds for on a busy CI node.
      RequestOptions::TIMEOUT => 5,
      RequestOptions::CONNECT_TIMEOUT => 10,
    ]);
  }

  /**
   * Run a LogQL range query and return the matching log entries.
   *
   * @param string $query
   *   A LogQL query, e.g. '{service_name="cms"} | severity_text=~"WARN.*"'.
   * @param \DateTimeImmutable $since
   *   Only include entries logged at or after this time.
   * @param int $limit
   *   The maximum number of entries to return.
   *
   * @return \Drupal\social\Behat\Loki\LogEntry[]
   *   The matching entries, oldest first.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   In case of an error while making the request to Loki.
   *
   * @throws \JsonException
   *   In case Loki returned invalid JSON.
   */
  public function query(string $query, \DateTimeImmutable $since, int $limit = 5000) : array {
    $response = $this->get('query_range', [
      'query' => $query,
      'start' => self::toNanoseconds($since),
      'end' => self::toNanoseconds(new \DateTimeImmutable('now', new \DateTimeZone('UTC'))),
      'limit' => (string) $limit,
      'direction' => 'forward',
    ]);

    $entries = [];
    foreach ($response['data']['result'] ?? [] as $stream) {
      foreach ($stream['values'] ?? [] as [$timestampNs, $line]) {
        $entries[] = LogEntry::fromStreamResult($stream['stream'], $timestampNs, $line);
      }
    }

    // Streams are returned in an arbitrary order; entries within a stream are
    // already ordered by direction=forward, so a stable sort on timestamp
    // across streams gives a consistent overall order.
    usort($entries, fn (LogEntry $a, LogEntry $b) => $a->timestamp <=> $b->timestamp);

    return $entries;
  }

  /**
   * Format a DateTimeImmutable as the nanosecond-precision epoch Loki wants.
   *
   * PHP's DateTime only has microsecond precision, so the result always ends
   * in 3 zeroes.
   */
  private static function toNanoseconds(\DateTimeImmutable $time) : string {
    return $time->format('U') . $time->format('u') . '000';
  }

  /**
   * Make a GET request to the Loki API.
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
   *   In case Loki returned invalid JSON.
   */
  protected function get(string $path, array $query = []) : array {
    $options = [RequestOptions::QUERY => $query];

    try {
      $response = $this->client->get($path, $options);
    }
    catch (ConnectException) {
      // Loki, or the DNS lookup for it, can be briefly unresponsive on a busy
      // CI node. Retry once so a transient connection failure does not fail an
      // otherwise healthy scenario; a second failure propagates as normal.
      usleep(500_000);
      $response = $this->client->get($path, $options);
    }

    return json_decode($response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
  }

}
