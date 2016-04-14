<?php

namespace Drupal\search_api\Query;

use Drupal\search_api\IndexInterface;

/**
 * Represents a search query on a Search API index.
 *
 * Methods not returning something else will return the object itself, so calls
 * can be chained.
 */
interface QueryInterface extends ConditionSetInterface {

  /**
   * Constant representing ascending sorting.
   */
  const SORT_ASC = 'ASC';

  /**
   * Constant representing descending sorting.
   */
  const SORT_DESC = 'DESC';

  /**
   * Instantiates a new instance of this query class.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which the query should be created.
   * @param \Drupal\search_api\Query\ResultsCacheInterface $results_cache
   *   The results cache that should be used for this query.
   * @param array $options
   *   (optional) The options to set for the query.
   *
   * @return static
   *   A query object to use.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if a search on that index (or with those options) won't be
   *   possible.
   */
  public static function create(IndexInterface $index, ResultsCacheInterface $results_cache, array $options = array());

  /**
   * Retrieves the parse modes supported by this query class.
   *
   * @return string[][]
   *   An associative array of parse modes recognized by objects of this class.
   *   The keys are the parse modes' IDs, values are associative arrays
   *   containing the following entries:
   *   - name: The translated name of the parse mode.
   *   - description: (optional) A translated text describing the parse mode.
   */
  public function parseModes();

  /**
   * Retrieves the parse mode.
   *
   * @return string
   *   The parse mode.
   */
  public function getParseMode();

  /**
   * Sets the parse mode.
   *
   * @param string $parse_mode
   *   The parse mode.
   *
   * @return $this
   */
  public function setParseMode($parse_mode);

  /**
   * Creates a new condition group to use with this query object.
   *
   * @param string $conjunction
   *   The conjunction to use for the condition group – either 'AND' or 'OR'.
   * @param string[] $tags
   *   (optional) Tags to set on the condition group.
   *
   * @return \Drupal\search_api\Query\ConditionGroupInterface
   *   A condition group object that is set to use the specified conjunction.
   */
  public function createConditionGroup($conjunction = 'AND', array $tags = array());

  /**
   * Sets the keys to search for.
   *
   * If this method is not called on the query before execution, this will be a
   * filters-only query.
   *
   * @param string|array|null $keys
   *   A string with the search keys, in one of the formats specified by
   *   getKeys(). A passed string will be parsed according to the set parse
   *   mode. Use NULL to not use any search keys.
   *
   * @return $this
   */
  public function keys($keys = NULL);

  /**
   * Sets the fields that will be searched for the search keys.
   *
   * If this is not called, all fulltext fields will be searched.
   *
   * @param array $fields
   *   An array containing fulltext fields that should be searched.
   *
   * @return $this
   */
  public function setFulltextFields(array $fields = NULL);

  /**
   * Adds a sort directive to this search query.
   *
   * If no sort is manually set, the results will be sorted descending by
   * relevance.
   *
   * @param string $field
   *   The field to sort by. The special fields 'search_api_relevance' (sort by
   *   relevance) and 'search_api_id' (sort by item id) may be used.
   * @param string $order
   *   The order to sort items in – one of the SORT_* constants.
   *
   * @return $this
   *
   * @see \Drupal\search_api\Query\QueryInterface::SORT_ASC
   * @see \Drupal\search_api\Query\QueryInterface::SORT_DESC
   */
  public function sort($field, $order = self::SORT_ASC);

  /**
   * Adds a range of results to return.
   *
   * This will be saved in the query's options. If called without parameters,
   * this will remove all range restrictions previously set.
   *
   * @param int|null $offset
   *   The zero-based offset of the first result returned.
   * @param int|null $limit
   *   The number of results to return.
   *
   * @return $this
   */
  public function range($offset = NULL, $limit = NULL);

  /**
   * Executes this search query.
   *
   * @return \Drupal\search_api\Query\ResultSetInterface
   *   The results of the search.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an error occurred during the search.
   */
  public function execute();

  /**
   * Prepares the query object for the search.
   *
   * This method should always be called by execute() and contain all necessary
   * operations before the query is passed to the server's search() method.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if any wrong options were set on the query (e.g., conditions or
   *   sorts on unknown fields).
   */
  public function preExecute();

  /**
   * Postprocesses the search results before they are returned.
   *
   * This method should always be called by execute() and contain all necessary
   * operations after the results are returned from the server.
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $results
   *   The search results returned by the server.
   */
  public function postExecute(ResultSetInterface $results);

  /**
   * Retrieves the index associated with this search.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The search index this query should be executed on.
   */
  public function getIndex();

  /**
   * Retrieves the search keys for this query.
   *
   * @return array|string|null
   *   This object's search keys – either a string or an array specifying a
   *   complex search expression.
   *   An array will contain a '#conjunction' key specifying the conjunction
   *   type, and search strings or nested expression arrays at numeric keys.
   *   Additionally, a '#negation' key might be present, which means – unless it
   *   maps to a FALSE value – that the search keys contained in that array
   *   should be negated, i.e. not be present in returned results. The negation
   *   works on the whole array, not on each contained term individually – i.e.,
   *   with the "AND" conjunction and negation, only results that contain all
   *   the terms in the array should be excluded; with the "OR" conjunction and
   *   negation, all results containing one or more of the terms in the array
   *   should be excluded.
   *
   * @see keys()
   */
  public function &getKeys();

