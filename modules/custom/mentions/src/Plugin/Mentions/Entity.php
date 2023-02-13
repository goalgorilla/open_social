<?php

namespace Drupal\mentions\Plugin\Mentions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\mentions\MentionsPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Mention entity.
 *
 * @Mention(
 *  id = "entity",
 *  name = @Translation("Entity")
 * )
 */
class Entity implements MentionsPluginInterface {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  private Token $tokenService;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Entity constructor.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(Token $token, EntityTypeManagerInterface $entity_type_manager) {
    $this->tokenService = $token;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $token = $container->get('token');
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $token,
      $entity_type_manager
    );
  }

  /**
   * {@inheritdoc}
   */
  public function outputCallback(array $mention, array $settings): array {
    $entity = $this->entityTypeManager->getStorage($mention['target']['entity_type'])
      ->load($mention['target']['entity_id']);
    $output = [];
    $output['value'] = $this->tokenService->replace($settings['value'], [$mention['target']['entity_type'] => $entity]);
    $output['render_plain'] = $entity && !$entity->access('view');
    if ($settings['renderlink']) {
      $output['link'] = $this->tokenService->replace($settings['rendertextbox'], [$mention['target']['entity_type'] => $entity]);
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function targetCallback(string $value, array $settings): array {
    $entity_type = $settings['entity_type'];
    $input_value = $settings['value'];
    $query = $this->entityTypeManager->getStorage($entity_type)->getQuery();
    $query->accessCheck(FALSE);
    $result = $query->condition($input_value, $value)->execute();
    $target = [];

    if (!empty($result) && is_array($result)) {
      $result = reset($result);
      $target['entity_type'] = $entity_type;
      $target['entity_id'] = $result;
    }

    return $target;
  }

}
