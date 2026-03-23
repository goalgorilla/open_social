<?php

declare(strict_types=1);

namespace Drupal\social_event\Controller;

use BigBlueButton\Core\Record;
use BigBlueButton\Parameters\GetRecordingsParameters;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Utility\Error;
use Drupal\meeting_api\BackendPluginManager;
use Drupal\meeting_api\Entity\Meeting;
use Drupal\meeting_api\MeetingEntityInterface;
use Drupal\meeting_api\ServerInterface;
use Drupal\meeting_api_bbb\Plugin\MeetingApiBackend\BigBlueButton;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\social_event\Entity\Node\EventInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller that redirects to the video playback URL of an event recording.
 */
final class EventRecordingController implements ContainerInjectionInterface {

  use AutowireTrait;

  /**
   * The logger channel.
   */
  private readonly LoggerChannelInterface $logger;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly BackendPluginManager $backendPluginManager,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('social_event');
  }

  /**
   * Checks access for the view recording route.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node from the route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(NodeInterface $node, AccountInterface $account): AccessResultInterface {
    if (!$node instanceof EventInterface) {
      return AccessResult::forbidden()->addCacheableDependency($node);
    }

    // Preserve cache metadata from the node's view access check so downstream
    // invalidations (permissions, grants, etc.) bubble up correctly.
    $view_access = $node->access('view', $account, TRUE);
    if (!$view_access->isAllowed()) {
      return $view_access;
    }

    if (!$node->hasEventMeetingRecording()) {
      return AccessResult::forbidden()
        ->addCacheableDependency($node)
        ->inheritCacheability($view_access);
    }

    $has_access = $account->hasPermission('administer nodes')
      || $node->isEventManager($account)
      || $node->getParticipation($account);

    return AccessResult::allowedIf($has_access)
      ->addCacheableDependency($node)
      ->addCacheTags(['event_enrollment_list:' . $node->id()])
      ->cachePerUser()
      ->inheritCacheability($view_access);
  }

  /**
   * Redirects to the video playback URL of an event's meeting recording.
   *
   * @param \Drupal\social_event\Entity\Node\EventInterface $node
   *   The event node.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   A redirect response to the video playback URL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \ReflectionException
   */
  public function viewRecording(EventInterface $node): TrustedRedirectResponse {
    $meeting = $node->getMeeting();
    if (!$meeting instanceof MeetingEntityInterface) {
      throw new NotFoundHttpException();
    }

    return match ($meeting->bundle()) {
      'big_blue_button' => $this->getBigBlueButtonRecordingUrl($node, $meeting),
      default => throw new NotFoundHttpException(),
    };
  }

  /**
   * Gets the recording URL from BigBlueButton.
   *
   * @param \Drupal\social_event\Entity\Node\EventInterface $node
   *   The event node.
   * @param \Drupal\meeting_api\MeetingEntityInterface $meeting
   *   The BBB meeting entity.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   A redirect response to the video playback URL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \ReflectionException
   */
  private function getBigBlueButtonRecordingUrl(EventInterface $node, MeetingEntityInterface $meeting): TrustedRedirectResponse {
    assert($meeting instanceof Meeting);

    $record_id = $meeting->get('recording_id')->getString();
    if (empty($record_id)) {
      throw new NotFoundHttpException();
    }

    $server_id = $meeting->getServerId();
    $server = $this->entityTypeManager->getStorage('meeting_api_server')->load($server_id);
    if (!$server instanceof ServerInterface) {
      throw new NotFoundHttpException();
    }

    $backend = $this->backendPluginManager->createInstance($server->get('backend'), $server->get('backend_config'));
    if (!$backend instanceof BigBlueButton) {
      throw new NotFoundHttpException();
    }

    $client = $backend->getClient();

    $params = new GetRecordingsParameters();
    $params->setRecordId($record_id);

    try {
      $response = $client->getRecordings($params);
    }
    catch (\Exception $exception) {
      Error::logException($this->logger, $exception);
      throw new NotFoundHttpException();
    }

    $record = $response->getRecords()[0] ?? NULL;
    if (!$record instanceof Record) {
      throw new NotFoundHttpException();
    }

    foreach ($record->getFormats() as $format) {
      if ($format->getType() !== 'video') {
        continue;
      }

      $url = $format->getUrl();
      if (!UrlHelper::isValid($url, TRUE)) {
        // BBB playback URLs can include signed tokens; log only the host to
        // avoid leaking credentials.
        $this->logger->error('Invalid playback URL returned by BBB for recording %record_id of event %event_id (host: %host)', [
          '%record_id' => $record_id,
          '%event_id' => $node->id(),
          '%host' => (string) parse_url($url, PHP_URL_HOST),
        ]);
        throw new NotFoundHttpException();
      }

      $redirect = new TrustedRedirectResponse($url);
      $redirect->getCacheableMetadata()->setCacheMaxAge(0);

      return $redirect;
    }

    $this->logger->warning('No video format found in BBB recording %record_id for event %event_id', [
      '%record_id' => $record_id,
      '%event_id' => $node->id(),
    ]);

    throw new NotFoundHttpException();
  }

}
