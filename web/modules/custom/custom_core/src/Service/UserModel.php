<?php

namespace Drupal\custom_core\Service;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

require_once DRUPAL_ROOT . '/modules/custom/custom_core/src/consts.php';

class UserModel {

  protected $database;

  protected $entity_type_manager;

  protected $entity_type_manager_user;

  protected $user_entity = 'user';

  protected $user_table = 'users_field_data';

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, EntityTypeManager $entity_type_manager) {
    $this->database = $database;
    $this->entity_type_manager = $entity_type_manager;
    $this->setUserEntityManager();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUserEntityManager() {
    $this->entity_type_manager_user = $this->entity_type_manager->getStorage($this->user_entity);
  }

  /**
   * Fetches all user entity objects with 'Artist' as Role
   */
  public function fetchArtists($start = 0, $limit = 10) {
    $results = NULL;
    $uids = NULL;

    $query = $this->entity_type_manager_user->getQuery();
    $query->condition('roles', ARTIST_ROLE);
    $query->range($start, $limit);

    $uids = $query->execute();

    if (!empty($uids)) {
      $results = $this->entity_type_manager_user->loadMultiple($uids);
    }

    return $results;
  }

  /**
   * Fetches all user entity objects with 'Artist' as Role
   */
  public function fetchTotalArtists() {
    $results = NULL;
    $uids = NULL;

    $query = $this->entity_type_manager_user->getQuery();
    $query->condition('roles', ARTIST_ROLE);
    $uids = $query->execute();

    if (!empty($uids)) {
      $results = count($uids);
    }

    return $results;
  }
}