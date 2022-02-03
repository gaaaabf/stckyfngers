<?php

namespace Drupal\light_saml_idp\EventSubscriber;

use Drupal\Core\Routing\RouteMatch;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class StoreAnonymousSAMLRequestSubscriber
 *
 * @package Drupal\light_saml_idp\EventSubscriber
 */
class StoreAnonymousSAMLRequestSubscriber implements EventSubscriberInterface {

  /** @var \Drupal\Core\Session\AccountProxyInterface */
  protected $account;

  /**
   * StoreAnonymousSAMLRequestSubscriber constructor.
   */
  public function __construct() {
    $this->account = \Drupal::currentUser();
  }

  /**
   * Store the SAML request data in the user's session.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   */
  public function storeSAMLRequest(GetResponseForExceptionEvent $event) {
    if ($event->getException() instanceof AccessDeniedHttpException && $this->account->isAnonymous()) {
      $request = $event->getRequest();
      $routeName = RouteMatch::createFromRequest($request)->getRouteName();
      $samlRequest = $request->get('SAMLRequest');
      $samlRelaystate = $request->get('RelayState');

      if ($routeName == 'light_saml_idp.login' && $samlRequest) {
        $session = $request->getSession();
        $session->set('light_saml_idp_saml_request', $samlRequest);
        $session->set('light_saml_idp_relaystate', $samlRelaystate);
      }
    }
  }

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['storeSAMLRequest', 100];
    return $events;
  }

}