  /**
   * Retrieves the unparsed search keys for this query as originally entered.
   *
   * @return array|string|null
   *   The unprocessed search keys, exactly as passed to this object. Has the
   *   same format as the return value of QueryInterface::getKeys().
   *
   * @see keys()
   */
  public function getOriginalKeys();

  /**
   * Retrieves the fulltext fields that will be searched for the search keys.
   *
   * @return string[]|null
   *   An array containing the fields that should be searched for the search
   *   keys, or NULL if all indexed fulltext fields should be used.
   *
   * @see setFulltextFields()
   */
  public function &getFulltextFields();

  /**
   * Retrieves the condition group object associated with this search query.
   *
   * @return \Drupal\search_api\Query\ConditionGroupInterface
   *   This object's associated condition group object.
   */
  public function getConditionGroup();

  /**
   * Retrieves the sorts set for this query.
   *
   * @return string[]
   *   An array specifying the sort order for this query. Array keys are the
   *   field IDs in order of importance, the values are the respective order in
   *   which to sort the results according to the field.
   *
   * @see sort()
   */
  public function &getSorts();

  /**
   * Retrieves an option set on this search query.
   *
   * @param string $name
   *   The name of the option.
   * @param mixed $default
   *   (optional) The value to return if the specified option is not set.
   *
   * @return mixed
   *   The value of the option with the specified name, if set. $default
   *   otherwise.
   */
  public function getOption($name, $default = NULL);

  /**
   * Sets an option for this search query.
   *
   * @param string $name
   *   The name of an option. The following options are recognized by default:
   *   - conjunction: The type of conjunction to use for this query – either
   *     'AND' or 'OR'. 'AND' by default. This only influences the search keys,
   *     condition groups will always use AND by default.
   *   - offset: The position of the first returned search results relative to
   *     the whole result in the index.
   *   - limit: The maximum number of search results to return. -1 means no
   *     limit.
   *   - 'search id': A string that will be used as the identifier when storing
   *     this search in the Search API's static cache.
   *   - 'skip result count': If present and set to TRUE, the search's result
   *     count will not be needed. Service classes can check for this option to
   *     possibly avoid executing expensive operations to compute the result
   *     count in cases where it is not needed.
   *   - search_api_access_account: The account which will be used for entity
   *     access checks, if available and enabled for the index.
   *   - search_api_bypass_access: If set to TRUE, entity access checks will be
   *     skipped, even if enabled for the index.
   *   However, contrib modules might introduce arbitrary other keys with their
   *   own, special meaning. (Usually they should be prefixed with the module
   *   name, though, to avoid conflicts.)
   * @param mixed $value
   *   The new value of the option.
   *
   * @return mixed
   *   The option's previous value, or NULL if none was set.
   */
  public function setOption($name, $value);

  /**
   * Retrieves all options set for this search query.
   *
   * The return value is a reference to the options so they can also be altered
   * this way.
   *
   * @return array
   *   An associative array of query options.
   */
  public function &getOptions();

  /**
   * Sets the given tag on this query.
   *
   * Tags are strings that categorize a query. A query may have any number of
   * tags. Tags are used to mark a query so that alter hooks may decide if they
   * wish to take action. Tags should be all lower-case and contain only
   * letters, numbers, and underscore, and start with a letter. That is, they
   * should follow the same rules as PHP identifiers in general.
   *
   * The call will be ignored if the tag is already set on this query.
   *
   * @param string $tag
   *   The tag to set.
   *
   * @return $this
   *
   * @see hook_search_api_query_TAG_alter()
   */
  public function addTag($tag);

  /**
   * Checks whether a certain tag was set on this search query.
   *
   * @param string $tag
   *   The tag to check for.
   *
   * @return bool
   *   TRUE if the tag was set for this search query, FALSE otherwise.
   */
  public function hasTag($tag);

  /**
   * Determines whether this query has all the given tags set on it.
   *
   * @param string ...
   *   The tags to check for.
   *
   * @return bool
   *   TRUE if all the method parameters were set as tags on this query; FALSE
   *   otherwise.
   */
  public function hasAllTags();

  /**
   * Determines whether this query has any of the given tags set on it.
   *
   * @param string ...
   *   The tags to check for.
   *
   * @return bool
   *   TRUE if any of the method parameters was set as a tag on this query;
   *   FALSE otherwise.
   */
  public function hasAnyTag();

  /**
   * Retrieves the tags set on this query.
   *
   * @return string[]
   *   The tags associated with this search query, as both the array keys and
   *   values. Returned by reference so it's possible to, e.g., remove existing
   *   tags.
   */
  public function &getTags();

}
