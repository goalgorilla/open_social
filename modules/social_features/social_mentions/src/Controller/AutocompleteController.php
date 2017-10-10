<?php

namespace Drupal\social_mentions\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AutocompleteController.
 *
 * TODO Add parameters here to prevent referencing users without access to node.
 *
 * @package Drupal\social_mentions\Controller
 */
class AutocompleteController extends ControllerBase {

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
    $connection = Database::getConnection();
    $name = $connection->escapeLike($name);
    $suggestion_format = $config->get('suggestions_format');

    $query = $this->database->select('users', 'u');
    $query->join('users_field_data', 'uf', 'uf.uid = u.uid');

    switch ($suggestion_format) {
      case SOCIAL_MENTIONS_SUGGESTIONS_USERNAME:
        $query->condition('uf.name', '%' . $name . '%', 'LIKE');
        break;

      case SOCIAL_MENTIONS_SUGGESTIONS_FULL_NAME:
        $query->join('profile', 'p', 'p.uid = u.uid');
        $query->join('profile__field_profile_first_name', 'fn', 'fn.entity_id = p.profile_id');
        $query->join('profile__field_profile_last_name', 'ln', 'ln.entity_id = p.profile_id');

        $or = $query->orConditionGroup();
        $or
          ->condition('fn.field_profile_first_name_value', '%' . $name . '%', 'LIKE')
          ->condition('ln.field_profile_last_name_value', '%' . $name . '%', 'LIKE');
        $query->condition($or);
        break;

      case SOCIAL_MENTIONS_SUGGESTIONS_ALL:
        $query->leftJoin('profile', 'p', 'p.uid = u.uid');
        $query->leftJoin('profile__field_profile_first_name', 'fn', 'fn.entity_id = p.profile_id');
        $query->leftJoin('profile__field_profile_last_name', 'ln', 'ln.entity_id = p.profile_id');

        $or = $query->orConditionGroup();
        $or
          ->condition('uf.name', '%' . $name . '%', 'LIKE')
          ->condition('fn.field_profile_first_name_value', '%' . $name . '%', 'LIKE')
          ->condition('ln.field_profile_last_name_value', '%' . $name . '%', 'LIKE');
        $query->condition($or);
        break;
    }

    $result = $query
      ->fields('u', ['uid'])
      ->condition('uf.status', 1)
      ->range(0, 8)
      ->execute()
      ->fetchCol();

    $response = [];
    $accounts = User::loadMultiple($result);
    $storage = $this->entityTypeManager->getStorage('profile');
    $view_builder = $this->entityTypeManager->getViewBuilder('profile');

    /* @var \Drupal\Core\Session\AccountInterface $account */
    foreach ($accounts as $account) {
      $item = [
        'uid' => $account->id(),
        'username' => $account->getAccountName(),
        'value' => $account->getAccountName(),
        'html_item' => '<div>' . $account->getAccountName() . '</div>',
        'profile_id' => '',
      ];

      if ($storage && ($profile = $storage->loadByUser($account, 'profile', TRUE)) && $suggestion_format != SOCIAL_MENTIONS_SUGGESTIONS_USERNAME) {
        $build = $view_builder->view($profile, 'autocomplete_item');
        $item['html_item'] = render($build);
        $item['profile_id'] = $profile->id();
        $item['value'] = $account->getDisplayName();
      }

      $response[] = $item;
    }

    return new JsonResponse($response);
  }

}
