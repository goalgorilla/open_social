<?php

/**
 * @file
 * Contains \Drupal\token\Controller\TokenDevelController.
 */

namespace Drupal\token\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\token\TokenEntityMapperInterface;
use Drupal\token\TreeBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Devel integration for tokens.
 */
class TokenDevelController extends ControllerBase {

  /**
   * @var \Drupal\token\TreeBuilderInterface
   */
  protected $treeBuilder;

  /**
   * @var \Drupal\token\TokenEntityMapperInterface
   */
  protected $entityMapper;

  public function __construct(TreeBuilderInterface $tree_builder, TokenEntityMapperInterface $entity_mapper) {
    $this->treeBuilder = $tree_builder;
    $this->entityMapper = $entity_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token.tree_builder'),
      $container->get('token.entity_mapper')
    );
  }

  /**
   * Prints the loaded structure of the current entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *    A RouteMatch object.
   *
   * @return array
   *    Array of page elements to render.
   */
  public function entityTokens(RouteMatchInterface $route_match) {
    $output = [];

    $parameter_name = $route_match->getRouteObject()->getOption('_token_entity_type_id');
    $entity = $route_match->getParameter($parameter_name);

    if ($entity && $entity instanceof EntityInterface) {
      $output = $this->renderTokenTree($entity);
    }

    return $output;
  }

  /**
   * Render the token tree for the specified entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which the token tree should be rendered.
   *
   * @return array
   *   Render array of the token tree for the $entity.
   *
   * @see static::entityLoad
   */
  protected function renderTokenTree(EntityInterface $entity) {
    $this->moduleHandler()->loadInclude('token', 'pages.inc');
    $entity_type = $entity->getEntityTypeId();

    $token_type = $this->entityMapper->getTokenTypeForEntityType($entity_type);
    $options = [
      'flat' => TRUE,
      'values' => TRUE,
      'data' => [$token_type => $entity],
    ];

    $token_tree = [
      $token_type => [
        'tokens' => $this->treeBuilder->buildTree($token_type, $options),
      ],
    ];
//    foreach ($tree as $token => $token_info) {
//      if (!isset($token_info['value']) && !empty($token_info['parent']) && !isset($tree[$token_info['parent']]['value'])) {
//        continue;
//      }
//    }

    $build['tokens'] = [
      '#type' => 'token_tree_table',
      '#show_restricted' => FALSE,
      '#skip_empty_values' => TRUE,
      '#token_tree' => $token_tree,
      '#columns' => ['token', 'value'],
      '#empty' => $this->t('No tokens available.'),
    ];

    return $build;
  }
}
