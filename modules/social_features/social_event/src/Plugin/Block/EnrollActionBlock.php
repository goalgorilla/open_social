<?php

namespace Drupal\social_event\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'EnrollActionBlock' block.
 *
 * @Block(
 *  id = "enroll_action_block",
 *  admin_label = @Translation("Enroll action block"),
 * )
 */
class EnrollActionBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * EnrollActionBlock constructor.
   *
   * @param array $configuration
   *   The given configuration.
   * @param string $plugin_id
   *   The given plugin id.
   * @param mixed $plugin_definition
   *   The given plugin definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $routeMatch, FormBuilderInterface $formBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Custom access logic to display the block on the hero region for an event.
   */
  protected function blockAccess(AccountInterface $account) {
    $route_name = $this->routeMatch->getRouteName();
    $routes_to_check = [
      'view.event_enrollments.view_enrollments',
      'entity.node.canonical',
      'view.managers.view_managers',
    ];
    if (in_array($route_name, $routes_to_check)) {
      $node = $this->routeMatch->getParameter('node');
      if (!is_null($node) && !is_object($node)) {
        $node = node_load($node);
      }

      if (is_object($node) && $node->getType() === 'event') {
        // Retrieve the group and if there are groups respect group permission.
        $groups = $this->getGroups($node);
        if (!empty($groups)) {
          foreach ($groups as $group) {
            if ($group->hasPermission('enroll to events in groups', $account)) {
              return AccessResult::allowed();
            }
          }
        }
        else {
          // @TODO Always show the block when the user is already enrolled.
          return AccessResult::allowed();
        }
      }
    }
    // By default, the block is not visible.
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = $this->formBuilder->getForm('Drupal\social_event\Form\EnrollActionForm');

    $render_array = [
      'enroll_action_form' => $form,
    ];

    $text = (string) $this->t('You have enrolled for this event.');

    // Add extra text to.
    if ($form['to_enroll_status']['#value'] === '0') {
      $render_array['feedback_user_has_enrolled'] = [
        '#markup' => '<div><strong>' . $text . '</strong></div>',
      ];
    }

    return $render_array;
  }

  /**
   * Get group object where event enrollment is posted in.
   *
   * Returns an array of Group Objects.
   *
   * @return array
   *   Group entities.
   */
  public function getGroups($node) {

    $groupcontents = GroupContent::loadByEntity($node);

    $groups = [];
    // Only react if it is actually posted inside a group.
    if (!empty($groupcontents)) {
      foreach ($groupcontents as $groupcontent) {
        /* @var \Drupal\group\Entity\GroupContent $groupcontent */
        $group = $groupcontent->getGroup();
        /* @var \Drupal\group\Entity\Group $group*/
        $groups[] = $group;
      }
    }

    return $groups;
  }

}
