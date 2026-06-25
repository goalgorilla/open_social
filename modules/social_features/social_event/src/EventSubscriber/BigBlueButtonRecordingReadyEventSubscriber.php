<?php

declare(strict_types=1);

namespace Drupal\social_event\EventSubscriber;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\meeting_api\Entity\Meeting;
use Drupal\meeting_api\MeetingEntityInterface;
use Drupal\meeting_api_bbb_recordings\Event\RecordingReadyEvent;
use Drupal\social_event\Entity\Node\EventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Persists the recording ID on the meeting entity when a recording is ready.
 */
final class BigBlueButtonRecordingReadyEventSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected readonly EntityRepositoryInterface $entityRepository,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      RecordingReadyEvent::class => ['onRecordingReady'],
    ];
  }

  /**
   * Saves the recording ID on the meeting entity.
   *
   * @param \Drupal\meeting_api_bbb_recordings\Event\RecordingReadyEvent $event
   *   The recording ready event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onRecordingReady(RecordingReadyEvent $event): void {
    $meeting = $this->entityRepository->loadEntityByUuid('meeting_api_meeting', $event->meetingId);
    if (!$meeting instanceof Meeting || $meeting->bundle() !== 'big_blue_button') {
      return;
    }

    // Persist the recording ID only when missing, but always attempt media
    // attachment so partial-failure retries can complete the flow.
    if ($meeting->get('recording_id')->isEmpty()) {
      $meeting
        ->set('recording_id', $event->recordId)
        ->save();
    }

    $this->attachEventRecordingMedia($meeting);
  }

  /**
   * Creates a video recording media and attaches it to the event.
   *
   * @param \Drupal\meeting_api\MeetingEntityInterface $meeting
   *   The meeting entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function attachEventRecordingMedia(MeetingEntityInterface $meeting): void {
    assert($meeting instanceof Meeting);
    $node = $this->findEventByMeeting($meeting);
    if ($node === NULL) {
      return;
    }

    if ($node->hasEventMeetingRecording()) {
      return;
    }

    $media = $this->entityTypeManager->getStorage('media')->create([
      'bundle' => 'video_recording_link',
      'uid' => $node->getOwnerId(),
      'name' => 'Video recording',
      'field_media_links' => [
        'uri' => 'internal:/' . Url::fromRoute('social_event.view_recording', ['node' => $node->id()])
          ->getInternalPath(),
        'title' => 'view',
      ],
    ]);
    $media->save();

    $node->set('field_event_recording', $media);
    $node->save();
  }

  /**
   * Finds the event node that references the given meeting.
   *
   * @param \Drupal\meeting_api\MeetingEntityInterface $meeting
   *   The meeting entity.
   *
   * @return \Drupal\social_event\Entity\Node\EventInterface|null
   *   The event node, or NULL if not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function findEventByMeeting(MeetingEntityInterface $meeting): ?EventInterface {
    $node_ids = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'event')
      ->condition('field_event_meeting', $meeting->id())
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();

    $nid = reset($node_ids);

    if (empty($nid)) {
      return NULL;
    }

    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    return $node instanceof EventInterface ? $node : NULL;
  }

}
