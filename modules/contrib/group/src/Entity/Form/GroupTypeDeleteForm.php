<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Form\GroupTypeDeleteForm.
 */

namespace Drupal\group\Entity\Form;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for group type deletion.
 */
class GroupTypeDeleteForm extends EntityDeleteForm {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a new GroupTypeDeleteForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query object.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_groups = $this->queryFactory->get('group')
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();

    if (!empty($num_groups)) {
      $common = ' You can not remove this group type until you have removed all of the %type groups.';
      $single = '%type is used by 1 group on your site.' . $common;
      $multiple = '%type is used by @count groups on your site.' . $common;
      $replace = ['%type' => $this->entity->label()];

      $form['#title'] = $this->getQuestion();
      $form['description'] = [
        '#markup' => '<p>' . $this->formatPlural($num_groups, $single, $multiple, $replace) . '</p>'
      ];

      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
