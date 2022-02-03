<?php

namespace Drupal\light_saml_idp\Entity;

use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;

class ServiceProviderRepository extends EntityRepository {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * ServiceProviderRepository constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   * 
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, ContextRepositoryInterface $context_repository) {
    parent::__construct($entity_type_manager, $language_manager, $context_repository);
    $this->entityStorage = ($this->entityTypeManager->getStorage('serviceProvider'));
  }

  /**
   * @param string $entityId
   *
   * @return \Drupal\light_saml_idp\Entity\ServiceProviderInterface|null
   */
  public function loadEntityByEntityId(string $entityId): ?ServiceProviderInterface {
    $entities = $this->entityStorage->loadByProperties(['entityId'=> $entityId]);
    return ($entities) ? reset($entities) : NULL;
  }
}
