<?php

namespace Drupal\social_profile\Plugin\search_api\processor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\Query\QueryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The RestrictedNameProcessor adds the restricted name to search indexes.
 *
 * The restricted name is a field that's based on the full name of a user or
 * the nickname, if they have it filled in. This is added to the search index
 * because it's not possibly to search on these fields on a row by row basis.
 * If the `limit_name_display` setting is TRUE then users should not be found
 * by their real name if they have a nickname filled and the user searching does
 * not have the `social profile always show full name` permission.
 *
 * Ensures that when the setting is enabled to be strict with first/last name
 * display and the current user does not have the permission to bypass this
 * setting that the search queries are altered to ignore the first name/last
 * name when the target user has filled in a nickname.
 *
 * Users will not be able to find themselves by first/last name if they have
 * filled in a nickname and do not have the correct permissions. This is
 * intentional because it means that they can test how other users can find
 * them.
 *
 * This processor should run before other processors that affect indexed values
 * such as the IgnoreCase or Tokenizer processor.
 *
 * @SearchApiProcessor(
 *   id = "social_profile_privacy_restricted_name",
 *   label = @Translation("Restricted Name"),
 *   description = @Translation("Adds the restricted name to the index according to the privacy rules."),
 *   stages = {
 *     "add_properties" = 0,
 *     "pre_index_save" = -50,
 *     "preprocess_query" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class RestrictedNameProcessor extends ProcessorPluginBase {

  /**
   * The user viewing the search results being processed.
   */
  protected AccountInterface $currentUser;

  /**
   * The Drupal config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->setCurrentUser($container->get('current_user'));
    $processor->setConfigFactory($container->get('config.factory'));

    return $processor;
  }

  /**
   * Set the current user.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   *
   * @return $this
   */
  public function setCurrentUser(AccountInterface $current_user) : self {
    $this->currentUser = $current_user;
    return $this;
  }

  /**
   * Set the config factory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   *
   * @return $this
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) : self {
    $this->configFactory = $config_factory;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = parent::getPropertyDefinitions($datasource);

    if ($datasource && $this->supportsDataSource($datasource)) {
      $definition = [
        'label' => $this->t('Restricted Name'),
        'description' => $this->t('The display name that is visible for unprivileged users.'),
        'type' => 'search_api_text',
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ];
      $properties['social_profile_privacy_restricted_name'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() : void {
    $datasources = $this->getIndex()->getDatasources();

    // Ensure that we have our "Restricted Name" field for all our supported
    // datasources.
    foreach ($datasources as $datasource_id => $datasource) {
      if ($this->supportsDataSource($datasource)) {
        $this->ensureField($datasource_id, 'social_profile_privacy_restricted_name');
        // Add our new field to the processors we need.
        foreach ($this->getIndex()->getProcessors() as $processor_id => $processor) {
          if (in_array(
            $processor_id,
            ['tokenizer', 'ignorecase', 'transliteration'],
            TRUE
          )) {
            $configuration = $processor->getConfiguration();
            if (!in_array('social_profile_privacy_restricted_name', $configuration['fields'], TRUE)) {
              $configuration['fields'][] = 'social_profile_privacy_restricted_name';
            }
            $processor->setConfiguration($configuration);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) : void {
    $restricted_name = NULL;

    $nickname = $this->getFirstItemField($item, 'field_profile_nick_name');
    // If the user specified a nickname then we will default to using it as
    // restricted name.
    if ($nickname !== '') {
      $restricted_name = $nickname;
    }
    // If the user didn't specify a nickname, we'll allow searching for
    // first/last name.
    else {
      $first_name = $this->getFirstItemField($item, 'field_profile_first_name');
      $last_name = $this->getFirstItemField($item, 'field_profile_last_name');

      $full_name = trim($first_name . ' ' . $last_name);

      if ($full_name !== '') {
        $restricted_name = $full_name;
      }
    }

    // If we have a restricted name we add it as a value for all of our
    // restricted name fields.
    if ($restricted_name) {
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath(
          $item->getFields(),
          $item->getDatasourceId(),
          'social_profile_privacy_restricted_name'
      );
      foreach ($fields as $field) {
        $field->addValue($restricted_name);
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) : void {
    $config = $this->configFactory->get('social_profile.settings');

    // If the use of real names is not limited or the user can bypass this
    // restriction then we're done too.
    if (!$config->get('limit_name_display') || $this->currentUser->hasPermission('social profile always show full name')) {
      return;
    }

    // Get all fields available to search in.
    $all_fields = $query->getFulltextFields() ?? $query->getIndex()->getFulltextFields();

    // Remove the first name and last name from the search query, if a user has
    // no nickname, this value will be searchable through
    // social_profile_privacy_restricted_name. If a user has a nickname then the
    // first and last name is not searchable.
    $search_fields = array_diff(
      $all_fields,
      ['field_profile_first_name', 'field_profile_last_name']
    );

    $query->setFulltextFields($search_fields);
  }

  /**
   * Check whether the datasource is supported by this processor.
   *
   * @param \Drupal\search_api\Datasource\DatasourceInterface $datasource
   *   The data source to verify.
   *
   * @return bool
   *   Whether this processor can use the datasource.
   */
  protected function supportsDataSource(DatasourceInterface $datasource) : bool {
    return $datasource->getEntityTypeId() === 'profile';
  }

  /**
   * Fetches the first value of a field for an item.
   *
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   The item that the field belongs to.
   * @param string $field_name
   *   The name of the field.
   *
   * @return string
   *   The first value of the item or an empty string if no value could be
   *   found.
   */
  protected function getFirstItemField(ItemInterface $item, string $field_name) : string {
    $field = $item->getField($field_name);

    // If the field doesn't exist we default to an empty string.
    if (!$field) {
      return '';
    }

    $field_values = $field->getValues();

    // If the field has no values then we convert it to an empty string.
    if (empty($field_values)) {
      return '';
    }

    return reset($field_values);
  }

}
