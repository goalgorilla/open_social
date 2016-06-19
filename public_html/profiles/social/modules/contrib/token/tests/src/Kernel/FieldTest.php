<?php

/**
 * @file
 * Contains Drupal\Tests\token\Kernel\FieldTest.
 */

namespace Drupal\Tests\token\Kernel;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests field tokens.
 *
 * @group token
 */
class FieldTest extends KernelTestBase {

  /**
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $testFormat;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'text', 'field', 'filter'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    // Create the article content type with a text field.
    $node_type = NodeType::create([
      'type' => 'article',
    ]);
    $node_type->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'node',
      'type' => 'text',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Test field',
    ]);
    $field->save();

    $this->testFormat = FilterFormat::create([
      'format' => 'test',
      'weight' => 1,
      'filters' => [
        'filter_html_escape' => ['status' => TRUE],
      ],
    ]);
    $this->testFormat->save();
  }

  /**
   * Tests [entity:field_name] tokens.
   */
  public function testEntityFieldTokens() {
    // Create a node with a value in the text field and test its token.
    $entity = Node::create([
      'title' => 'Test node title',
      'type' => 'article',
      'test_field' => [
        'value' => 'foo',
        'format' => $this->testFormat->id(),
      ],
    ]);
    $entity->save();

    $this->assertTokens('node', ['node' => $entity], [
      'test_field' => Markup::create('foo'),
    ]);

    // Create a node without a value in the text field and test its token.
    $entity = Node::create([
      'title' => 'Test node title',
      'type' => 'article',
    ]);
    $entity->save();

    $this->assertNoTokens('node', ['node' => $entity], ['test_field']);
  }

  /**
   * Tests the token metadata for a field token.
   */
  public function testFieldTokenInfo() {
    /** @var \Drupal\token\Token $tokenService */
    $tokenService = \Drupal::service('token');

    // Test the token info of the text field of the artcle content type.
    $token_info = $tokenService->getTokenInfo('node', 'test_field');
    $this->assertEqual($token_info['name'], 'Test field', 'The token info name is correct.');
    $this->assertEqual($token_info['description'], 'Text (formatted) field.', 'The token info description is correct.');
    $this->assertEqual($token_info['module'], 'token', 'The token info module is correct.');

    // Now create two more content types that share the field but the last
    // of them sets a different label. This should show an alternative label
    // at the token info.
    $node_type = NodeType::create([
      'type' => 'article2',
    ]);
    $node_type->save();
    $field = FieldConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'node',
      'bundle' => 'article2',
      'label' => 'Test field',
    ]);
    $field->save();

    $node_type = NodeType::create([
      'type' => 'article3',
    ]);
    $node_type->save();
    $field = FieldConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'node',
      'bundle' => 'article3',
      'label' => 'Different test field',
    ]);
    $field->save();

    $token_info = $tokenService->getTokenInfo('node', 'test_field');
    $this->assertEqual($token_info['name'], 'Test field', 'The token info name is correct.');
    $this->assertEqual((string) $token_info['description'], 'Text (formatted) field. Also known as <em class="placeholder">Different test field</em>.', 'When a field is used in several bundles with different labels, this is noted at the token info description.');
    $this->assertEqual($token_info['module'], 'token', 'The token info module is correct.');
  }

  /**
   * Test tokens on node with the token view mode overriding default formatters.
   */
  public function testTokenViewMode() {
    $value = 'A really long string that should be trimmed by the special formatter on token view we are going to have.';

    // The formatter we are going to use will eventually call Unicode::strlen.
    // This expects that the Unicode has already been explicitly checked, which
    // happens in DrupalKernel. But since that doesn't run in kernel tests, we
    // explicitly call this here.
    Unicode::check();

    // Create a node with a value in the text field and test its token.
    $entity = Node::create([
      'title' => 'Test node title',
      'type' => 'article',
      'test_field' => [
        'value' => $value,
        'format' => $this->testFormat->id(),
      ],
    ]);
    $entity->save();

    $this->assertTokens('node', ['node' => $entity], [
      'test_field' => Markup::create($value),
    ]);

    // Now, create a token view mode which sets a different format for
    // test_field. When replacing tokens, this formatter should be picked over
    // the default formatter for the field type.
    // @see field_tokens().
    $view_mode = EntityViewMode::create([
      'id' => 'node.token',
      'targetEntityType' => 'node',
    ]);
    $view_mode->save();
    $entity_display = entity_get_display('node', 'article', 'token');
    $entity_display->setComponent('test_field', [
      'type' => 'text_trimmed',
      'settings' => [
        'trim_length' => 50,
      ]
    ]);
    $entity_display->save();

    $this->assertTokens('node', ['node' => $entity], [
      'test_field' => Markup::create(substr($value, 0, 50)),
    ]);
  }
}
