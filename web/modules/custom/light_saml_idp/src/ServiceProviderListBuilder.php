<?php

namespace Drupal\light_saml_idp;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

class ServiceProviderListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['id'] = $entity->id();

    if (!empty($entity->id())) {
      return $row + parent::buildRow($entity);
    }
    return $row;
  }

  /**
   * Overridden for consistent camelCasing.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return array
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    if ($entity->access('update') && $entity->hasLinkTemplate('editForm')) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $this->ensureDestination($entity->toUrl('editForm')),
      ];
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate('deleteForm')) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => $this->ensureDestination($entity->toUrl('deleteForm')),
      ];
    }

    // Translation operation is irrelevant for this ConfigEntity but is always
    // added anyways. @see config_translation_entity_operation.
    if (isset($operations['translate'])) {
      unset($operations['translate']);
    }

    return $operations;
  }

}