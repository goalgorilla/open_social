<?php

namespace Drupal\mentions\Plugin\Mentions;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\mentions\MentionsPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\profile\Entity\ProfileInterface;

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $config;

  /**
   * Entity constructor.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory.
   */
  public function __construct(Token $token, EntityTypeManagerInterface $entity_type_manager, ConfigFactory $config) {
    $this->tokenService = $token;
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $token = $container->get('token');
    $entity_type_manager = $container->get('entity_type.manager');
    $config = $container->get('config.factory');
    return new static(
      $token,
      $entity_type_manager,
      $config
    );
  }

  /**
   * {@inheritdoc}
   */
  public function outputCallback(array $mention, array $settings): array {
    $entity = $this->entityTypeManager->getStorage($mention['target']['entity_type'])
      ->load($mention['target']['entity_id']);
    $output = [];

    // If the mentions is run with cron, replace the output ourself.
    if (PHP_SAPI === 'cli') {
      if ($entity instanceof ProfileInterface) {
        $entity = $entity->getOwner();

        // Get the output value according to the config settings.
        $config = $this->config->get('mentions.settings');
        switch ($config->get('suggestions_format')) {
          case SOCIAL_PROFILE_SUGGESTIONS_FULL_NAME:
          case SOCIAL_PROFILE_SUGGESTIONS_ALL:
            $output['value'] = $entity->getDisplayName();
        }
        if (empty($output['value'])) {
          $output['value'] = $entity->getAccountName();
        }

        $output['render_plain'] = !$entity->access('view');
        // Convert to the correct output link based on the mention config.
        // Ex: user/[user:uid] will replace between the brackets for OwnerId.
        $output['link'] = preg_replace("/\[([^\[\]]++|(?R))*+\]/", (string) $entity->id(), $settings['rendertextbox']);
      }
      return $output;
    }

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
