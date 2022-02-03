<?php

namespace Drupal\light_saml_idp\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * Defines the ServiceProvider entity.
 *
 * @ConfigEntityType(
 *   id = "serviceProvider",
 *   label = @Translation("Service Provider"),
 *   translatable = FALSE,
 *   handlers = {
 *     "list_builder" = "Drupal\light_saml_idp\ServiceProviderListBuilder",
 *     "form" = {
 *       "add" = "Drupal\light_saml_idp\Form\ServiceProviderForm",
 *       "edit" = "Drupal\light_saml_idp\Form\ServiceProviderForm",
 *       "delete" = "Drupal\light_saml_idp\Form\ServiceProviderDeleteForm",
 *     }
 *   },
 *   config_prefix = "sp",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "editForm" = "/admin/config/people/light_saml_idp/serviceProvider/edit/{serviceProvider}",
 *     "deleteForm" = "/admin/config/people/light_saml_idp/serviceProvider/delete/{serviceProvider}",
 *   }
 * )
 */
class ServiceProvider extends ConfigEntityBase implements ServiceProviderInterface {

  /**
   * The human readable name.
   *
   * @var string|null
   */
  protected $label;

  /**
   * The machine name.
   *
   * @var string|null
   */
  protected $id;

  /**
   * An Entity Identifier in SAML terminology.
   *
   * @var string|null
   */
  protected $entityId;

  /**
   * An Assertion Consumer Service (or ACS) is SAML terminology
   * for the location at a ServiceProvider that accepts saml response messages.
   *
   * @var string|null
   */
  protected $acsUrl;

  /**
   * Whether the message should be signed.
   *
   * @var bool|null
   */
  protected $signingMessage;

  /**
   * Whether the assertion should be signed.
   *
   * @var bool|null
   */
  protected $signingAssertion;

  /**
   * The  x.509 signing certificate.
   *
   * @var string|null
   */
  protected $signingCertificate;

  /**
   * The method used for signing.
   * For example @see XMLSecurityDSig::SHA512
   *
   * @var string|null
   */
  protected $signingMethod;

  /**
   * Whether the assertion should be encrypted.
   *
   * It's an extra level of security that's enabled if the SAML assertion contains
   * particularly sensitive user information or the environment dictates the need.
   * HTTPS should always be used so SAML assertion encryption is on top of the
   * security provided at the transport layer. If there are intermediate network
   * nodes, the HTTPS traffic may be decrypted. The SAML assertion will remain
   * encrypted from IdP through to SP regardless of any intermediate network nodes.
   *
   * @var bool|null
   */
  protected $encryptionAssertion;

  /**
   * The certificate to use for encryption.
   *
   * @var string|null
   */
  protected $encryptionCertificate;

  /**
   * The method to use for encryption.
   * For example:
   * @see XMLSecurityKey::AES128_CBC
   *
   * @var string|null
   */
  protected $encryptionMethod;

  /**
   * {@inheritdoc}
   */
  public function getLabel(): ?string {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel(string $label): void {
    $this->label = $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): ?string {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setId(string $id): void {
    $this->id = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityId(): ?string {
    return $this->entityId;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityId(string $entityId): void {
    $this->entityId = $entityId;
  }

  /**
   * {@inheritdoc}
   */
  public function getAcsUrl(): ?string {
    return $this->acsUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function setAcsUrl(string $acsUrl): void {
    $this->acsUrl = $acsUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function isSigningMessage(): ?bool {
    return $this->signingMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function setSigningMessage(bool $signingMessage): void {
    $this->signingMessage = $signingMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function isSigningAssertion(): ?bool {
    return $this->signingAssertion;
  }

  /**
   * {@inheritdoc}
   */
  public function setSigningAssertion(bool $signingAssertion): void {
    $this->signingAssertion = $signingAssertion;
  }

  /**
   * {@inheritdoc}
   */
  public function getSigningCertificate(): ?string {
    return $this->signingCertificate;
  }

  /**
   * {@inheritdoc}
   */
  public function setSigningCertificate(string $signingCertificate): void {
    $this->signingCertificate = $signingCertificate;
  }

  /**
   * {@inheritdoc}
   */
  public function getSigningMethod(): ?string {
    return $this->signingMethod;
  }

  /**
   * {@inheritdoc}
   */
  public function setSigningMethod(string $signingMethod): void {
    $this->signingMethod = $signingMethod;
  }

  /**
   * {@inheritdoc}
   */
  public function isEncryptionAssertion(): ?bool {
    return $this->encryptionAssertion;
  }

  /**
   * {@inheritdoc}
   */
  public function setEncryptionAssertion(bool $encryptionAssertion): void {
    $this->encryptionAssertion = $encryptionAssertion;
  }

  /**
   * {@inheritdoc}
   */
  public function getEncryptionCertificate(): ?string {
    return $this->encryptionCertificate;
  }

  /**
   * {@inheritdoc}
   */
  public function setEncryptionCertificate(string $encryptionCertificate): void {
    $this->encryptionCertificate = $encryptionCertificate;
  }

  /**
   * {@inheritdoc}
   */
  public function getEncryptionMethod(): ?string {
    return $this->encryptionMethod;
  }

  /**
   * {@inheritdoc}
   */
  public function setEncryptionMethod(string $encryptionMethod): void {
    $this->encryptionMethod = $encryptionMethod;
  }

  /**
   * {@inheritdoc}
   */
  public function getEncryptionPublicKey(): XMLSecurityKey {
    // We need the encryption certificate from the service provider to encrypt the assertion.
    $certificate = new X509Certificate();
    $certificate->setData($this->getEncryptionCertificate());
    return KeyHelper::createPublicKey($certificate);
  }

  /**
   * {@inheritdoc}
   */
  public function getSigningPublicKey(): XMLSecurityKey {
    $certificate = new X509Certificate();
    $certificate->setData($this->getSigningCertificate());
    return KeyHelper::createPublicKey($certificate);
  }

  /**
   * @todo determine if serviceProvider is valid.
   *    Containing all required values for example.
   *    Implement this when service providers are used.
   */
  public function isValid(): bool {

    return FALSE;
  }

}
