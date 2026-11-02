<?php

namespace Drupal\Tests\social_node\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\social_node\SocialNodeForm;

/**
 * Tests the disabled visibility check of the node form validation.
 *
 * @coversDefaultClass \Drupal\social_node\SocialNodeForm
 * @group social_node
 */
class SocialNodeFormVisibilityValidationTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'options',
    'entity',
    'file',
    'entity_access_by_field',
    'social_node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['system', 'node']);
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
    $this->setUpCurrentUser([], ['bypass node access']);
  }

  /**
   * A disabled option is rejected only when it is actually submitted.
   *
   * @param array $input
   *   The user input of the submitted form.
   * @param bool $rejected
   *   Whether the visibility must get an error.
   *
   * @covers ::validateForm
   *
   * @dataProvider providerDisabledVisibility
   */
  public function testDisabledVisibility(array $input, bool $rejected): void {
    $node = Node::create(['type' => 'article', 'title' => $this->randomMachineName()]);
    $node->save();

    $form_object = $this->entityTypeManager->getFormObject('node', 'edit');
    $this->assertInstanceOf(SocialNodeForm::class, $form_object);
    $form_object->setEntity($node);
    $form_state = (new FormState())->setFormObject($form_object);
    $form = $form_object->buildForm([], $form_state);
    $form['field_content_visibility']['widget'] = [
      '#options' => ['public' => 'Public', 'community' => 'Community'],
      'public' => ['#disabled' => TRUE],
      'community' => ['#disabled' => TRUE],
    ];
    $form_state->setUserInput($input);

    $form_object->validateForm($form, $form_state);

    $errors = $form_state->getErrors();
    if ($rejected) {
      $this->assertSame('public visibility is not allowed', (string) $errors['field_content_visibility']);
    }
    else {
      $this->assertSame([], $errors);
    }
  }

  /**
   * Provides the submitted visibility input.
   *
   * @return array<string, array{array, bool}>
   *   The user input, whether the visibility is rejected.
   */
  public static function providerDisabledVisibility(): array {
    return [
      'every radio disabled, key missing' => [[], FALSE],
      'translation, key present as NULL' => [['field_content_visibility' => NULL], FALSE],
      'tampered with a disabled option' => [['field_content_visibility' => 'public'], TRUE],
    ];
  }

}
