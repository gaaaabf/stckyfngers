<?php

namespace Drupal\custom_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\custom_core\Service\UserModel;
use Drupal\custom_core\Service\PagerService;
use Drupal\Core\Pager\PagerManagerInterface;

/**
 * Class PageController.
 */
class PageController extends ControllerBase {

  protected $database;

  protected $entity_type_manager;

  protected $user_model;

  protected $paginator;

  protected $pager;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, EntityTypeManager $entity_type_manager, UserModel $user_model, PagerService $pager, PagerManagerInterface $paginator) {
    $this->database = $database;
    $this->entity_type_manager = $entity_type_manager;
    $this->user_model = $user_model;
    $this->pager = $pager;
    $this->paginator = $paginator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('custom_core.user_model'),
      $container->get('custom_core.pager_service'),
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

    // Get Page
    $page = $request->query->get('page');

    // Set pager configurations
    $this->pager->setPage($page);
    $this->pager->setItemsPerPage(12);

    // Get offset and limit to query data
    $offset_limit = $this->pager->getOffsetLimit();

    // Fetch artists
    $data = $this->user_model->fetchArtists($offset_limit['offset'], $offset_limit['limit']);

    // Fetch pagination to be rendered
    $this->pager->setTotalPages($this->user_model->fetchTotalArtists());
    $pager_links = $this->pager->getPagerLinks();

    $this->paginator->createPager(100, 10);

    $render[] = [
      '#theme' => 'artists_page',
      '#data' => $data,
      '#pagers' => $pager_links,
    ];

    $render[] = [
      '#type' => 'pager'
    ];

    return $render;
  }
}
