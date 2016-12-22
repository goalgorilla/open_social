<?php
namespace Drupal\mentions\Plugin\Mentions;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Utility\Token;
use Drupal\mentions\MentionsPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * @Mention(
 *  id = "entity",
 *  name = @Translation("Entity")
 * )
 */
class Entity implements MentionsPluginInterface {
  private $token_service;
  private $entity_manager;
  private $entityQuery_service;

  public function __construct(Token $token, EntityManager $entity_manager, QueryFactory $entity_query) {
    $this->token_service = $token;
    $this->entity_manager = $entity_manager;
    $this->entityQuery_service = $entity_query;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $token = $container->get('token');
    $entity_manager = $container->get('entity.manager');
    $entity_query = $container->get('entity.query');
    return new static(
      $token,
      $entity_manager,
      $entity_query
    );
  }

  public function outputCallback($mention, $settings) {
    $entity = $this->entity_manager->getStorage($mention['target']['entity_type'])
      ->load($mention['target']['entity_id']);
    $output['value'] = $this->token_service->replace($settings['value'], array($mention['target']['entity_type'] => $entity));
    if ($settings['renderlink']) {
      $output['link'] = $this->token_service->replace($settings['rendertextbox'], array($mention['target']['entity_type'] => $entity));
    }
    return $output;
  }

  public function targetCallback($value, $settings) {
    $entity_type = $settings['entity_type'];
    $input_value = $settings['value'];
    $query = $this->entityQuery_service->get($entity_type);
    $result = $query->condition($input_value, $value)->execute();

    if (!empty($result)) {
      $result = reset($result);
      $target['entity_type'] = $entity_type;
      $target['entity_id'] = $result;

      return $target;
    }
    else {
      return FALSE;
    }
  }

  public function mentionPresaveCallback($entity) {

  }

  public function patternCallback($settings, $regex) {

  }

  public function settingsCallback($form, $form_state, $type) {

  }

  public function settingsSubmitCallback($form, $form_state, $type) {

  }

}
