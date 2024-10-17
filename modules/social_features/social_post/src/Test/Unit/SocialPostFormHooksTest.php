<?php

namespace Drupal\Tests\social_post\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\social_post\Hooks\SocialPostFormHooks;
use Drupal\social_post\Service\SocialPostHelperInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit test for SocialPostFormHooks class.
 *
 * @group social_post
 */
class SocialPostFormHooksTest extends UnitTestCase {

  /**
   * The mocked SocialPostHelperInterface.
   *
   * @var \Drupal\social_post\Service\SocialPostHelperInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected SocialPostHelperInterface|MockObject $socialPostHelper;

  /**
   * The mocked AccountProxyInterface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected AccountProxyInterface| MockObject $currentUser;

  /**
   * The mocked form state interface.
   *
   * @var \Drupal\Core\Form\FormStateInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected FormStateInterface|MockObject $formState;

  /**
   * The class under test.
   *
   * @var \Drupal\social_post\Hooks\SocialPostFormHooks
   */
  protected SocialPostFormHooks $socialPostFormHooks;


  /**
   * The drupal form.
   *
   * @var array
   */
  protected array $form = [];

  /**
   * Setup the test case.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->socialPostHelper = $this->createMock(SocialPostHelperInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->formState = $this->createMock(FormStateInterface::class);

    $this->socialPostFormHooks = new SocialPostFormHooks(
      $this->socialPostHelper,
      $this->currentUser
    );

    // Create a mock TranslationInterface.
    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translate')
      ->willReturnCallback(function ($string, array $args = [], array $options = []) {
        // phpcs:ignore
        return new TranslatableMarkup($string, $args, $options);
      });

    // Set up the container with the string_translation service.
    $container = new ContainerBuilder();
    $container->set('string_translation', $translation);
    \Drupal::setContainer($container);

    $this->form = [
      'field_post' => ['widget' => [0 => []]],
      'actions' => ['submit' => ['#value' => '']],
    ];
  }

  /**
   * Test formPostFormAlter with a new post and valid current user image.
   */
  public function testFormPostFormAlterWithNewPostAndImage(): void {
    $this->mockContentEntityForm();
    $currentUserImage = $this->mockUserImage();

    $this->socialPostFormHooks->formPostFormAlter($this->form, $this->formState);

    // Check if the user image is added to the form.
    $this->assertEquals($currentUserImage, $this->form['current_user_image']);

    // Check if submit button caption is set to "Post".
    $this->assertEquals(
      new TranslatableMarkup('Post', [], ['context' => 'Post button']),
      $this->form['actions']['submit']['#value']
    );

    // Check if the title is set correctly.
    $this->assertEquals(
      new TranslatableMarkup('Say something to the Community'),
      $this->form['field_post']['widget'][0]['#title']
    );
  }

  /**
   * Test formPostFormAlter when there's a current group set.
   */
  public function testFormPostFormAlterWithCurrentGroup(): void {
    $this->mockContentEntityForm();
    $currentUserImage = $this->mockUserImage();

    $this->formState->expects($this->exactly(2))
      ->method('get')
      ->willReturnMap([
        ['currentGroup', 2],
        ['recipientUser', NULL],
      ]);

    $this->socialPostFormHooks->formPostFormAlter($this->form, $this->formState);

    // Assert that the current user image is set.
    $this->assertEquals($currentUserImage, $this->form['current_user_image']);

    // Assert that the submit button value is set to "Post".
    $this->assertEquals(
      new TranslatableMarkup('Post', [], ['context' => 'Post button']),
      $this->form['actions']['submit']['#value']
    );

    // Assert the title placeholder value.
    $this->assertEquals(
      new TranslatableMarkup('Say something to the group'),
      $this->form['field_post']['widget'][0]['#title']
    );
    $this->assertEquals(
      new TranslatableMarkup('Say something to the group'),
      $this->form['field_post']['widget'][0]['#placeholder']
    );
  }

  /**
   * Test formPostFormAlter when it's a private message.
   */
  public function testFormPostFormAlterWithRecipientUser(): void {
    $this->mockContentEntityForm();
    $currentUserImage = $this->mockUserImage();

    $recipientUser = $this->createMock(User::class);

    $this->formState->expects($this->exactly(2))
      ->method('get')
      ->willReturnMap([
        ['currentGroup', NULL],
        ['recipientUser', $recipientUser],
      ]);

    $recipientUser->expects($this->once())
      ->method('getDisplayName')
      ->willReturn('John Doe');
    $recipientUser->expects($this->once())
      ->method('id')
      ->willReturn(2);

    $this->currentUser->expects($this->once())
      ->method('id')
      ->willReturn(1);

    // Call the form alter hook.
    $this->socialPostFormHooks->formPostFormAlter($this->form, $this->formState);

    // Assert that the current user image is set.
    $this->assertEquals($currentUserImage, $this->form['current_user_image']);

    // Assert that the submit button value is set to "Post".
    $this->assertEquals(
      new TranslatableMarkup('Post', [], ['context' => 'Post button']),
      $this->form['actions']['submit']['#value']
    );

    // Assert title and placeholder value.
    $this->assertEquals(
      new TranslatableMarkup('Leave a message to @name', ['@name' => 'John Doe']),
      $this->form['field_post']['widget'][0]['#title']
    );
    $this->assertEquals(
      new TranslatableMarkup('Leave a message to @name', ['@name' => 'John Doe']),
      $this->form['field_post']['widget'][0]['#placeholder']
    );
  }

  /**
   * Mock user image.
   *
   * @return array
   *   The mocked user image.
   */
  private function mockUserImage(): array {
    // Mock the SocialPostHelper to return user image.
    $currentUserImage = ['#markup' => 'User Image'];
    $this->socialPostHelper->expects($this->once())
      ->method('buildCurrentUserImage')
      ->willReturn($currentUserImage);
    return $currentUserImage;
  }

  /**
   * Mock content entity form.
   */
  public function mockContentEntityForm(): void {
    $contentEntityForm = $this->createMock(ContentEntityForm::class);
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('isNew')
      ->willReturn(TRUE);

    $contentEntityForm->expects($this->once())
      ->method('getEntity')
      ->willReturn($entity);

    $this->formState->expects($this->once())
      ->method('getFormObject')
      ->willReturn($contentEntityForm);
  }

}
