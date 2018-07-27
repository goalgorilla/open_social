<?php

namespace Drupal\social_gdpr\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
use Drupal\data_policy\Controller\DataPolicy as DataPolicyBase;

/**
 * Class DataPolicy.
 *
 *  Returns responses for Data policy route.
 */
class DataPolicy extends DataPolicyBase {

  /**
   * {@inheritdoc}
   */
  public function entityOverviewAccess() {
    $access = parent::entityOverviewAccess();

    if ($access->isForbidden() && $this->currentUser()->hasPermission('edit data policy')) {
      $access = AccessResult::allowed();
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function revisionOverviewTitle($data_policy_revision) {
    /** @var \Drupal\data_policy\Entity\DataPolicyInterface $data_policy */
    $data_policy = $this->entityTypeManager()->getStorage('data_policy')
      ->loadRevision($data_policy_revision);

    return $this->t('Revision from %date', [
      '%date' => $this->dateFormatter()->format($data_policy->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function revisionsOverviewPage() {
    $build = parent::revisionsOverviewPage();

    $routes = [
      'view' => 'social_gdpr.data_policy.revision',
      'edit' => 'social_gdpr.data_policy.revision_edit',
      'delete' => 'social_gdpr.data_policy.revision_delete',
    ];

    foreach ($build['data_policy_revisions_table']['#rows'] as &$row) {
      foreach ($row[1]['data']['#links'] as $operation => &$link) {
        /** @var \Drupal\Core\Url $url */
        $url = &$link['url'];

        if ($operation == 'revert') {
          if ($url->getRouteName() == 'entity.data_policy.revision_revert') {
            $route_name = 'social_gdpr.data_policy.revision_revert';
          }
          else {
            $route_name = 'social_gdpr.data_policy.translation_revert';
          }

          $link['url'] = Url::fromRoute($route_name, $url->getRouteParameters());

          $link['attributes'] = [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'dialogClass' => 'social-dialog',
              'title' => '',
              'width' => 360,
            ]),
          ];
        }

        if (isset($routes[$operation])) {
          $link['url'] = Url::fromRoute($routes[$operation], $link['url']->getRouteParameters());
        }
      }

      /** @var \Drupal\Core\Url $url */
      $url = &$row[1]['data']['#links']['view']['url'];

      $parameters = $url->getRouteParameters();

      $row = [
        'data' => $row,
        'class' => ['revision-' . $parameters['data_policy_revision']],
      ];
    }

    return $build;
  }

}
