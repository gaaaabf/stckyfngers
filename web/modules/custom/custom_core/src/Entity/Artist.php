<?php

namespace Drupal\custom_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the artist entity.
 *
 * @ContentEntityType(
 *   id = "artist",
 *   label = @Translation("Artist"),
 *   base_table = "artist",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *     "uid" = "uid",
 *   },
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   links = {
 *     "canonical" = "/artist/{artist}",
 *     "add-page" = "/artist/add",
 *     "add-form" = "/artist/add/{artist_type}",
 *     "edit-form" = "/artist/{artist}/edit",
 *     "delete-form" = "/artist/{artist}/delete",
 *     "collection" = "/admin/content/artists",
 *   },
 *   admin_permission = "administer site configuration",
 * )
 */
class Artist extends ContentEntityBase implements ContentEntityInterface, EntityOwnerInterface {

  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['uid']
      ->setLabel(t('Author'))
      ->setDescription(t('The product author.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The product title.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_reference'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Reference'))
      ->setDescription(t('The default variation.'))
      ->setSetting('target_type', 'user')
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', FALSE);

    return $fields;
  }
}