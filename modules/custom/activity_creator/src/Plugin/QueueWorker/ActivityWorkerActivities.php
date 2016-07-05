<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\QueueWorker\ActivityWorkerActivities.
 */

namespace Drupal\activity_creator\Plugin\QueueWorker;
use Drupal\activity_creator\ActivityFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A report worker.
 *
 * @QueueWorker(
 *   id = "activity_creator_activities",
 *   title = @Translation("Process activity activities."),
 *   cron = {"time" = 60}
 * )
 *
 * @TODO Change the weight to make sure it runs after the logger
 * is this possible? See Cron.php::processQueues() and getDefinitions().
 *
 * This QueueWorker is responsible for creating Activity entities and will
 * retrieve use information provided by activity_creator_logger.
 */
class ActivityWorkerActivities extends ActivityWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Activity Factory.
   *
   * @var \Drupal\activity_creator\ActivityFactory
   */
  private $activityFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ActivityFactory $activityFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->activityFactory = $activityFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('activity_creator.activity_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    // @TODO Can one item have multiple destinations; if not: split

    // Let the factory work.
    $this->activityFactory->createActivities($data);

  }

}
