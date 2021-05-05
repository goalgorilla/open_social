<?php

namespace Drupal\social_mentions\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_profile\SocialProfileTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AutocompleteController.
 *
 * @todo Add parameters here to prevent referencing users without access to node.
 *
 * @package Drupal\social_mentions\Controller
 */
class AutocompleteController extends ControllerBase {

  use SocialProfileTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * AutocompleteController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $configFactory, Connection $database, EntityTypeManagerInterface $entityTypeManager) {
    $this->configFactory = $configFactory;
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Function for suggestions.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns a JsonResponse.
   */
  public function suggestions(Request $request) {
    $name = $request->get('term');
    $config = $this->configFactory->get('mentions.settings');
    $suggestion_format = $config->get('suggestions_format');
    $suggestion_amount = $config->get('suggestions_amount');
    $result = $this->getUserIdsFromName($name, $suggestion_amount, $suggestion_format);
    $response = [];
    $accounts = User::loadMultiple($result);
    $storage = $this->entityTypeManager->getStorage('profile');
    $view_builder = $this->entityTypeManager->getViewBuilder('profile');

    /** @var \Drupal\Core\Session\AccountInterface $account */
    foreach ($accounts as $account) {
      $item = [
        'uid' => $account->id(),
        'username' => $account->getAccountName(),
        'value' => $account->getAccountName(),
        'html_item' => '<div>' . $account->getAccountName() . '</div>',
        'profile_id' => '',
      ];

      if ($storage && ($profile = $storage->loadByUser($account, 'profile', TRUE)) && $suggestion_format != SOCIAL_PROFILE_SUGGESTIONS_USERNAME) {
        $build = $view_builder->view($profile, 'autocomplete_item');
        $item['html_item'] = render($build);
        $item['profile_id'] = $profile->id();
        $item['value'] = strip_tags($account->getDisplayName());
      }

      $response[] = $item;
    }

    return new JsonResponse($response);
  }

}
