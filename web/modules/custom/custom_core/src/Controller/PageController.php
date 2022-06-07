<?php

namespace Drupal\custom_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\custom_core\Service\UserModel;
use Drupal\Core\Pager\PagerManagerInterface;

/**
 * Class PageController.
 */
class PageController extends ControllerBase {

  protected $database;

  protected $entity_type_manager;

  protected $user_model;

  protected $pager;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, EntityTypeManager $entity_type_manager, UserModel $user_model, PagerManagerInterface $pager) {
    $this->database = $database;
    $this->entity_type_manager = $entity_type_manager;
    $this->user_model = $user_model;
    $this->pager = $pager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('custom_core.user_model'),
      $container->get('pager.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function topPage(Request $request) {

    $data = ['1', '2', '3'];

    return array(
      '#theme' => 'top_page',
      '#data' => $data,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function artistsPage(Request $request) {

    // Set Pager
    $items_per_page = 12;
    $page = $this->pager->findPage();
    $offset = $page * $items_per_page;
    $this->pager->createPager($this->user_model->countArtists(), $items_per_page);

    // Fetch artists
    $data = $this->user_model->fetchArtists($offset, $items_per_page);

    $render[] = [
      '#theme' => 'artists_page',
      '#data' => $data,
    ];

    $render[] = [
      '#type' => 'pager'
    ];

    return $render;
  }
}
