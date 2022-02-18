<?php

namespace Drupal\custom_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Class PageController.
 */
class PageController extends ControllerBase {

  public function topPage(Request $request) {

    $items = [];

    return array(
      '#theme' => 'top_page',
      '#items' => $items,
    );
  }  
}
