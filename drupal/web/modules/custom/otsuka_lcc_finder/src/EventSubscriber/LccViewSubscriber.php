<?php

namespace Drupal\otsuka_lcc_finder\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * Event Subscriber LccEventSubscriber.
 */
class LccViewSubscriber implements EventSubscriberInterface {

  /**
   * The router doing the actual routing.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $router;

  /**
   * The account to use in access checks.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs a router for Drupal with access check and upcasting.
   *
   * @param \Symfony\Component\Routing\Matcher\RequestMatcherInterface $router
   *   The router doing the actual routing.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to use in access checks.
   */
  public function __construct(RequestMatcherInterface $router, AccountInterface $account) {
    $this->router = $router;
    $this->account = $account;
  }

  /**
   * Process lcc_finder view page.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   Event.
   */
  public function onRequest(KernelEvent $event) {
    static $once = TRUE;

    $request = $event->getRequest();

    // Path alias is hidden in form, so check unaliased URL only.
    if ($event->isMasterRequest() && strpos($request->getPathInfo(), '/node/') === 0 && $once) {
      $parameters = $this->router->matchRequest($request);
      if ($parameters['_route'] != 'entity.node.canonical') {
        return;
      }

      /* @var $node \Drupal\node\Entity\Node */
      $node = $parameters['node'];
      if ($node->bundle() != 'lcc') {
        return;
      }

      $access_result = $node->access('edit', $this->account, TRUE);
      if ($access_result->isAllowed()) {
        /* @var $route \Symfony\Component\Routing\Route */
        $route = $parameters[RouteObjectInterface::ROUTE_OBJECT];
        $route->setOption('_admin_route', TRUE);
        $request->attributes->add($parameters);
      }
      else {
        throw new NotFoundHttpException();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest', 50];
    return $events;
  }

}
