<?php

declare(strict_types=1);

namespace Drupal\Tests\social_core\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\social_core\Plugin\Field\FieldWidget\InlineEntityFormSimpleConditional;

/**
 * Tests the conditional simple inline entity form widget.
 *
 * @group social_core
 */
final class InlineEntityFormSimpleConditionalTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'inline_entity_form',
    'node',
    'social_core',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['field', 'node', 'user']);

    NodeType::create([
      'type' => 'parent',
      'name' => 'Parent',
    ])->save();
    NodeType::create([
      'type' => 'child',
      'name' => 'Child',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_required_text',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_required_text',
      'entity_type' => 'node',
      'bundle' => 'child',
      'label' => 'Required text',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_reference',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'node',
      ],
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_reference',
      'entity_type' => 'node',
      'bundle' => 'parent',
      'label' => 'Reference',
      'settings' => [
        'handler' => 'default:node',
        'handler_settings' => [
          'target_bundles' => [
            'child' => 'child',
          ],
        ],
      ],
    ])->save();
  }

  /**
   * Tests that a new incomplete inline entity is ignored.
   */
  public function testIncompleteNewInlineEntityIsDiscarded(): void {
    $parent = Node::create([
      'type' => 'parent',
      'title' => 'Parent',
    ]);
    $child = Node::create([
      'type' => 'child',
      'title' => 'Child',
    ]);

    $field_items = $parent->get('field_reference');
    $field_items->setValue([
      [
        'entity' => $child,
      ],
    ]);

    $widget = $this->createWidget();
    $form_state = new FormState();
    $form_state->setValue('field_reference', [
      0 => ['_weight' => 0],
    ]);
    $this->initializeWidgetState($form_state);

    $form = $this->buildWidgetForm($child);
    $widget->extractFormValues($field_items, $form, $form_state);

    $this->assertTrue($field_items->isEmpty());
    $this->assertSame([], $form_state->get(['inline_entity_form', 'field_reference', 'delete']));
  }

  /**
   * Tests that an existing incomplete inline entity is queued for deletion.
   */
  public function testIncompleteExistingInlineEntityIsDeleted(): void {
    $parent = Node::create([
      'type' => 'parent',
      'title' => 'Parent',
    ]);
    $child = Node::create([
      'type' => 'child',
      'title' => 'Child',
      'field_required_text' => 'Original value',
    ]);
    $child->save();

    $field_items = $parent->get('field_reference');
    $field_items->setValue([
      [
        'target_id' => $child->id(),
        'entity' => $child,
      ],
    ]);

    $child->set('field_required_text', NULL);

    $widget = $this->createWidget();
    $form_state = new FormState();
    $form_state->setValue('field_reference', [
      0 => ['_weight' => 0],
    ]);
    $this->initializeWidgetState($form_state);

    $form = $this->buildWidgetForm($child);
    $widget->extractFormValues($field_items, $form, $form_state);

    $this->assertTrue($field_items->isEmpty());
    $this->assertNull(Node::load($child->id()));
  }

  /**
   * Tests that a completed inline entity is retained.
   */
  public function testCompletedInlineEntityIsRetained(): void {
    $parent = Node::create([
      'type' => 'parent',
      'title' => 'Parent',
    ]);
    $child = Node::create([
      'type' => 'child',
      'title' => 'Child',
      'field_required_text' => 'Filled',
    ]);

    $field_items = $parent->get('field_reference');
    $field_items->setValue([
      [
        'entity' => $child,
      ],
    ]);

    $widget = $this->createWidget();
    $form_state = new FormState();
    $form_state->setValue('field_reference', [
      0 => ['_weight' => 0],
    ]);
    $this->initializeWidgetState($form_state);

    $form = $this->buildWidgetForm($child);
    $widget->extractFormValues($field_items, $form, $form_state);

    $this->assertFalse($field_items->isEmpty());
    $kept_child = $field_items->entity;
    $this->assertInstanceOf(Node::class, $kept_child);
    $this->assertSame('Filled', $kept_child->get('field_required_text')->value);
    $this->assertSame([], $form_state->get(['inline_entity_form', 'field_reference', 'delete']));
  }

  /**
   * Tests that broken references render as an empty add widget.
   */
  public function testBrokenReferenceDoesNotBlockWidget(): void {
    $parent = Node::create([
      'type' => 'parent',
      'title' => 'Parent',
    ]);
    $child = Node::create([
      'type' => 'child',
      'title' => 'Child',
      'field_required_text' => 'Filled',
    ]);
    $child->save();

    $field_items = $parent->get('field_reference');
    $field_items->setValue([
      [
        'target_id' => $child->id(),
      ],
    ]);

    $child->delete();

    $widget = $this->createWidget();
    $form_state = new FormState();
    $form = [];
    $element = [
      '#field_parents' => [],
      '#delta' => 0,
      '#weight' => 0,
      '#required' => FALSE,
    ];

    $built = $widget->formElement($field_items, 0, $element, $form, $form_state);

    $this->assertArrayNotHasKey('warning', $built);
    $this->assertArrayHasKey('inline_entity_form', $built);
    $this->assertSame('add', $built['inline_entity_form']['#op']);
    $this->assertTrue($field_items->isEmpty());
  }

  /**
   * Tests that multivalue field metadata does not count as submitted input.
   */
  public function testMultivalueRequiredFieldIgnoresWeightMetadata(): void {
    $form_state = new FormState();
    $form_state->setUserInput([
      'field_reference' => [
        0 => [
          'inline_entity_form' => [
            'field_required_text' => [
              0 => ['_weight' => '0'],
              1 => ['_weight' => '1'],
            ],
          ],
        ],
      ],
    ]);

    $entity_form = [
      '#ief_required_fields' => ['field_required_text'],
      '#parents' => ['field_reference', 0, 'inline_entity_form'],
    ];

    $this->assertFalse($this->hasSubmittedRequiredFieldValues($entity_form, $form_state));
  }

  /**
   * Tests that multivalue field content is still treated as submitted input.
   */
  public function testMultivalueRequiredFieldDetectsActualValue(): void {
    $form_state = new FormState();
    $form_state->setUserInput([
      'field_reference' => [
        0 => [
          'inline_entity_form' => [
            'field_required_text' => [
              0 => [
                'value' => 'Filled',
                '_weight' => '0',
              ],
            ],
          ],
        ],
      ],
    ]);

    $entity_form = [
      '#ief_required_fields' => ['field_required_text'],
      '#parents' => ['field_reference', 0, 'inline_entity_form'],
    ];

    $this->assertTrue($this->hasSubmittedRequiredFieldValues($entity_form, $form_state));
  }

  /**
   * Creates the widget under test.
   */
  private function createWidget(): WidgetInterface {
    $widget = $this->container->get('plugin.manager.field.widget')->createInstance(
      'social_inline_entity_form_simple_conditional',
      [
        'field_definition' => FieldConfig::loadByName('node', 'parent', 'field_reference'),
        'settings' => [
          'form_mode' => 'default',
          'revision' => FALSE,
          'override_labels' => FALSE,
          'label_singular' => '',
          'label_plural' => '',
          'collapsible' => FALSE,
          'collapsed' => FALSE,
          'required_fields' => ['field_required_text'],
        ],
      ],
    );
    assert($widget instanceof WidgetInterface);
    return $widget;
  }

  /**
   * Builds the minimal form structure needed by extractFormValues().
   */
  private function buildWidgetForm(Node $child): array {
    return [
      '#parents' => [],
      'field_reference' => [
        'widget' => [
          0 => [
            'inline_entity_form' => [
              '#entity' => $child,
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Initializes Field API widget state for the simplified test harness.
   */
  private function initializeWidgetState(FormState $form_state): void {
    WidgetBase::setWidgetState([], 'field_reference', $form_state, []);
  }

  /**
   * Invokes the widget's submitted-value helper.
   */
  private function hasSubmittedRequiredFieldValues(array $entity_form, FormState $form_state): bool {
    $method = new \ReflectionMethod(InlineEntityFormSimpleConditional::class, 'hasSubmittedRequiredFieldValues');
    $method->setAccessible(TRUE);

    return $method->invoke(NULL, $entity_form, $form_state);
  }

}
