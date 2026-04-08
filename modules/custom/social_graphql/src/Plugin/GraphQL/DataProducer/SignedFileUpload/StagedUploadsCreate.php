<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\SignedFileUpload;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\signed_file_upload\Exception\InvalidContentException;
use Drupal\signed_file_upload\SignedUploadFileLifecycleInterface;
use Drupal\signed_file_upload\UploadSessionManagerInterface;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\social_graphql\Wrappers\Input\StagedUploadsCreateInput;
use Drupal\social_graphql\Wrappers\Payload\StagedUploadsCreatePayload;
use Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a new staged upload request.
 *
 * @DataProducer(
 *   id = "staged_uploads_create",
 *   name = @Translation("Staged Uploads Create"),
 *   description = @Translation("Provides one or more Staged Upload Grants."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("StagedUploadsCreatePayload")
 *   ),
 *   consumes = {
 *     "input" = @ContextDefinition("any",
 *       label = "StagedUploadsCreateInput",
 *     ),
 *   }
 * )
 */
class StagedUploadsCreate extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  use LoggerChannelTrait;

  /**
   * Constructs a StagedUploadsCreate producer.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The user starting upload sessions.
   * @param \Drupal\signed_file_upload\UploadSessionManagerInterface $uploadSessionManager
   *   Creates tus-aligned upload sessions.
   * @param \Drupal\signed_file_upload\SignedUploadFileLifecycleInterface $uploadFileLifecycle
   *   Cleans up partial sessions on failure.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger factory for cleanup errors.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected AccountInterface $currentUser,
    protected UploadSessionManagerInterface $uploadSessionManager,
    protected SignedUploadFileLifecycleInterface $uploadFileLifecycle,
    LoggerChannelFactoryInterface $loggerChannelFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setLoggerFactory($loggerChannelFactory);
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return static
   *   A configured producer instance.
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get(UploadSessionManagerInterface::class),
      $container->get(SignedUploadFileLifecycleInterface::class),
      $container->get('logger.factory'),
    );
  }

  /**
   * Runs stagedUploadsCreate: sessions per request, or errors on the payload.
   *
   * @param \Drupal\social_graphql\Wrappers\Input\StagedUploadsCreateInput $input
   *   Typed input from staged_uploads_create_input (setValues already applied).
   *
   * @return \Drupal\social_graphql\Wrappers\Payload\StagedUploadsCreatePayload
   *   Payload with grants or violation list; grants only if every request
   *   succeeded.
   */
  public function resolve(StagedUploadsCreateInput $input): StagedUploadsCreatePayload {
    $payload = new StagedUploadsCreatePayload();
    $payload->setClientMutationId($input->getClientMutationId());

    // Validate the input.
    if (!$input->validate()) {
      $payload->addViolations($input->getViolations());
      return $payload;
    }

    $sessions = [];
    foreach ($input->getRequests() as $request) {
      try {
        $sessions[] = new StagedUploadGrant(
          target: $request['target'],
          sessionGrant: $this->uploadSessionManager->beginUploadSession(
            destination: $request['destination'],
            account: $this->currentUser,
            metadata: $request['metadata'],
            uploadLength: $request['fileSize'],
          )
        );
      }
      catch (InvalidContentException $exception) {
        // Normally Violations should be static IDs that are useful for clients.
        // However, from the underlying service we don't have such static IDs.
        // This static ID requirement without any other metadata fields is an
        // unsolved issue in error design in our API. Rigidly resolving this
        // exception to a static ID would seriously hurt the debuggability of
        // errors for clients, so we choose the least-bad option and violate our
        // own error contract here in the name of clarity.
        $payload->addViolation(new Violation($exception->getMessage()));
        // We only want sessions to be available if all requests are valid so we
        // immediately nix any sessions created thus far.
        $this->deleteSessions($sessions);
        return $payload;
      }
      catch (\Throwable $exception) {
        // We only want sessions to be available if all requests are valid so we
        // immediately nix any sessions created thus far.
        $this->deleteSessions($sessions);
        throw $exception;
      }
    }

    $payload->setStagedUploadGrants($sessions);
    return $payload;
  }

  /**
   * Error handler to delete all created sessions in case of issues.
   *
   * @param \Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant[] $sessions
   *   Wrapper grants whose underlying upload sessions should be removed.
   */
  protected function deleteSessions(array $sessions): void {
    foreach ($sessions as $session) {
      // We want to attempt to delete all sessions even if we run into a session
      // that fails to delete for some reason.
      try {
        $this->uploadFileLifecycle->deleteUpload($session->sessionGrant->session);
      }
      catch (\Throwable $exception) {
        // Delete session is an error handler, so we log but do not throw.
        $this->getLogger('social_graphql')->error("Exception encountered while cleaning up sessions after failed StagedUploadsCreate mutation.", ['exception' => $exception]);
      }
    }
  }

}
