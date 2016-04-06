<?php

/**
 * @file
 * Contains \Drupal\profile\Form\ProfileTypeDeleteForm.
 */

namespace Drupal\profile\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a confirmation form for deleting a Profile type entity.
 */
class ProfileTypeDeleteForm extends EntityDeleteForm {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a new ProductTypeDeleteForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *    The entity query object.
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
    $num_profiles = $this->queryFactory->get('profile')
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($num_profiles) {
      $caption = '<p>' . \Drupal::translation()
          ->formatPlural($num_profiles, '%type is used by 1 profile on your site. You can not remove this profile type until you have removed all of the %type profiles.', '%type is used by @count profiles on your site. You may not remove %type until you have removed all of the %type profiles.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->entity->label();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
