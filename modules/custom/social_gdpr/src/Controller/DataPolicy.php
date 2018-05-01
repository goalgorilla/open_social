<?php

namespace Drupal\social_gdpr\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\gdpr_consent\Controller\DataPolicyController;

/**
 * Class DataPolicy.
 *
 *  Returns responses for Data policy route.
 */
class DataPolicy extends DataPolicyController {

  /**
   * {@inheritdoc}
   */
  public function revisionPageTitle($data_policy_revision) {
    /** @var \Drupal\gdpr_consent\Entity\DataPolicyInterface $data_policy */
    $data_policy = $this->entityTypeManager()->getStorage('data_policy')
      ->loadRevision($data_policy_revision);

    return $this->t('Revision from %date', [
      '%date' => $this->dateFormatter()->format($data_policy->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function revisionOverview() {
    $build = parent::revisionOverview();

    $routes = [
      'view' => 'social_gdpr.data_policy.revision',
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
    }

    return $build;
  }

}
