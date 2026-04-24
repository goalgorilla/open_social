<?php

declare(strict_types=1);

namespace Drupal\social_graphql\EventSubscriber;

use Drupal\Core\Database\Database;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\graphql\Event\OperationEvent;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\simple_oauth\Authentication\TokenAuthUserInterface;
use GraphQL\Executor\ExecutionResult;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Logs GraphQL request metadata for the /graphql endpoint.
 *
 * @phpstan-type QueryContext array{
 *    type: string,
 *    server_entity_id: string,
 *    server_entity_uuid: string,
 *    operation: string,
 *    query: string|null,
 *    query_id: string|null,
 *    readonly: bool,
 *  }
 * @phpstan-type AuthInfo array{
 *    type: string,
 *    mode: string|null,
 *    user_id: int,
 *    consumer_id: string|null,
 *  }
 * @phpstan-type PerformanceInfo array{
 *    duration_ms: float|int|null,
 *    query_count: int,
 *  }
 */
final class GraphqlAuditSubscriber implements EventSubscriberInterface {

  private const string DBLOG_KEY = 'social_graphql_audit';

  /**
   * Keeps track of queries for timing purposes.
   */
  private array $queryTracker = [];

  /**
   * Create a new audit subscriber instance.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The module logger to use in case of errors.
   * @param \Psr\Log\LoggerInterface $queryLogger
   *   The logger to push query information to.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The user currently executing a task.
   */
  public function __construct(
    private readonly LoggerInterface $logger,
    private readonly LoggerInterface $queryLogger,
    private readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Request: capture payload + start timers/logging.
    // Terminate: compute duration + query count and log.
    return [
      OperationEvent::GRAPHQL_OPERATION_BEFORE => ['beforeExecution', 0],
      OperationEvent::GRAPHQL_OPERATION_AFTER => ['afterExecution', 0],
      OperationEvent::GRAPHQL_OPERATION_CACHE_HIT => ['cacheHit', 0],
    ];
  }

  /**
   * Handle the start of a GraphQL Operation Execution.
   *
   * Records the start time and turns on database query logging.
   *
   * @param \Drupal\graphql\Event\OperationEvent $event
   *   The operation that will be executed in GraphQL.
   */
  public function beforeExecution(OperationEvent $event): void {
    // This assumes the context is the same object throughout the operation.
    $this->queryTracker[spl_object_id($event->getContext())] = hrtime(TRUE);
    Database::startLog(self::DBLOG_KEY);
  }

  /**
   * Handle the completion of a GraphQL Operation Execution.
   *
   * Logs information about the GraphQL query and basic performance information.
   *
   * @param \Drupal\graphql\Event\OperationEvent $event
   *   The operation that was executed.
   */
  public function afterExecution(OperationEvent $event): void {
    // This assumes the context is the same object throughout the operation.
    $context_id = spl_object_id($event->getContext());
    $start = $this->queryTracker[$context_id] ?? NULL;
    unset($this->queryTracker[$context_id]);
    // In case that invariant is no longer true we log a message, but we still
    // want to log the request itself so that we have auditability.
    if ($start === NULL) {
      $this->logger->notice("The Context provided by the GraphQL module was changed between the before/after operation event. This breaks calculation of request duration.");
    }
    $durationMs = $start !== NULL ? (hrtime(TRUE) - (int) $start) / 1_000_000 : NULL;
    $dbQueries = Database::getLog(self::DBLOG_KEY);
    $dbQueryCount = count($dbQueries);

    $this->logRequest(
      query: $this->queryContext($event->getContext()),
      auth: $this->authInfo(),
      success: $this->isSuccess($event->getResult()),
      from_cache: FALSE,
      performance: [
        'duration_ms' => $durationMs,
        'query_count' => $dbQueryCount,
      ],
    );
  }

  /**
   * Handle the cache serving of a GraphQL Operation Execution.
   *
   * Logs information about the GraphQL query.
   *
   * @param \Drupal\graphql\Event\OperationEvent $event
   *   The operation that was served from cache.
   */
  public function cacheHit(OperationEvent $event): void {
    $this->logRequest(
      query: $this->queryContext($event->getContext()),
      auth: $this->authInfo(),
      success: $this->isSuccess($event->getResult()),
      from_cache: TRUE,
      performance: NULL,
    );
  }

  /**
   * Log the request.
   *
   * @param QueryContext $query
   *   The context about the query.
   * @param AuthInfo $auth
   *   The authentication information.
   * @param bool $success
   *   Whether the query was a success.
   * @param bool $from_cache
   *   Whether the result was served from cache.
   * @param PerformanceInfo|null $performance
   *   Performance information if the result was not served from cache.
   */
  private function logRequest(array $query, array $auth, bool $success, bool $from_cache, ?array $performance) : void {
    $this->queryLogger->info('GraphQL request served', [
      'query' => $query,
      'auth' => $auth,
      'success' => $success,
      'from_cache' => $from_cache,
      'performance' => $performance,
    ]);
  }

  /**
   * Get query information from the resolve context.
   *
   * @param \Drupal\graphql\GraphQL\Execution\ResolveContext $context
   *   The resolve context.
   *
   * @return QueryContext
   *   The information about the query.
   */
  private function queryContext(ResolveContext $context) : array {
    $type = $context->getType();
    $serverId = (string) $context->getServer()->id();
    $serverUuid = $context->getServer()->uuid() ?? "UNSAVED-TEST-SERVER";
    $operation = $context->getOperation()->operation;
    $query = $context->getOperation()->query;
    $queryId = $context->getOperation()->queryId;
    $readonly = $context->getOperation()->readOnly;

    return [
      'type' => $type,
      'server_entity_id' => $serverId,
      'server_entity_uuid' => $serverUuid,
      'operation' => $operation,
      'query' => $query,
      'query_id' => $queryId,
      'readonly' => $readonly,
    ];
  }

  /**
   * Whether the execution of the query was a success.
   *
   * @param \GraphQL\Executor\ExecutionResult|null $result
   *   The result from the OperationEvent.
   *
   * @return bool
   *   Whether the execution of the query was a success.
   */
  private function isSuccess(?ExecutionResult $result) : bool {
    if ($result === NULL) {
      return FALSE;
    }

    return count($result->errors) === 0;
  }

  /**
   * Get the auth info from the current user service.
   *
   * @return AuthInfo
   *   The information about the authentication.
   */
  private function authInfo() : array {
    $user_id = $this->currentUser->id();
    $user = $this->currentUser->getAccount();

    // User is using OAuth.
    if ($user instanceof TokenAuthUserInterface) {
      $type = "oauth";
      $token = $user->getToken();
      $mode = $token->get('auth_user_id')->isEmpty() ? 'client_credentials' : 'authorization_code';
      $consumer_id = $token->get('client')->target_id;
    }
    // User has a cookie session directly with Drupal (through the login form
    // or SSO).
    else {
      $type = "cookie";
      $mode = NULL;
      $consumer_id = NULL;
    }

    return [
      // The request authentication type.
      'type' => $type,
      // The mode for the authentication type.
      'mode' => $mode,
      // The user ID by which actions on the platform will be visible.
      // For OAuth can be the user assigned to the client_credentials
      // application or the user that authorized the authorization_code
      // application. In case of cookie is the user that authenticated using
      // the login process.
      'user_id' => $user_id,
      // The entity ID of the consumer that made the request (or NULL).
      'consumer_id' => $consumer_id,
    ];
  }

}
