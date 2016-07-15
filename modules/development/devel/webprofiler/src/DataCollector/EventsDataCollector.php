<?php

/**
 * @file
 * Contains \Drupal\webprofiler\DataCollector\EventsDataCollector.
 */

namespace Drupal\webprofiler\DataCollector;

use Drupal\webprofiler\DrupalDataCollectorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpKernel\DataCollector\EventDataCollector as BaseEventDataCollector;

/**
 * Class EventsDataCollector
 */
class EventsDataCollector extends BaseEventDataCollector implements DrupalDataCollectorInterface {

  use StringTranslationTrait, DrupalDataCollectorTrait;

  /**
   * @return int
   */
  public function getCalledListenersCount() {
    return count($this->getCalledListeners());
  }

  /**
   * @return int
   */
  public function getNotCalledListenersCount() {
    return count($this->getNotCalledListeners());
  }

  /**
   * {@inheritdoc}
   */
  public function setCalledListeners(array $listeners) {
    $listeners = $this->computePriority($listeners);
    $this->data['called_listeners'] = $listeners;
  }

  /**
   * Adds the priority value to the $listeners array.
   *
   * @param array $listeners
   * @return array
   */
  private function computePriority(array $listeners) {
    foreach ($listeners as &$listener) {
      foreach ($listener['class']::getSubscribedEvents() as $event => $methods) {

        if (is_string($methods)) {
          $methods = [[$methods], 0];
        }
        else {
          if (is_string($methods[0])) {
            $methods = [$methods];
          }
        }

        foreach ($methods as $method) {
          if ($listener['event'] === $event) {
            if ($listener['method'] === $method[0]) {
              $listener['priority'] = isset($method[1]) ? $method[1] : 0;
            }
          }
        }
      }
    }

    return $listeners;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('Events');
  }

  /**
   * {@inheritdoc}
   */
  public function getPanelSummary() {
    return $this->t('Called listeners: @listeners', ['@listeners' => count($this->getCalledListeners())]);
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon() {
    return 'iVBORw0KGgoAAAANSUhEUgAAABUAAAAcCAYAAACOGPReAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABFJJREFUeNrkVVlIY2cY/RMTE81NMkkajUs1OBqkiVsjjAtStGrtSGyFjOjAQNVCKRb66ot9KrjgQx+FUgTBKkURbIfighWl4r6h44pajcZEo3ESTeKS9PzB2AyNZaD1qRcOem+S83/f+c53Lsvj8ZD/+mKTB7gehJTj+2d9fZ1MTk6S0NBQSW9vb97e3t7jmpqaXzIzM185HA7vd4KDg8nGxoaysbGxVCwWm/V6/aDL5TKlpKSQpKSkv5NyuVxyc3Mj7e7u/jw2NjYxJyfnMDIykmGz2UQgEBAWi0XcbjeRSqWhZWVl4v39fXVXV5cqNzf3exxmCNj+9fU1MzQ09JVWq32sUqmMu7u7QhwiDwoKIoeHh2R7e5twOByCwcrQhUShUJjz8vJkw8PDX5+fn8sDkvb3938YHR39rlAoNBoMBgGqtWxubnJRKbu9vZ20trZSQoJnvKioKMvZ2Rn/6urKmpqayvT19ekCks7NzaUnJyeboK0kPj7+cGZmJprH4zGnp6duEBFUTg4ODqjmIfPz87GQxoRnori4ODOKUPuTsnw+RRvPGIYJMZvNDNplYmJiLvPz839oamoSj4yMfAJNuRqN5mV9ff0fOPDF1NSUAt85lclkDkjnys7O/vGOlZLeQgjIgUggnmqHqmMqKip+z8jI8MAFnpKSkpXZ2dn38JkIUAFRQNjt/R2Xv09twBFwAGwClunp6efLy8tZdFgUW1tbiaOjo1/is9fUhcA+YL69fzvzSyQSEQZHfBJBT4J2Bf9qo9Rq9bxcLndeXl4STJrA8B4Mc/atN4pesAk5OTkh1PB0exYXF/kWi4UTFhZG+Hw+wZQJ5BDR7fEPIroYASu9uLggJpOJYO2I0+kkqI47Njb2MdzAKS4uXisvL5/FurIGBgaeYoDS1dVVsrKyQpaWlghsF7hS2IJERER4T4U/qckT4ccP6BYplco+rOcxqn0fZFqj0fgkLS3tV18m0EICktJV9F101xcWFj5Cu+HQ1YGNoeSXWGErpv8IwVOSlZXVh7xw0zy4V1MY3/uXWgetMzB8EZUHw7lKSEjgQ0MONLei2kcTExN5R0dHMehshw7x3umLRKI7YDhaDOSJ18hstq2qquobLMG30DKYkuzs7KggTa5Pf4p/rJReSCud1WplEBYuSMGrra39FG1ywsPDgwsLC+0YFoMAKi0qKupA5c57K0V1XjsdHx+/g6mXUksVFBS8wmF23FeMj48/w7PXiLsxePcG65qPDNCsra15XRCQFNP1AgRPMaA4aOvp6OjQ2O12cVtb20vE389YAHFLS0sO2vbYbLYQHKRHShEEy5ul+kIAe02Q5vy6urouTNyDV8VNT0/PBGzzxW1wRIHsM7T+W3V1tROvEE9lZeUCKlVgSfyD6S9SGsKdnZ1pOp3OkJ6efj04OPgTnmsAlv8PACXa/Q4L4UByuZqbm/UNDQ1vkLL+3+/9ByH9U4ABADscgvUMKuLiAAAAAElFTkSuQmCC';
  }
}
