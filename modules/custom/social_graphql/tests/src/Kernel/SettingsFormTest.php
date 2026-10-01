<?php

declare(strict_types=1);

namespace Drupal\Tests\social_graphql\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\social_graphql\Form\SettingsForm;

/**
 * Builds and submits the GraphQL API settings form.
 *
 * @coversDefaultClass \Drupal\social_graphql\Form\SettingsForm
 * @group social_graphql
 */
final class SettingsFormTest extends SocialGraphQLTestBase {

  /**
   * The checkbox mirrors the stored value.
   *
   * @covers ::getFormId
   * @covers ::buildForm
   */
  public function testCheckboxReflectsStoredValue(): void {
    $form = $this->buildSettingsForm();
    self::assertSame('social_graphql_settings', $form['#form_id']);
    self::assertSame('checkbox', $form['allow_session_auth']['#type']);
    self::assertFalse($form['allow_session_auth']['#default_value']);

    $this->config('social_graphql.settings')
      ->set('allow_session_auth', TRUE)
      ->save();

    self::assertTrue($this->buildSettingsForm()['allow_session_auth']['#default_value']);
  }

  /**
   * A site that has not run the update hook yet renders an unticked box.
   *
   * @covers ::buildForm
   */
  public function testCheckboxDefaultsToFalseWhenTheKeyIsMissing(): void {
    $this->config('social_graphql.settings')
      ->clear('allow_session_auth')
      ->save();

    self::assertFalse($this->buildSettingsForm()['allow_session_auth']['#default_value']);
  }

  /**
   * Both checkbox states reach the config object.
   *
   * @covers ::submitForm
   * @covers ::getEditableConfigNames
   */
  public function testSubmitWritesBothStates(): void {
    $this->submitSettingsForm(TRUE);
    self::assertTrue($this->config('social_graphql.settings')->get('allow_session_auth'));

    $this->submitSettingsForm(FALSE);
    self::assertFalse($this->config('social_graphql.settings')->get('allow_session_auth'));
  }

  /**
   * Builds the settings form through the form builder.
   *
   * @return array<string, mixed>
   *   The built form array.
   */
  private function buildSettingsForm(): array {
    return $this->container->get('form_builder')->getForm(SettingsForm::class);
  }

  /**
   * Submits the settings form with one checkbox value.
   */
  private function submitSettingsForm(bool $allow_session_auth): void {
    $form_state = new FormState();
    // An unchecked box reaches the value callback as NULL, the way a browser
    // omits it. FALSE is the callback's "no input" sentinel and would keep
    // the stored value instead of clearing it.
    $form_state->setValues(['allow_session_auth' => $allow_session_auth ? 1 : NULL]);
    $this->container->get('form_builder')->submitForm(SettingsForm::class, $form_state);
    self::assertSame([], $form_state->getErrors());
  }

}
