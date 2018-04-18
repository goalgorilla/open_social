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
      'revert' => 'social_gdpr.data_policy.revision_revert',
    ];

    foreach ($build['data_policy_revisions_table']['#rows'] as &$row) {
      foreach ($row[1]['data']['#links'] as $operation => &$link) {
        if (isset($routes[$operation])) {
          $link['url'] = Url::fromRoute(
            $routes[$operation],
            $link['url']->getRouteParameters()
          );
        }

        switch ($operation) {
          case 'revert':
            $link['url'] = Url::fromRoute(
              'social_gdpr.data_policy.revision_revert',
              $link['url']->getRouteParameters()
            );

            $link['attributes'] = [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'title' => $this->t('Are you sure to revert this revision'),
                'width' => 700,
              ]),
            ];
            break;
        }
      }
    }

    return $build;
  }

}
