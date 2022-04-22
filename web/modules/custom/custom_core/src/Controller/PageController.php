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
  public function __construct(Connection $database, EntityTypeManager $entity_type_manager, UserModel $user_model, PagerService $pager) {
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
      $container->get('custom_core.pager_service'),
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
    $page = $request->query->get('page');
    $this->pager->setPage($page);
    $this->pager->setItemsPerPage(1);
    // $this->pager->setTotalDisplayPagers(5);

    $pager_results = $this->pager->getOffsetLimit();
    $data = $this->user_model->fetchArtists($pager_results['offset'], $pager_results['limit']);
    $total_data = $this->user_model->fetchTotalArtists();

    $this->pager->setTotalItemCount($total_data);
    $this->pager->setTotalPages();
    $pager_links = $this->pager->getPagerLinks();

    return array(
      '#theme' => 'artists_page',
      '#data' => $data,
      '#pagers' => $pager_links,
    );
  }  
}
