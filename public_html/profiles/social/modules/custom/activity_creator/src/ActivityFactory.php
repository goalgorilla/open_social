<?php
/**
 * ActivityFactory
 */

namespace Drupal\activity_creator;

use Drupal\Core\Entity\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ActivityFactory to create Activity items based on ActivityLogs.
 *
 * @package Drupal\activity_creator
 */
abstract class ActivityFactory implements ContainerInterface {

  /**
   * @var \Drupal\Core\Entity\EntityManager
   */
  private $entityManager;


  /**
   * ActivityFactory constructor.
   */
  public function __construct(EntityManager $entityManager) {
    $this->entityManager = $entityManager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      // User the $container to get a query factory object. This object let's us create query objects.
      $container->get('entity.manager')
    );
  }

  /**
   * Create the activities based on a data array.
   *
   * @param array $data
   * @return mixed
   */
  public function createActivities(array $data) {
    $activities = [];

    

    return $activities;
  }
  
}