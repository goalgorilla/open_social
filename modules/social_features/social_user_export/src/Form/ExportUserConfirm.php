<?php

namespace Drupal\social_user_export\Form;

use \Drupal\Core\Form\ConfirmFormBase;
use \Drupal\user\PrivateTempStoreFactory;
use \Drupal\user\UserStorageInterface;
use \Drupal\Core\Entity\EntityTypeManagerInterface;
use \Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\Core\Url;
use \Drupal\Core\Form\FormStateInterface;
use \Drupal\user\Entity\User;

class ExportUserConfirm extends ConfirmFormBase {

  /**
   * The temp store factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new ExportUserConfirm.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, UserStorageInterface $user_storage, EntityTypeManagerInterface $entity_manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->userStorage = $user_storage;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_export_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Export users');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.user.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Export users');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve the data to be exported from the temp store.
    $data = $this->tempStoreFactory
      ->get('user_operations_export')
      ->get($this->currentUser()->id());

    if (!$data) {
      return $this->redirect('entity.user.collection');
    }

    // If not selected all items, show list of selected items,
    // else show quantity of all items.
    if (empty($data['apply_all'])) {
      $root = NULL;
      $form['accounts'] = [
        '#prefix' => '<ul>',
        '#suffix' => '</ul>',
        '#tree' => TRUE,
      ];

      foreach ($data['entities'] as $account) {
        $uid = $account->id();
        // Prevent user 1 from being canceled.
        if ($uid <= 1) {
          $root = intval($uid) === 1 ? $account : $root;
          continue;
        }

        $form['accounts'][$uid] = [
          '#type' => 'hidden',
          '#value' => $uid,
          '#prefix' => '<li>',
          '#suffix' => $account->label() . "</li>\n",
        ];
      }
    }
    else {
      $query = \Drupal::database()
        ->select('users', 'u')
        ->condition('u.uid', 0, '<>');

      if (!empty($data['query'])) {
        // Apply received conditions to query for getting correct quantity of
        // exported items.
        social_user_export_user_apply_filter($query, $data['query']);

        // Create hidden fields with applied filters to store and use later.
        $form['query'] = [
          '#tree' => TRUE,
        ];

        foreach ($data['query'] as $key => $value) {
          if (is_array($value)) {
            foreach ($value as $key2 => $value2) {
              $form['query'][$key][$key2] = [
                '#type' => 'hidden',
                '#value' => $value2,
              ];
            }
          }
          else {
            $form['query'][$key] = [
              '#type' => 'hidden',
              '#value' => $value,
            ];
          }
        }
      }

      $count = $query
        ->countQuery()
        ->execute()
        ->fetchField();

      $form['message'] = [
        '#type' => 'item',
        '#markup' => t('@count users will be exported', [
          '@count' => $count,
        ]),
      ];
    }

    $form['select_all'] = [
      '#type' => 'hidden',
      '#value' => !empty($data['apply_all']),
    ];

    $form['operation'] = [
      '#type' => 'hidden',
      '#value' => 'export',
    ];

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm')) {
      // Define the batch.
      $batch = [
        'title' => t('Exporting users'),
        'operations' => [],
        'finished' => [
          '\Drupal\social_user_export\ExportUser',
          'finishedCallback',
        ],
      ];

      // If selected all users, add single massive operation,
      // else add one operation for each user.
      if ($form_state->getValue('select_all')) {
        if (!$query = $form_state->getValue('query')) {
          $query = [];
        }

        $batch['operations'][] = [
          [
            '\Drupal\social_user_export\ExportUser',
            'exportUsersAllOperation',
          ],
          [$query],
        ];
      }
      else {
        $accounts = $form_state->getValue('accounts');

        foreach ($accounts as $uid) {
          $batch['operations'][] = [
            [
              '\Drupal\social_user_export\ExportUser',
              'exportUserOperation',
            ],
            [User::load($uid)],
          ];
        }
      }

      batch_set($batch);
    }

    $form_state->setRedirect('entity.user.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Users will be exported to CSV file');
  }

}