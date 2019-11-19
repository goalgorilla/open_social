<?php

namespace Drupal\social_profile_privacy\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\Query\QueryInterface;

/**
 * The RestrictedNameProcessor adds the restricted name to search indexes.
 *
 * The restricted name is a field that's based on the full name of a user or
 * the nickname, if they have it filled in. This is added to the search index
 * because it's not possibly to search on these fields on a row by row basis.
 * If the `limit_search_and_mention` setting is TRUE then users should not be
 * findable by their real name if they have a nickname filled and the user
 * searching does not have the `social profile privacy always show full name`
 * permission.
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
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = parent::getPropertyDefinitions($datasource);

    if ($datasource && $this->supportsDataSource($datasource)) {
      $definition = [
        'label' => $this->t('Restricted Name'),
        'description' => $this->t('The display name that is visible for unpriviliged users.'),
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
  public function preIndexSave() {
    $datasources = $this->getIndex()->getDatasources();

    // Ensure that we have our "Restricted Name" field for all our supported
    // datasources.
    foreach ($datasources as $datasource_id => $datasource) {
      if ($this->supportsDataSource($datasource)) {
        $this->ensureField($datasource_id, 'social_profile_privacy_restricted_name');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
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
  public function preprocessSearchQuery(QueryInterface $query) {
    $config = \Drupal::config('social_profile_privacy.settings');
    $account = \Drupal::currentUser();

    // If the use of real names is not limited or the user can bypass this
    // restriction then we're done too.
    if (!$config->get('limit_search_and_mention') || $account->hasPermission('social profile privacy always show full name')) {
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
