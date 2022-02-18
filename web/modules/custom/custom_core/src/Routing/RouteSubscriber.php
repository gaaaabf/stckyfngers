<?php

namespace Drupal\custom_core\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // if ($route = $collection->get('forum.index')) {
    //   $route->setRequirement('_user_is_logged_in', 'TRUE');
    // }
  }

}