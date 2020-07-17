<?php

namespace Drupal\social_gdpr\Controller;

use Drupal\Core\Access\AccessResult;
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
  public function revisionsOverviewPage($entity_id = NULL) {
    $build = [];
    $entity_ids = $this->dataPolicyConsentManager->getEntityIdsFromConsentText();

    foreach ($entity_ids as $entity_id) {
      /** @var \Drupal\data_policy\Entity\DataPolicyInterface $entity */
      $entity = \Drupal::entityTypeManager()->getStorage('data_policy')->load($entity_id);
      $wrapper = 'wrapper_entity_' . $entity_id;

      $build[$wrapper] = [
        '#type' => 'fieldset',
        '#title' => $entity->getName(),
      ];

      $build[$wrapper]['revisions'] = parent::revisionsOverviewPage($entity_id);
    }

    return $build;
  }

}
