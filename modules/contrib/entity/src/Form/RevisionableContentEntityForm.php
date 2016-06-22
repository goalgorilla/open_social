<?php

/**
 * @file
 * Contains \Drupal\entity\Form\RevisionableContentEntityForm.
 */

namespace Drupal\entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity\Entity\RevisionableEntityBundleInterface;

/**
 * Extends the base entity form with revision support in the UI.
 */
class RevisionableContentEntityForm extends ContentEntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface|\Drupal\entity\Revision\EntityRevisionLogInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    parent::prepareEntity();

    $bundle_entity = $this->getBundleEntity();

    // Set up default values, if required.
    if (!$this->entity->isNew()) {
      $this->entity->setRevisionLogMessage(NULL);
    }

    if ($bundle_entity instanceof RevisionableEntityBundleInterface) {
      // Always use the default revision setting.
      $this->entity->setNewRevision($bundle_entity && $bundle_entity->shouldCreateNewRevision());
    }
  }

  /**
   * Returns the bundle entity of the entity, or NULL if there is none.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  protected function getBundleEntity() {
    if ($bundle_key = $this->entity->getEntityType()->getKey('bundle')) {
      return $this->entity->{$bundle_key}->referencedEntities()[0];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $entity_type = $this->entity->getEntityType();
    $bundle_entity = $this->getBundleEntity();
    $account = $this->currentUser();

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit %bundle_label @label', [
        '%bundle_label' => $bundle_entity ? $bundle_entity->label() : '',
        '@label' => $this->entity->label(),
      ]);
    }

    $form['advanced'] = [
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    ];

    // Add a log field if the "Create new revision" option is checked, or if the
    // current user has the ability to check that option.
    // @todo Could we autogenerate this form by using some widget on the
    //   revision info field.
    $form['revision_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Revision information'),
      // Open by default when "Create new revision" is checked.
      '#open' => $this->entity->isNewRevision(),
      '#group' => 'advanced',
      '#weight' => 20,
      '#access' => $this->entity->isNewRevision() || $account->hasPermission($entity_type->get('admin_permission')),
    ];

    $form['revision_information']['revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => $this->entity->isNewRevision(),
      '#access' => $account->hasPermission($entity_type->get('admin_permission')),
    ];

    // Check the revision log checkbox when the log textarea is filled in.
    // This must not happen if "Create new revision" is enabled by default,
    // since the state would auto-disable the checkbox otherwise.
    if (!$this->entity->isNewRevision()) {
      $form['revision_information']['revision']['#states'] = [
        'checked' => [
          'textarea[name="revision_log"]' => ['empty' => FALSE],
        ],
      ];
    }

    $form['revision_information']['revision_log'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Revision log message'),
      '#rows' => 4,
      '#default_value' => $this->entity->getRevisionLogMessage(),
      '#description' => $this->t('Briefly describe the changes you have made.'),
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('revision')) {
      $this->entity->setNewRevision();
    }

    $insert = $this->entity->isNew();
    $this->entity->save();
    $context = ['@type' => $this->entity->bundle(), '%info' => $this->entity->label()];
    $logger = $this->logger($this->entity->id());
    $bundle_entity = $this->getBundleEntity();
    $t_args = ['@type' => $bundle_entity ? $bundle_entity->label() : 'None', '%info' => $this->entity->label()];

    if ($insert) {
      $logger->notice('@type: added %info.', $context);
      drupal_set_message($this->t('@type %info has been created.', $t_args));
    }
    else {
      $logger->notice('@type: updated %info.', $context);
      drupal_set_message($this->t('@type %info has been updated.', $t_args));
    }

    if ($this->entity->id()) {
      $form_state->setValue('id', $this->entity->id());
      $form_state->set('id', $this->entity->id());

      if ($this->entity->getEntityType()->hasLinkTemplate('collection')) {
        $form_state->setRedirectUrl($this->entity->toUrl('collection'));
      }
      else {
        $form_state->setRedirectUrl($this->entity->toUrl('canonical'));
      }
    }
    else {
      // In the unlikely case something went wrong on save, the entity will be
      // rebuilt and entity form redisplayed.
      drupal_set_message($this->t('The entity could not be saved.'), 'error');
      $form_state->setRebuild();
    }
  }

}
