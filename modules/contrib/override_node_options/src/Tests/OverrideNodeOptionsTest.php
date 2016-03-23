<?php

/**
 * @file
 * Unit tests for the override_node_options module.
 */

namespace Drupal\override_node_options\Tests;

use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\user\UserInterface;

/**
 * Unit tests for the override_node_options module.
 *
 * @group override_node_options
 */
class OverrideNodeOptionsTest extends WebTestBase {

  /**
   * A standard authenticated user.
   *
   * @var UserInterface $normalUser
   */
  protected $normalUser;

  /**
   * An administrator user.
   *
   * @var UserInterface $adminUser
   */
  protected $adminUser;

  /**
   * A node to test against.
   *
   * @var NodeInterface $node
   */
  protected $node;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['override_node_options'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $types = NodeType::loadMultiple();
    if (empty($types['article'])) {
      $this->drupalCreateContentType(['type' => 'page', 'name' => t('Page')]);
    }

    $this->normalUser = $this->drupalCreateUser([
      'create page content',
      'edit any page content',
    ]);
    $this->node = $this->drupalCreateNode();
  }

  /**
   * Assert that fields in a node were updated to certain values.
   *
   * @param NodeInterface $node
   *   The node object to check (will be reloaded from the database).
   * @param array $fields
   *   An array of values to check equality, keyed by node object property.
   */
  public function assertNodeFieldsUpdated(NodeInterface $node, array $fields, $vid = NULL) {
    if (!$vid) {
      // Re-load the node from the database to make sure we have the current
      // values.
      $node = node_load($node->id(), TRUE);
    }
    if ($vid) {
      $node = node_revision_load($vid);
    }

    foreach ($fields as $field => $value) {
      $this->assertEqual(
        $node->get($field)->value,
        $value,
        t('Node :field was updated to :value, expected :expected.',
          [
            ':field' => $field,
            ':value' => $node->get($field)->value,
            ':expected' => $value,
          ]
        )
      );
    }
  }

  /**
   * Assert that the user cannot access fields on node add and edit forms.
   *
   * @param NodeInterface $node
   *   The node object, will be used on the node edit form.
   * @param array $fields
   *   An array of form fields to check.
   */
  public function assertNodeFieldsNoAccess(NodeInterface $node, array $fields) {
    $this->drupalGet('node/add/' . $node->getType());
    foreach ($fields as $field) {
      $this->assertNoFieldByName($field);
    }

    $this->drupalGet('node/' . $this->node->id() . '/edit');
    foreach ($fields as $field) {
      $this->assertNoFieldByName($field);
    }
  }

  /**
   * Test the 'Authoring information' fieldset.
   */
  public function testNodeOptions() {
    $this->adminUser = $this->drupalCreateUser([
      'create page content',
      'edit any page content',
      'override page published option',
      'override page promote to front page option',
      'override page sticky option',
    ]);
    $this->drupalLogin($this->adminUser);

    $fields = ['promote' => TRUE, 'sticky' => TRUE];

    $this->drupalPostForm('node/' . $this->node->id() . '/edit', ['promote[value]' => TRUE, 'sticky[value]' => TRUE], t('Save and keep published'));
    $this->assertNodeFieldsUpdated($this->node, $fields);

    $this->drupalLogin($this->normalUser);
    $this->assertNodeFieldsNoAccess($this->node, array_keys($fields));
  }

  /**
   * Test the 'Revision information' fieldset.
   */
  public function testNodeRevisions() {
    $this->adminUser = $this->drupalCreateUser([
      'create page content',
      'edit any page content',
      'override page revision option',
    ]);
    $this->drupalLogin($this->adminUser);

    $fields = ['revision' => TRUE];

    $this->drupalPostForm('node/' . $this->node->id() . '/edit', $fields, t('Save'));
    $this->assertNodeFieldsUpdated($this->node, [], $this->node->getRevisionId());

    $this->drupalLogin($this->normalUser);
    $this->assertNodeFieldsNoAccess($this->node, array_keys($fields));
  }

  /**
   * Test the 'Authoring information' fieldset.
   */
  public function testNodeAuthor() {
    $this->adminUser = $this->drupalCreateUser(
      [
        'create page content',
        'edit any page content',
        'override page authored on option',
        'override page authored by option',
      ]
    );
    $this->drupalLogin($this->adminUser);

    $this->drupalPostForm('node/' . $this->node->id() . '/edit', ['uid[0][target_id]' => 'invalid-user'], t('Save'));
    $this->assertText('There are no entities matching "invalid-user".');

    $this->drupalPostForm('node/' . $this->node->id() . '/edit', array('created[0][value][date]' => 'invalid-date'), t('Save'));
    $this->assertText('The Authored on date is invalid.');

    $time = time();
    $fields = [
      'uid[0][target_id]' => '',
      'created[0][value][date]' => \Drupal::service('date.formatter')->format($time, 'custom', 'Y-m-d'),
      'created[0][value][time]' => \Drupal::service('date.formatter')->format($time, 'custom', 'H:i:s'),
    ];
    $this->drupalPostForm('node/' . $this->node->id() . '/edit', $fields, t('Save'));
    $this->assertNodeFieldsUpdated($this->node, ['uid' => 0, 'created' => $time]);

    $this->drupalLogin($this->normalUser);
    $this->assertNodeFieldsNoAccess($this->node, array_keys($fields));
  }
}
