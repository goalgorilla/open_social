<?php

declare(strict_types=1);

namespace Drupal\social_event;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\social_event\Entity\Node\EventInterface;

/**
 * Builds the registration button for events that use external registration.
 */
class ExternalRegistrationLinkBuilder {

  use StringTranslationTrait;

  private const BUTTON_CLASSES = [
    'btn',
    'btn-accent',
    'btn-lg',
    'btn-raised',
    'brand-bg-accent',
    'waves-effect',
  ];

  /**
   * Builds the registration button for an event.
   *
   * The button links to the external destination and does not create an Open
   * Social enrolment.
   *
   * @param \Drupal\social_event\Entity\Node\EventInterface $event
   *   The event.
   *
   * @return array
   *   The registration link, a disabled button when the event has passed, or
   *   an empty render array when the event has no usable registration URL.
   */
  public function build(EventInterface $event): array {
    $build = [];
    CacheableMetadata::createFromObject($event)->applyTo($build);

    $url = $event->getExternalRegistrationUrl();
    if (!$event->isExternalRegistration() || $url === NULL || !ExternalRegistrationUrl::isValid($url)) {
      return $build;
    }

    $build += [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['external-registration'],
      ],
    ];

    if ($event->isEnded()) {
      $build['register'] = [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $this->t('Event has passed'),
        '#attributes' => [
          'type' => 'button',
          'class' => self::BUTTON_CLASSES,
          'disabled' => 'disabled',
        ],
      ];
      return $build;
    }

    $build['register'] = [
      '#type' => 'link',
      '#title' => $this->t('Register for event'),
      '#url' => Url::fromUri($url),
      '#attributes' => [
        'class' => self::BUTTON_CLASSES,
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
      ],
    ];
    return $build;
  }

}
