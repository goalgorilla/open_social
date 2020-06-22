<?php

namespace Drupal\social_event_managers\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AutocompleteController.
 *
 * @package Drupal\social_event_managers\Controller
 */
class AutocompleteController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SocialTopicController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Routing\RouteMatch $route_match
   *   Current route.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Check standard and custom permissions.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function access(AccountInterface $account, RouteMatch $route_match) {
    return AccessResult::allowed();
  }

  public function populate ($node) {
    $response = new AjaxResponse();

    // Create the dataset.
    $data = [];
    $data[] = [
      'id' => '3',
      'full_name' => 'Jim (3)',
    ];

    $data[] = [
      'id' => '1',
      'full_name' =>  'testing (1)',
    ];


    $response->setData($data);

    return $response;

  }
}
