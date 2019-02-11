<?php

namespace Drupal\social_content_report\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\user\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'ContentReportActivityContext' activity context.
 *
 * @ActivityContext(
 *  id = "content_report_activity_context",
 *  label = @Translation("Content report activity context"),
 * )
 */
class ContentReportActivityContext extends ActivityContextBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryFactory $entity_query, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_query, $entity_type_manager);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.query.sql'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    $ids = $this->entityTypeManager->getStorage('user')->getQuery()
      ->condition('status', 1)
      ->condition('roles', $this->getRolesWithPermission(), 'IN')
      ->execute();

    if (!empty($ids)) {
      // Create a list of recipients in the expected format.
      foreach ($ids as $uid) {
        $recipients[] = [
          'target_type' => 'user',
          'target_id' => $uid,
        ];
      }
    }

    return $recipients;
  }

  /**
   * Returns the role with the required permission.
   *
   * @return array
   *   A list of Role IDs.
   */
  protected function getRolesWithPermission() {
    $roles_with_perm = [];
    $roles = Role::loadMultiple();

    // Check for each role which one has permission to "view inappropriate
    // reports".
    foreach ($roles as $role) {
      /* @var \Drupal\user\RoleInterface $role */
      if ($role->hasPermission('view inappropriate reports')) {
        $roles_with_perm[] = $role->id();
      }
    }

    return $roles_with_perm;
  }

}
