<?php

namespace Drupal\social_mentions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AutocompleteController.
 *
 * @package Drupal\social_mentions\Controller
 */
class AutocompleteController extends ControllerBase {

  public function suggestions(Request $request) {
    $name = $request->get('term');
    $config = \Drupal::config('mentions.settings');
    $connection = Database::getConnection();
    $name = $connection->escapeLike($name);
    $suggestion_format = $config->get('suggestions_format');

    $query = \Drupal::database()->select('users', 'u');
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
    $storage = \Drupal::entityTypeManager()->getStorage('profile');
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('profile');

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
