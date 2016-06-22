<?php

/**
 * @file
 * Contains \Drupal\address\Form\ZoneForm.
 */

namespace Drupal\address\Form;

use Drupal\address\ZoneMemberManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ZoneForm extends EntityForm {

  /**
   * The zone member plugin manager.
   *
   * @var \Drupal\address\ZoneMemberManager
   */
  protected $memberManager;

  /**
   * Creates a ZoneForm instance.
   *
   * @param \Drupal\address\ZoneMemberManager $member_manager
   *   The zone member plugin manager.
   */
  public function __construct(ZoneMemberManager $member_manager) {
    $this->memberManager = $member_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.address.zone_member')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $zone = $this->entity;
    $user_input = $form_state->getUserInput();

    $form['#tree'] = TRUE;
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $zone->getName(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $zone->getId(),
      '#machine_name' => [
        'exists' => '\Drupal\address\Entity\Zone::load',
        'source' => ['name'],
      ],
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['scope'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scope'),
      '#description' => t('Used to group zones by purpose. Examples: tax, shipping.'),
      '#default_value' => $zone->getScope(),
      '#maxlength' => 255,
    ];
    $form['priority'] = [
      '#type' => 'weight',
      '#title' => $this->t('Priority'),
      '#description' => $this->t('Zones with a higher priority will be matched first.'),
      '#default_value' => (int) $zone->getPriority(),
      '#delta' => 10,
    ];

    $wrapper_id = Html::getUniqueId('zone-members-ajax-wrapper');
    $form['members'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Type'),
        $this->t('Zone member'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'zone-member-order-weight',
        ],
      ],
      '#weight' => 5,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    $index = 0;
    foreach ($this->entity->getMembers() as $key => $member) {
      $member_form = &$form['members'][$index];
      $member_form['#attributes']['class'][] = 'draggable';
      $member_form['#weight'] = isset($user_input['members'][$index]) ? $user_input['members'][$index]['weight'] : NULL;

      $member_form['type'] = [
        '#type' => 'markup',
        '#markup' => $member->getPluginDefinition()['name'],
      ];
      $member_parents = ['members', $index, 'form'];
      $member_form_state = $this->buildMemberFormState($member_parents, $form_state);
      $member_form['form'] = $member->buildConfigurationForm([], $member_form_state);
      $member_form['form']['#element_validate'] = ['::memberFormValidate'];

      $member_form['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $member->getName()]),
        '#title_display' => 'invisible',
        '#default_value' => $member->getWeight(),
        '#attributes' => [
          'class' => ['zone-member-order-weight'],
        ],
      ];
      $member_form['remove'] = [
        '#type' => 'submit',
        '#name' => 'remove_member' . $index,
        '#value' => $this->t('Remove'),
        '#limit_validation_errors' => [],
        '#submit' => ['::removeMemberSubmit'],
        '#member_index' => $index,
        '#ajax' => [
          'callback' => '::membersAjax',
          'wrapper' => $wrapper_id,
        ],
      ];

      $index++;
    }

    // Sort the members by weight. Ensures weight is preserved on ajax refresh.
    uasort($form['members'], ['\Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);

    $plugins = [];
    foreach ($this->memberManager->getDefinitions() as $plugin => $definition) {
      $plugins[$plugin] = $definition['name'];
    }
    $form['members']['_new'] = [
      '#tree' => FALSE,
    ];
    $form['members']['_new']['type'] = [
      '#prefix' => '<div class="zone-member-new">',
      '#suffix' => '</div>',
    ];
    $form['members']['_new']['type']['plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Zone member type'),
      '#title_display' => 'invisible',
      '#options' => $plugins,
      '#empty_value' => '',
    ];
    $form['members']['_new']['type']['add_member'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#validate' => ['::addMemberValidate'],
      '#submit' => ['::addMemberSubmit'],
      '#limit_validation_errors' => [['plugin']],
      '#ajax' => [
        'callback' => '::membersAjax',
        'wrapper' => $wrapper_id,
      ],
    ];
    $form['members']['_new']['member'] = [
      'data' => [],
    ];
    $form['members']['_new']['operations'] = [
      'data' => [],
    ];

    return parent::form($form, $form_state);
  }

  /**
   * Ajax callback for member operations.
   */
  public function membersAjax(array $form, FormStateInterface $form_state) {
    return $form['members'];
  }

  /**
   * Validation callback for adding a zone member.
   */
  public function addMemberValidate(array $form, FormStateInterface $form_state) {
    if (!$form_state->getValue('plugin')) {
      $form_state->setErrorByName('plugin', $this->t('Select a zone member type to add.'));
    }
  }

  /**
   * Submit callback for adding a zone member.
   */
  public function addMemberSubmit(array $form, FormStateInterface $form_state) {
    $plugin_id = $form_state->getValue('plugin');
    $member = $this->memberManager->createInstance($plugin_id);
    $this->entity->addMember($member);
    $form_state->setRebuild();
  }

  /**
   * Submit callback for removing a zone member.
   */
  public function removeMemberSubmit(array $form, FormStateInterface $form_state) {
    $member_index = $form_state->getTriggeringElement()['#member_index'];
    $member = $form['members'][$member_index]['form']['#member'];
    $this->entity->removeMember($member);
    $form_state->setRebuild();
  }

  /**
   * Validation callback for the embedded zone member form.
   */
  public function memberFormValidate($member_form, FormStateInterface $form_state) {
    $member = $member_form['#member'];
    $member_form_state = $this->buildMemberFormState($member_form['#parents'], $form_state);
    $member->validateConfigurationForm($member_form, $member_form_state);
    // Update form state with values that might have been changed by the plugin.
    $form_state->setValue($member_form['#parents'], $member_form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    foreach ($form_state->getValue(['members']) as $member_index => $values) {
      $member_form = $form['members'][$member_index]['form'];
      $member = $member_form['#member'];
      $member_form_state = $this->buildMemberFormState($member_form['#parents'], $form_state);
      $member->submitConfigurationForm($member_form, $member_form_state);
      // Update form state with values that might have been changed by the plugin.
      $form_state->setValue($member_form['#parents'], $member_form_state->getValues());
      // Update the member weight.
      $configuration = $member->getConfiguration();
      $configuration['weight'] = $values['weight'];
      $member->setConfiguration($configuration);
      // Update the member on the entity.
      $this->entity->getMembers()->addInstanceId($member->getId(), $configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('Saved the %label zone.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirect('entity.zone.collection');
  }

  /**
   * Builds the form state passed to zone members.
   *
   * @param array $member_parents
   *   The parents array indicating the position of the member form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The parent form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The new member form state.
   */
  protected function buildMemberFormState($member_parents, FormStateInterface $form_state) {
    $member_values = $form_state->getValue($member_parents, []);
    $member_user_input = (array) NestedArray::getValue($form_state->getUserInput(), $member_parents);
    $member_form_state = new FormState();
    $member_form_state->setValues($member_values);
    $member_form_state->setUserInput($member_user_input);

    return $member_form_state;
  }

}
