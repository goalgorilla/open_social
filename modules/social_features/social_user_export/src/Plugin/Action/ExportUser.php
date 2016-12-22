<?php

namespace Drupal\social_user_export\Plugin\Action;

use \Drupal\Core\Action\ActionBase;
use \Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use \Drupal\Core\Session\AccountInterface;
use \Drupal\user\PrivateTempStoreFactory;
use \Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exports a user accounts to CSV.
 *
 * @Action(
 *   id = "social_user_export_user_action",
 *   label = @Translation("Export the selected user(s) to CSV"),
 *   type = "user",
 *   confirm_form_route_name = "social_user_export.export_user_confirm"
 * )
 */

class ExportUser extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var bool
   */
  protected $apply_all;

  /**
   * @var array
   */
  protected $query = [];

  /**
   * Constructs a ExportUser object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param AccountInterface $current_user
   *   Current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PrivateTempStoreFactory $temp_store_factory, AccountInterface $current_user) {
    $this->currentUser = $current_user;
    $this->tempStoreFactory = $temp_store_factory;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.private_tempstore'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    if ($this->apply_all) {
      $this->tempStoreFactory->get('user_operations_export')->set($this->currentUser->id(), [
        'apply_all' => TRUE,
        'query' => $this->query,
      ]);
    }
    else {
      $this->tempStoreFactory->get('user_operations_export')->set($this->currentUser->id(), [
        'entities' => $entities,
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple([$object]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    return $object->access('view', $account, $return_as_object);
  }

  /**
   * @param bool $apply_all
   */
  public function setApplyAll($apply_all) {
    $this->apply_all = $apply_all;
  }

  /**
   * @param array $query
   */
  public function setQuery(array $query) {
    $this->query = $query;
  }

}