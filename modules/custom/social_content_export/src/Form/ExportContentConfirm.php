<?php

namespace Drupal\social_content_export\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ExportContentConfirm.
 *
 * @package Drupal\social_content_export\Form
 */
class ExportContentConfirm extends ConfirmFormBase {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The Entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $manager;

  /**
   * Constructs Export Content form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityManagerInterface $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'export_content_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Export content');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('system.admin_content');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Confirm');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve the data to be exported from temp store.
    $data = $this->tempStoreFactory->get('export_content_confirm')->get($this->currentUser()->id());
    if (empty($data)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }
    $export_params = [
      'entities' => [],
      'query' => [],
    ];
    $form['entities'] = [
      '#prefix' => '<ul>',
      '#suffix' => '</ul>',
      '#tree' => TRUE,
    ];
    foreach ($data['entities'] as $node) {
      $export_params['entities'][] = $node->id();
      $form['entities'][] = [
        '#type' => 'markup',
        '#markup' => '<li>' . $node->getTitle() . '</li>',
      ];
    }
    $export_params['query'] = $data['query'];
    $content_number = count($data['entities']);
    $form['message'] = [
      '#type' => 'item',
      '#markup' => t('@count content will be exported', [
        '@count' => $content_number,
      ]),
    ];

    $form_state->set('export_params', $export_params);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $export_params = $form_state->get('export_params');
    $batch = [];
    if ($form_state->getValue('confirm')) {
      $batch = [
        'title' => t('Exporting contents'),
        'operations' => [],
        'finished' => [
          '\Drupal\social_content_export\ExportContent',
          'finishedCallback',
        ],
      ];
    }
    foreach ($export_params['entities'] as $nid) {
      $batch['operations'][] = [
        [
          '\Drupal\social_content_export\ExportContent',
          'exportContentOperation',
        ],
        [
          Node::load($nid),
        ],
      ];
    }

    $batch['operations'] = array_reverse($batch['operations']);

    batch_set($batch);

    $form_state->setRedirect('system.admin_content');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Content will be exported to CSV file');
  }

}
