<?php

namespace Drupal\social_topic\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a 'TopicAddBlock' block.
 *
 * @Block(
 *  id = "topic_add_block",
 *  admin_label = @Translation("Topic add block"),
 * )
 */
class TopicAddBlock extends BlockBase implements ContainerInjectionInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $routeMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('route_match')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Custom access logic to display the block only on current user Topic page.
   */
  protected function blockAccess(AccountInterface $account) {
    $route_user_id = $this->routeMatch->getParameter('user');
    if ($account->id() == $route_user_id) {
      return AccessResult::allowed();
    }
    // By default, the block is not visible.
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $url = Url::fromUserInput('/node/add/topic');
    $link_options = array(
      'attributes' => array(
        'class' => array(
          'btn',
          'btn-primary',
          'btn-raised',
          'waves-effect',
          'brand-bg-primary',
        ),
      ),
    );
    $url->setOptions($link_options);

    $build['content'] = Link::fromTextAndUrl($this->t('Create Topic'), $url)
      ->toRenderable();

    return $build;
  }

}
