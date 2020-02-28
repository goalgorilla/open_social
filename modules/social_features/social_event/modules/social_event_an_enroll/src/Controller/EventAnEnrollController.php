<?php

namespace Drupal\social_event_an_enroll\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\social_event_managers\SocialEventManagersAccessHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class EventAnEnrollController.
 *
 * @package Drupal\social_event_an_enroll\Controller
 */
class EventAnEnrollController extends ControllerBase {

  /**
   * The route match.
   *
   * @var RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * SocialTopicController constructor.
   *
   * @param RouteMatchInterface $routeMatch
   *   The route match object.
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param ConfigFactoryInterface $configFactory
   */
  public function __construct(RouteMatchInterface $routeMatch,
                              EntityTypeManagerInterface $entityTypeManager,
                              ConfigFactoryInterface $configFactory) {
    $this->routeMatch = $routeMatch;
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Determines if user has access to enroll form.
   */
  public function enrollAccess(NodeInterface $node) {
    $config = $this->config('social_event_an_enroll.settings');
    $is_global_enabled = $config->get('event_an_enroll');
    $is_event = $node->getType() === 'event';
    $is_public = $node->get('field_content_visibility')->getString() === 'public';
    $is_event_an_enroll = !empty($node->get('field_event_an_enroll')->value);
    if ($is_global_enabled && $is_event && $is_public && $is_event_an_enroll) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * Enroll dialog callback.
   */
  public function enrollDialog(NodeInterface $node) {

    // Fetch the user settings.
    $userSettings = $this->configFactory->get('user.settings');

    $action_links['login'] = [
      'uri' => Url::fromRoute('user.login', [], [
        'query' => [
          'destination' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()])
            ->toString(),
        ],
      ])->toString(),
    ];

    // Check if users are allowed to register.
    if ('admin_only' !== $userSettings->get('register')) {
      $action_links['register'] = [
        'uri' => Url::fromRoute('user.register', [], [
          'query' => [
            'destination' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()])
              ->toString(),
          ],
        ])->toString(),
      ];
    }

    $action_links['guest'] = [
      'uri' => Url::fromRoute('social_event_an_enroll.enroll_form', ['node' => $node->id()], [])
        ->toString(),
    ];

    return [
      '#theme' => 'event_an_enroll_dialog',
      '#links' => $action_links,
    ];
  }

  /**
   * The _title_callback for the event enroll dialog route.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node.
   *
   * @return string
   *   The page title.
   */
  public function enrollTitle(NodeInterface $node) {
    return $this->t('Enroll in @label Event', ['@label' => $node->label()]);
  }

  /**
   * Checks access for manage enrollment page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Check standard and custom permissions.
   */
  public function enrollManageAccess(AccountInterface $account) {
    if (AccessResult::allowedIfHasPermission($account, 'manage all enrollments')->isAllowed()) {
      return AccessResult::allowed();
    }
    else {
      /** @var \Drupal\node\Entity\Node $node */
      $node = $this->routeMatch->getParameter('node');
      if (!is_null($node) && (!is_object($node))) {
        $node = $this->entityTypeManager
          ->getStorage('node')
          ->load($node);
      }
      if ($node instanceof NodeInterface) {
        return SocialEventManagersAccessHelper::getEntityAccessResult($node, 'update', $account);
      }
    }
    return AccessResult::forbidden();
  }

}
