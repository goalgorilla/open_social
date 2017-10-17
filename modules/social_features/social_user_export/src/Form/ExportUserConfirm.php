<?php

namespace Drupal\social_user_export\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\user\UserStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Class ExportUserConfirm.
 *
 * @package Drupal\social_user_export\Form
 */
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

    $export_params = [
      'apply_all' => !empty($data['apply_all']),
      'accounts' => [],
      'query' => [],
    ];

    // If not selected all items, show list of selected items,
    // else show quantity of all items.
    if (empty($data['apply_all'])) {
      $form['accounts'] = [
        '#prefix' => '<ul>',
        '#suffix' => '</ul>',
        '#tree' => TRUE,
      ];

      foreach ($data['entities'] as $account) {
        $export_params['accounts'][] = $account->id();
        $form['accounts'][] = [
          '#type' => 'markup',
          '#markup' => '<li>' . $account->getDisplayName() . '</li>',
        ];
      }
    }
    else {
      if (empty($data['query'])) {
        $data['query'] = [];
      }

      $export_params['query'] = $data['query'];

      $view = _social_user_export_get_view($data['query']);
      $form['message'] = [
        '#type' => 'item',
        '#markup' => t('@count users will be exported', [
          '@count' => $view->total_rows,
        ]),
      ];
    }

    $form_state->set('export_params', $export_params);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $export_params = $form_state->get('export_params');

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
      if ($export_params['apply_all']) {
        $batch['operations'][] = [
          [
            '\Drupal\social_user_export\ExportUser',
            'exportUsersAllOperation',
          ],
          [
            $export_params['query'],
          ],
        ];
      }
      else {
        foreach ($export_params['accounts'] as $uid) {
          $batch['operations'][] = [
            [
              '\Drupal\social_user_export\ExportUser',
              'exportUserOperation',
            ],
            [User::load($uid)],
          ];
        }

        $batch['operations'] = array_reverse($batch['operations']);
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
