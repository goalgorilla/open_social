<?php

namespace Drupal\social_node\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * The messenger service.
 */
class SocialNodeMessenger extends Messenger implements SocialNodeMessengerInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * SocialNodeMessenger constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface $flash_bag
   *   The flash bag.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $killSwitch
   *   The kill switch.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    FlashBagInterface $flash_bag,
    KillSwitch $killSwitch,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($flash_bag, $killSwitch);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function addStatus($message, $repeat = FALSE) {
    $messages = $this->moduleHandler->invokeAll('social_node_message', [
      $this->node,
    ]);

    if ($messages) {
      $message = end($messages);
    }

    return parent::addStatus($message, $repeat);
  }

  /**
   * {@inheritdoc}
   */
  public function setNode(NodeInterface $node) {
    $this->node = $node;
  }

}
