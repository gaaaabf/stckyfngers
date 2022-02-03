<?php

namespace Drupal\light_saml_idp\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * Provides an interface defining a Example entity.
 */
interface ServiceProviderInterface extends ConfigEntityInterface {

  /**
   * @return string|null
   */
  public function getLabel(): ?string;

  /**
   * @param string $label
   */
  public function setLabel(string $label): void;

  /**
   * @return string|null
   */
  public function getId(): ?string;

  /**
   * @param string $id
   */
  public function setId(string $id): void;

  /**
   * @return string|null
   */
  public function getEntityId(): ?string;

  /**
   * @param string $entity_id
   */
  public function setEntityId(string $entity_id): void;

  /**
   * @return string|null
   */
  public function getAcsUrl(): ?string;

  /**
   * @param string $acs_url
   */
  public function setAcsUrl(string $acs_url): void;

  /**
   * @return bool|null
   */
  public function isSigningMessage(): ?bool;

  /**
   * @param bool $signing_message
   */
  public function setSigningMessage(bool $signing_message): void;

  /**
   * @return bool|null
   */
  public function isSigningAssertion(): ?bool;

  /**
   * @param bool $signing_assertion
   */
  public function setSigningAssertion(bool $signing_assertion): void;

  /**
   * @return string|null
   */
  public function getSigningCertificate(): ?string;

  /**
   * @param string $signing_certificate
   */
  public function setSigningCertificate(string $signing_certificate): void;

  /**
   * @return string|null
   */
  public function getSigningMethod(): ?string;

  /**
   * @param string $signing_method
   */
  public function setSigningMethod(string $signing_method): void;

  /**
   * @return bool|null
   */
  public function isEncryptionAssertion(): ?bool;

  /**
   * @param bool $encryption_assertion
   */
  public function setEncryptionAssertion(bool $encryption_assertion): void;

  /**
   * @return string|null
   */
  public function getEncryptionCertificate(): ?string;

  /**
   * @param string $encryption_certificate
   */
  public function setEncryptionCertificate(string $encryption_certificate): void;

  /**
   * @return string|null
   */
  public function getEncryptionMethod(): ?string;

  /**
   * @param string $encryption_method
   */
  public function setEncryptionMethod(string $encryption_method): void;

  /**
   * @return \RobRichards\XMLSecLibs\XMLSecurityKey
   */
  public function getEncryptionPublicKey(): XMLSecurityKey;

  /**
   * @return \RobRichards\XMLSecLibs\XMLSecurityKey
   */
  public function getSigningPublicKey(): XMLSecurityKey;
}
