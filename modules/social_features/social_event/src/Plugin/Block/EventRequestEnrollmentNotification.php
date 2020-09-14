<?php

namespace Drupal\social_event\Plugin\Block;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Event requests notification' block.
 *
 * @Block(
 *   id = "event_requests_notification",
 *   admin_label = @Translation("Event requests notification"),
 * )
 */
class EventRequestEnrollmentNotification extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Event entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $event;

  /**
   * Entity type manger.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translation;

  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs SocialGroupRequestMembershipNotification.
   *
   * @param array $configuration
   *   Configuration array.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationManager $translation
   *   The translation manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Current route match.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    TranslationManager $translation,
    RouteMatchInterface $route_match,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->event = social_event_get_current_event();
    $this->entityTypeManager = $entity_type_manager;
    $this->translation = $translation;
    $this->routeMatch = $route_match;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('current_route_match'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() :array {
    // No event? Don't bother anymore.
    if (!$this->event instanceof NodeInterface) {
      return [];
    }

    // Don't continue if we don't have the correct enroll method for this event.
    if ((int) $this->event->getFieldValue('field_enroll_method', 'value') !== EventEnrollmentInterface::ENROLL_METHOD_REQUEST) {
      return [];
    }

    // At this point we try to get the amount of pending requests.
    try {
      $requests = $this->entityTypeManager->getStorage('event_enrollment')->getQuery()
        ->condition('field_event.target_id', $this->event->id())
        ->condition('field_request_or_invite_status.value', EventEnrollmentInterface::REQUEST_PENDING)
        ->condition('field_enrollment_status.value', '0')
        ->count()
        ->execute();

      if (!$requests) {
        return [];
      }

      return [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('There @link to enroll in this event.', [
          '@link' => Link::fromTextAndUrl(
            $this->translation->formatPlural($requests, 'is (1) new request', 'are (@count) new requests'),
            Url::fromRoute('view.event_manage_enrollment_requests.page_manage_enrollment_requests', ['node' => $this->event->id()])
          )->toString(),
        ]),
        '#attributes' => [
          'class' => [
            'alert',
            'alert-warning',
          ],
        ],
      ];
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('social_event')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('social_event')->error($e->getMessage());
    }

    // Catch all.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $is_event_page = isset($this->event);

    // Show this block only on these specific routes.
    // We can't use the Block UI as you can't specify just the node canonical
    // route.
    $routes = [
      'entity.node.canonical',
      'view.event_enrollments.view_enrollments',
      'view.event_manage_enrollments.page_manage_enrollments',
      'view.event_manage_enrollment_invites.page_manage_enrollment_invites',
      'view.manage_enrollments.page',
      'view.managers.view_managers',
      'social_event_managers.add_enrollees',
      'social_event_managers.vbo.execute_configurable',
      'social_event_managers.vbo.confirm',
    ];

    // We have an event and it's part of the above list of routes.
    if ($this->event instanceof NodeInterface && in_array($this->routeMatch->getRouteName(), $routes, TRUE)) {
      return AccessResult::allowedIf($is_event_page && social_event_manager_or_organizer($this->event));
    }

    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    // Ensure the context keeps track of the URL so we don't see the message on
    // every event.
    $contexts = Cache::mergeContexts($contexts, [
      'url',
      'user.permissions',
    ]);
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), [
      'event_enrollment_list:' . $this->event->id(),
    ]);
  }

}
