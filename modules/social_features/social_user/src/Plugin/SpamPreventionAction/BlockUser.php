<?php

namespace Drupal\social_user\Plugin\SpamPreventionAction;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\gaia_spam_prevention\Plugin\SpamPreventionAction\SpamPreventionActionBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Blocks the user who created the content.
 *
 * @SpamPreventionAction(
 *   id = "block_user",
 *   label = @Translation("Block User"),
 *   description = @Translation("Blocks the user who created the content.")
 * )
 */
class BlockUser extends SpamPreventionActionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The session manager.
   */
  protected SessionManagerInterface $sessionManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('session_manager')
    );
  }

  /**
   * Constructs a BlockUser plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The session manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, SessionManagerInterface $session_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->sessionManager = $session_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ContentEntityInterface $entity, int $score): void {
    if (!$this->canExecute($entity)) {
      return;
    }

    $user_id = $entity->get('uid')->target_id;
    if (!$user_id) {
      return;
    }

    $user = $this->entityTypeManager->getStorage('user')->load($user_id);
    if (!$user instanceof UserInterface) {
      return;
    }

    // Don't block user if they have bypass permission.
    if ($user->hasPermission('bypass spam prevention')) {
      return;
    }

    $user->block();
    $user->save();

    // Force logout the user.
    $this->sessionManager->delete((int) $user_id);
  }

  /**
   * {@inheritdoc}
   */
  public function canExecute(ContentEntityInterface $entity): bool {
    return $entity->hasField('uid');
  }

}
