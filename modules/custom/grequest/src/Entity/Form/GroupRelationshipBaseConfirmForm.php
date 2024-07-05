<?php

namespace Drupal\grequest\Entity\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Group content base confirmation form.
 *
 * @ingroup group
 */
class GroupRelationshipBaseConfirmForm extends ContentEntityConfirmFormBase {

  /**
   * Returns the plugin responsible for this piece of group relationship.
   *
   * @return \Drupal\group\Plugin\Group\Relation\GroupRelationInterface
   *   The responsible group relation.
   */
  protected function getPlugin() {
    /** @var \Drupal\group\Entity\GroupRelationInterface $group_relationship */
    $group_relationship = $this->getEntity();
    return $group_relationship->getPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return str_replace('-', '_', parent::getFormId());
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelURL() {
    /** @var \Drupal\group\Entity\GroupRelationInterface $group_relationship */
    $group_relationship = $this->getEntity();
    $group = $group_relationship->getGroup();
    $route_params = [
      'group' => $group->id(),
      'group_content' => $group_relationship->id(),
    ];
    return new Url('entity.group_content.canonical', $route_params);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'core/drupal.form';
    return $form;
  }

}
