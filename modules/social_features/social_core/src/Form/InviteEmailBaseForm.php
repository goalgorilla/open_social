<?php

namespace Drupal\social_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class InviteBaseForm.
 */
class InviteEmailBaseForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The current group from route.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->loggerFactory = $container->get('logger.factory');
    $instance->routeMatch = $instance->getRouteMatch();
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'invite_email_base_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['users_fieldset'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'class' => [
          'form-horizontal',
        ],
      ],
    ];

    // @todo Validation should go on the element and return a nice list.
    $form['users_fieldset']['user'] = [
      '#title' => $this->t('Find people by name or email address'),
      '#type' => 'select2',
      '#description' => $this->t('You can enter or paste multiple entries separated by comma or semicolon'),
      '#multiple' => TRUE,
      '#tags' => TRUE,
      '#autocomplete' => TRUE,
      '#selection_handler' => 'social',
      '#target_type' => 'user',
      '#select2' => [
        'tags' => TRUE,
        'placeholder' => t('Jane Doe, johndoe@example.com'),
        'tokenSeparators' => [',', ';'],
        'autocomplete' => FALSE,
      ],
      '#required' => TRUE,
      '#validated' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send your invite(s) by email'),
    ];

    $form['#attached']['library'][] = 'social_core/invite-form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Custom function to extract email addresses from a string.
   */
  public function extractEmailsFrom($string) {
    // Remove select2 ID parameter.
    $string = str_replace('$ID:', '', $string);
    preg_match_all("/[\._a-zA-Z0-9+-]+@[\._a-zA-Z0-9+-]+/i", $string, $matches);
    return $matches[0];
  }

}
