<?php

namespace Drupal\custom_core\Service;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;

class UserModel {

  protected $database;

  protected $entity_type_manager;

  protected $user_table = 'users_field_data';

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, EntityTypeManager $entity_type_manager) {
    $this->database = $database;
    $this->entity_type_manager = $entity_type_manager;
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
  public function fetchArtists() {
    $this->database->select($this->user_table, 'user');
    $this->database->addField('user', '');

    \Kint::dump($this->database);
  }
}