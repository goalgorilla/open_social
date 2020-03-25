<?php

namespace Drupal\social_event_invite\Plugin\views\field;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Entity\Render\EntityTranslationRenderTrait;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the invited email or user if there is an account.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("social_event_invite_recipient")
 */
class SocialEventInviteRecipientField extends FieldPluginBase {

  use EntityTranslationRenderTrait;

  /**
   * The entity type manager interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    return parent::defineOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // If the row result values for the profile id is empty we should check
    // and get the event_enrollment__field_email value.
    $build = [];

    // Check the database for the email and account.
    $recipient = $this->checkEmailAndAccount($values->id);
    // Set the email already.
    if (!empty($recipient['email'])) {
      $build = $recipient['email'];
    }

    // If we have an account then we should load the
    // profile and render it instead of the email.
    if (!empty($recipient['account'])) {
      // Load the account profile.
      /** @var \Drupal\Core\Session\AccountInterface $account */
      $account = $recipient['account'];
      $storage = $this->entityTypeManager->getStorage('profile');
      $profile = $storage->loadByUser($account, 'profile');

      // Build the rendered entity for this profile.
      $entity = $this->getEntity($values);
      if ($entity && $profile) {
        $build = [];
        $entity = $this->getEntityTranslation($entity, $values);
        $access = $entity->access('view', NULL, TRUE);
        $build['#access'] = $access;
        if ($access->isAllowed()) {
          $view_builder = $this->entityTypeManager->getViewBuilder('profile');
          $build += $view_builder->view($profile, 'table', $entity->language()
            ->getId());
        }
        return $build;
      }
    }

    return $build;
  }

  /**
   * Get the recipient by enrollment id.
   *
   * @param string $enrollment_id
   *   The enrollment id.
   *
   * @return array
   *   Return the fetched values.
   */
  private function checkEmailAndAccount($enrollment_id) {
    // Get the recipient email.
    $emailQuery = $this->database->select('event_enrollment__field_event', 'eefev');
    $emailQuery->join('event_enrollment__field_email', 'eefem', 'eefem.entity_id = ' . $enrollment_id);
    $emailQuery->addField('eefem', 'field_email_value');
    $email = $emailQuery->execute()->fetchField();

    // Check if there is an user registered with this email.
    $userQuery = \Drupal::database()->select('users_field_data', 'ufd');
    $userQuery->condition('mail', $email);
    $userQuery->addField('ufd', 'uid');
    $userId = $userQuery->execute()->fetchField();

    $account = NULL;
    if ($userId) {
      $account = $this->entityTypeManager->getStorage('user')->load($userId);
    }

    return [
      'email' => $email,
      'account' => $account,
    ];
  }

//  /**
//   * {@inheritdoc}
//   */
//  public function getCacheContexts() {
//    return [];
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function getCacheTags() {
//    $view_display_storage = $this->entityTypeManager->getStorage('entity_view_display');
//    $view_displays = $view_display_storage->loadMultiple($view_display_storage
//      ->getQuery()
//      ->condition('targetEntityType', $this->getEntityTypeId())
//      ->execute());
//
//    $tags = [];
//    foreach ($view_displays as $view_display) {
//      $tags = array_merge($tags, $view_display->getCacheTags());
//    }
//    return $tags;
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function getCacheMaxAge() {
//    return Cache::PERMANENT;
//  }

  /**
   * @inheritDoc
   */
  public function getEntityTypeId() {
    return $this->getEntityTypeId();
  }

  /**
   * @inheritDoc
   */
  protected function getLanguageManager() {
    return $this->getLanguageManager();
  }

  /**
   * @inheritDoc
   */
  protected function getView() {
    return $this->getView();
  }
}
