<?php

namespace Drupal\social_group_invite\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for resend membership invite to user.
 */
class SocialGroupInviteResendConfirmForm extends ConfirmFormBase implements ContainerInjectionInterface {

  /**
   * Invite (a group content entity).
   *
   * @var \Drupal\group\Entity\GroupContentInterface|null
   */
  protected $invite;

  /**
   * The action plugin manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $actionManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = new static();
    $instance->redirectDestination = $container->get('redirect.destination');
    $instance->actionManager = $container->get('plugin.manager.action');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_group_invite_resend_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Resend an invitation');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t("Send a reminder to <strong>@user</strong>?", [
      '@user' => $this->invite instanceof EntityInterface ? $this->invite->getEntity()->label() : 'member',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromUserInput($this->getRedirectDestination()->get());
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, GroupInterface $group = NULL, GroupContentInterface $group_content = NULL): array {
    $this->invite = $group_content;

    $form = parent::buildForm($form, $form_state);
    $form['#attributes']['class'][] = 'form--default';
    $form['actions']['#prefix'] = '</div></div>';
    $form['actions']['cancel']['#attributes']['class'][] = 'btn btn-flat';

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if (!($this->invite instanceof GroupContentInterface)) {
      return;
    }

    /** @var \Drupal\social_group_invite\Plugin\Action\SocialGroupInviteResend $action */
    $action = $this->actionManager->createInstance('social_group_invite_resend_action');
    $action->execute($this->invite);

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
