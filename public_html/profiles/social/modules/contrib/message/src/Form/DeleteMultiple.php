<?php

/**
 * @file
 * Contains \Drupal\message\Form\DeleteMultiple.
 */

namespace Drupal\message\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\message\Entity\Message;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\user\TempStoreFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a message deletion confirmation form.
 */
class DeleteMultiple extends ConfirmFormBase {
  /**
   * The array of messages to delete.
   *
   * @var array
   */
  protected $messages = [];

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The message storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $manager;

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityManagerInterface $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('message');
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
    return 'message_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return \Drupal::translation()->formatPlural(count($this->messages), 'Are you sure you want to delete this item?', 'Are you sure you want to delete these items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->messages = $this->tempStoreFactory->get('message_multiple_delete_confirm')->get(\Drupal::currentUser()->id());
    if (empty($this->messages)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    $form['messages'] = [
      '#theme' => 'item_list',
      '#items' => array_map(function (Message $message) {
        $params = [
          '@id' => $message->id(),
          '@type' => $message->getType()->label(),
        ];
        return t('Delete message ID @id fo type @type', $params);
      }, $this->messages),
    ];
    $form = parent::buildForm($form, $form_state);

    $form['actions']['cancel']['#href'] = $this->getCancelRoute();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->messages)) {
      $this->storage->delete($this->messages);
      $this->tempStoreFactory->get('message_multiple_delete_confirm')->delete(\Drupal::currentUser()->id());
      $count = count($this->messages);
      $this->logger('message')->notice('Deleted @count posts.', ['@count' => $count]);
      drupal_set_message(\Drupal::translation()->formatPlural($count, 'Deleted 1 message.', 'Deleted @count messages.'));
    }
    $form_state->setRedirect('message.messages');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('message.messages');
  }

}
