<?php

namespace Drupal\social_auth_extra;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;

/**
 * Class UserManager
 * @package Drupal\social_auth_extra
 */
abstract class UserManager implements UserManagerInterface {

  /**
   * Object of a user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * Object of a user profile.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $profile;

  /**
   * Contains the field definition with a profile picture.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldPicture;

  /**
   * Contains the profile type.
   *
   * @var string
   */
  protected $profileType = 'profile';

  protected $configFactory;
  protected $entityTypeManager;
  protected $languageManager;
  protected $entityFieldManager;
  protected $token;
  protected $transliteration;
  protected $loggerFactory;

  /**
   * UserManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\Core\Utility\Token $token
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, EntityFieldManagerInterface $entity_field_manager, Token $token, TransliterationInterface $transliteration, LoggerChannelFactoryInterface $logger_factory) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->token = $token;
    $this->transliteration = $transliteration;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function createAccount($values = []) {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $values = array_merge([
      'name' => '',
      'mail' => '',
      'init' => '',
      'pass' => NULL,
      'status' => 1,
      'langcode' => $langcode,
      'preferred_langcode' => $langcode,
      'preferred_admin_langcode' => $langcode,
    ], $values);
    $this->account = $this->entityTypeManager
      ->getStorage('user')
      ->create($values);

    return $this->account;
  }

  /**
   * {@inheritdoc}
   */
  public function createProfile($values = []) {
    $values = array_merge([
      'uid' => $this->account ? $this->account->id() : NULL,
      'type' => $this->profileType,
    ], $values);
    $this->profile = $this->entityTypeManager
      ->getStorage('profile')
      ->create($values);

    return $this->profile;
  }

  /**
   * {@inheritdoc}
   */
  public function setProfilePicture($url, $account_id) {
    if ($this->profile && ($file = $this->downloadProfilePicture($url, $account_id))) {
      !$this->account ?: $file->setOwner($this->account);
      $file->save();
      $field_name = $this->fieldPicture->getName();
      $this->profile->get($field_name)->setValue($file->id());

      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function downloadProfilePicture($url, $account_id) {
    $key = $this->getSocialNetworkKey();

    if (!$url || !$account_id) {
      return FALSE;
    }

    if (!$directory = $this->getPictureDirectory()) {
      return FALSE;
    }

    if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      $this->loggerFactory
        ->get('social_auth_' . $key)
        ->error('The image could not be saved, the directory @directory is not valid.', [
          '@directory' => $directory,
        ]);

      return FALSE;
    }

    $filename = $this->transliteration->transliterate($key . '_' . $account_id . '.jpg', 'en', '_', 50);
    $destination = "{$directory}/{$filename}";

    if (!$file = system_retrieve_file($url, $destination, TRUE, FILE_EXISTS_REPLACE)) {
      $this->loggerFactory
        ->get('social_auth_' . $key)
        ->error('The file @filename could not downloaded.', [
          '@filename' => $filename,
        ]);

      return FALSE;
    }

    return $file;
  }

  /**
   * {@inheritdoc}
   */
  public function getPictureDirectory() {
    if ($this->fieldPicture instanceof FieldDefinitionInterface) {
      // Prepare directory where downloaded image will be saved.
      $scheme = $this->configFactory
        ->get('system.file')
        ->get('default_scheme');

      $directory = $this->fieldPicture->getSetting('file_directory');
      $directory = "{$scheme}://{$directory}";
      $directory = $this->token->replace($directory);
      $directory = $this->transliteration->transliterate($directory, 'en', '_', 50);

      return $directory;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setProfile(ProfileInterface $profile) {
    $this->profile = $profile;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccount(UserInterface $account) {
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldPicture(FieldDefinitionInterface $field) {
    $this->fieldPicture = $field;
  }

  /**
   * {@inheritdoc}
   */
  public function setProfileType($profile_type) {
    $this->profileType = $profile_type;
  }

}
