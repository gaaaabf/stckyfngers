<?php

namespace Drupal\light_saml_idp;

use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\ContactPerson;
use LightSaml\Model\Metadata\Organization;
use LightSaml\Model\Metadata\IdpSsoDescriptor;
use LightSaml\Model\Metadata\SingleSignOnService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
use LightSaml\Model\Context\SerializationContext;
use LightSaml\Model\Metadata;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\SamlConstants;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class IdentityProvider {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \LightSaml\Credential\X509Certificate|null
   */
  protected $signingCertificate;

  /**
   * @var \RobRichards\XMLSecLibs\XMLSecurityKey|null
   */
  protected $signingPrivateKey;

  /**
   * An Entity Identifier in SAML terminology.
   *
   * @var string|null
   */
  protected $entityId;

  /**
   * @var string|null
   */
  protected $organizationName;

  /**
   * @var string|null
   */
  protected $organizationDisplayName;

  /**
   * @var string|null
   */
  protected $organizationURL;

  /**
   * @var string|null
   */
  protected $contactSupportName;

  /**
   * @var string|null
   */
  protected $contactSupportEmail;

  /**
   * IdentityProvider constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
    $idpSettings = $this->configFactory->get('light_saml_idp.settings');
    $certificateLocation = $idpSettings->get('security.signing.cert_location');
    $this->signingCertificate = $this->loadX509Certificate($certificateLocation);

    $privateKeyLocation = $idpSettings->get('security.signing.key_location');
    $this->signingPrivateKey = $this->loadPrivateKey($privateKeyLocation);
    $this->entityId = $idpSettings->get('entity_id');

    $this->organizationName = $idpSettings->get('organization.name');
    $this->organizationDisplayName = $idpSettings->get('organization.display_name');
    $this->organizationURL = $idpSettings->get('organization.url');

    $this->contactSupportName = $idpSettings->get('contact.support.name');
    $this->contactSupportEmail = $idpSettings->get('contact.support.email');
  }

  /**
   * @return string|null
   */
  public function getContactSupportName(): ?string {
    return $this->contactSupportName;
  }

  /**
   * @return string|null
   */
  public function getContactSupportEmail(): ?string {
    return $this->contactSupportEmail;
  }

  /**
   * @param $certificateLocation
   *
   * @return \LightSaml\Credential\X509Certificate|null
   */
  protected function loadX509Certificate($certificateLocation): ?X509Certificate {
    try {
      $certificate = X509Certificate::fromFile($certificateLocation);
    } catch (\InvalidArgumentException $e) {
      return NULL;
    }
    return $certificate;
  }

  /**
   * @todo Make passphrase configurable in IDP config.
   *   Only keys without passphrase can be used till then.
   *
   * @param $privateKeyLocation
   *
   * @return \RobRichards\XMLSecLibs\XMLSecurityKey|null
   */
  protected function loadPrivateKey($privateKeyLocation): ?XMLSecurityKey {
    if (is_file($privateKeyLocation)) {
      $privateKeyContent = file_get_contents($privateKeyLocation);
      // KeyHelper::createPrivateKey doesn't check on file contents,
      // so we need to check it here.
      if ($privateKeyContent === FALSE) {
        return NULL;
      }
      return KeyHelper::createPrivateKey($privateKeyContent, '', false, XMLSecurityKey::RSA_SHA512);
    }
    return NULL;
  }

  /**
   * @return \LightSaml\Credential\X509Certificate|null
   */
  public function getSigningCertificate(): ?X509Certificate {
    return $this->signingCertificate;
  }

  /**
   * @return \RobRichards\XMLSecLibs\XMLSecurityKey|null
   */
  public function getSigningPublicKey(): ?XMLSecurityKey {
    return KeyHelper::createPublicKey($this->getSigningCertificate());
  }

  /**
   * @return \RobRichards\XMLSecLibs\XMLSecurityKey|null
   */
  public function getSigningPrivateKey(): ?XMLSecurityKey {
    return $this->signingPrivateKey;
  }

  /**
   * @return string|null
   */
  public function getEntityId(): ?string {
    return $this->entityId;
  }

  /**
   * @return string|null
   */
  public function getOrganizationName(): ?string {
    return $this->organizationName;
  }

  /**
   * @return string|null
   */
  public function getOrganizationDisplayName(): ?string {
    return $this->organizationDisplayName;
  }

  /**
   * @return string|null
   */
  public function getOrganizationURL(): ?string {
    return $this->organizationURL;
  }

  /**
   * @todo
   * NameID Encrypted
   * logoutRequestSigned
   * logoutResponseSigned
   * signatureAlgorithm
   * signMetaData
   *
   * DigestMethod
   * SigningMethod
   *
   *
   * @return \DOMDocument
   */
  public function getMetaData(): \DOMDocument {
    $entityDescriptor = new EntityDescriptor();
    $entityDescriptor->setEntityID($this->getEntityId());

    $contactPerson = new ContactPerson();
    $contactPerson->setContactType(ContactPerson::TYPE_SUPPORT);
    $contactPerson->setGivenName($this->getContactSupportName());
    $contactPerson->setEmailAddress($this->getContactSupportEmail());
    $entityDescriptor->addContactPerson($contactPerson);

    $organization = new Organization();
    $organization->setLang('en'); // @todo Make language configurable.
    $organization->setOrganizationName($this->getOrganizationName());
    $organization->setOrganizationDisplayName($this->getOrganizationDisplayName());
    $organization->setOrganizationURL($this->getOrganizationURL());
    $entityDescriptor->addOrganization($organization);

    $idpSsoDescriptor = new IdpSsoDescriptor();
    $idpSsoDescriptor->setWantAuthnRequestsSigned(TRUE);
    $idpSsoDescriptor->setValidUntil(time() + 172800); // 2 days
    $idpSsoDescriptor->addNameIDFormat(SamlConstants::NAME_ID_FORMAT_EMAIL);

    $url = Url::fromRoute('light_saml_idp.login', [], ['absolute' => TRUE]);
    $ssoService = new SingleSignOnService();
    $ssoService->setBinding(SamlConstants::BINDING_SAML2_HTTP_POST);
    $ssoService->setLocation($url->toString());
    $idpSsoDescriptor->addSingleSignOnService($ssoService);

// @todo Add logout service.
//    $url = Url::fromRoute('light_saml_idp.logout', [], ['absolute' => TRUE]);
//    $ssoService = new Metadata\SingleLogoutService();
//    $ssoService->setBinding(SamlConstants::BINDING_SAML2_HTTP_POST);
//    $ssoService->setLocation($url->toString());
//    $idpSsoDescriptor->addSingleLogoutService($ssoService);

    $signingCertificate = $this->getSigningCertificate();
    $keyDescriptor = new KeyDescriptor('signing', $signingCertificate);
    $signature = new SignatureWriter($signingCertificate, $this->getSigningPrivateKey(), XMLSecurityDSig::SHA512);
    $idpSsoDescriptor->addSignature($signature);
    $idpSsoDescriptor->addKeyDescriptor($keyDescriptor);
    $entityDescriptor->addItem($idpSsoDescriptor);


    $serializationContext = new SerializationContext();
    $document = $serializationContext->getDocument();
    $entityDescriptor->serialize($document, $serializationContext);
    return $document;
  }

}
