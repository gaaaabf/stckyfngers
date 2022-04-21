<?php

namespace Drupal\custom_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\custom_core\UserModel;

/**
 * Class PageController.
 */
class PageController extends ControllerBase {

  protected $database;

  protected $entity_type_manager;

  protected $user_model;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, EntityTypeManager $entity_type_manager) {
    $this->database = $database;
    $this->entity_type_manager = $entity_type_manager;
    $this->user_model = new UserModel($database, $entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function topPage(Request $request) {

    $items = ['1', '2', '3'];

    return array(
      '#theme' => 'top_page',
      '#items' => $items,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function artistsPage(Request $request) {

    \Kint::dump($this->user_model);

    $items = ['artist1', 'artist2', 'artist3'];

    return array(
      '#theme' => 'artists_page',
      '#items' => $items,
    );
  }  
}
