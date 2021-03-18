<?php

namespace Drupal\social_gdpr\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\data_policy\Controller\DataPolicy as DataPolicyBase;
use Drupal\data_policy\DataPolicyConsentManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DataPolicy.
 *
 *  Returns responses for Data policy route.
 */
class DataPolicy extends DataPolicyBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * DataPolicy constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\data_policy\DataPolicyConsentManagerInterface $data_policy_consent_manager
   *   The Data Policy consent manager.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    DateFormatterInterface $date_formatter,
    RendererInterface $renderer,
    DataPolicyConsentManagerInterface $data_policy_consent_manager,
    Request $request,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($date_formatter, $renderer, $data_policy_consent_manager, $request);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('data_policy.manager'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('entity_type.manager')
    );
  }

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
      $entity = $this->entityTypeManager->getStorage('data_policy')->load($entity_id);
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
