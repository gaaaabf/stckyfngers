<?php

namespace Drupal\light_saml_idp;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\light_saml_idp\Entity\ServiceProviderInterface;
use LightSaml\ClaimTypes;
use LightSaml\Helper as SAMLHelper;
use LightSaml\Model\Assertion\Assertion;
use LightSaml\Model\Assertion\Attribute;
use LightSaml\Model\Assertion\AttributeStatement;
use LightSaml\Model\Assertion\AudienceRestriction;
use LightSaml\Model\Assertion\AuthnContext;
use LightSaml\Model\Assertion\AuthnStatement;
use LightSaml\Model\Assertion\Conditions;
use LightSaml\Model\Assertion\EncryptedAssertionWriter;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Assertion\Subject;
use LightSaml\Model\Assertion\SubjectConfirmation;
use LightSaml\Model\Assertion\SubjectConfirmationData;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\Response as SAMLResponse;
use LightSaml\Model\Protocol\Status;
use LightSaml\Model\Protocol\StatusCode;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\SamlConstants;
use Psr\Log\LoggerInterface;

class SamlGenerator implements SamlGeneratorInterface {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * A IdentityProvider instance.
   *
   * @var \Drupal\light_saml_idp\IdentityProvider
   */
  protected $identityProvider;

  /**
   * A time instance.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  public function __construct(IdentityProvider $identityProvider, LoggerInterface $logger, TimeInterface $time, ConfigFactoryInterface $configFactory) {
    $this->identityProvider = $identityProvider;
    $this->logger = $logger;
    $this->time = $time;
    $this->configFactory = $configFactory;
  }

  public function buildSamlMessage(
    ServiceProviderInterface $serviceProvider,
    string $requestId = ''
  ): SAMLResponse {
    $statusCode = new StatusCode('urn:oasis:names:tc:SAML:2.0:status:Success');
    $status = new Status($statusCode);

    $response = new SAMLResponse();
    if ($requestId) {
      $response->setInResponseTo($requestId);
    }
    $response->setStatus($status);
    $response->setID(SAMLHelper::generateID());
    $response->setIssueInstant($this->time->getCurrentTime());
    $response->setDestination($serviceProvider->getAcsUrl());
    $response->setIssuer($this->getAssertionIssuer());
    if ($serviceProvider->isSigningMessage()) {
      $signature = $this->getSignature($serviceProvider);
      $response->setSignature($signature);
    }

    return $response;
  }

  /**
   * @return \LightSaml\Model\Assertion\Issuer|null
   */
  public function getAssertionIssuer(): ?Issuer {
    if ($entityId = $this->identityProvider->getEntityId()) {
      return new Issuer($entityId);
    }
    return NULL;
  }

  /**
   * @param \Drupal\light_saml_idp\Entity\ServiceProviderInterface $serviceProvider
   *
   * @return \LightSaml\Model\XmlDSig\SignatureWriter
   */
  protected function getSignature(ServiceProviderInterface $serviceProvider): SignatureWriter {
    return new SignatureWriter(
      $this->identityProvider->getSigningCertificate(),
      $this->identityProvider->getSigningPrivateKey(),
      $serviceProvider->getSigningMethod()
    );
  }

  public function buildAssertion(
    ServiceProviderInterface $serviceProvider,
    AccountProxy $account,
    string $requestId = ''
  ): Assertion {
    // Create an assertion for authorizing the user on the Service Provider.
    $assertion = new Assertion();
    $assertion->setId(SAMLHelper::generateID());
    $assertion->setIssueInstant($this->time->getCurrentTime());
    $assertion->setIssuer($this->getAssertionIssuer());

    // Assertion Subject
    $subject = new Subject();
    $subjectConfirmation = new SubjectConfirmation();

    $nameId = new NameID($account->getEmail(), SamlConstants::NAME_ID_FORMAT_EMAIL);
    $subject->setNameID($nameId);
    $subjectConfirmation->setMethod(SamlConstants::CONFIRMATION_METHOD_BEARER);
    $subjectConfirmationData = new SubjectConfirmationData();
    if ($requestId) {
      $subjectConfirmationData->setInResponseTo($requestId);
    }
    $subjectConfirmationData->setNotOnOrAfter($this->time->getCurrentTime() + 60);
    $subjectConfirmationData->setRecipient($serviceProvider->getAcsUrl());
    $subjectConfirmation->setSubjectConfirmationData($subjectConfirmationData);
    $subject->addSubjectConfirmation($subjectConfirmation);
    $assertion->setSubject($subject);

    // Assertion Conditions
    $conditions = new Conditions();
    $conditions->setNotBefore($this->time->getCurrentTime());
    $conditions->setNotOnOrAfter($this->time->getCurrentTime() + 60);
    $audienceRestriction = new AudienceRestriction([$serviceProvider->getEntityId()]);
    $conditions->addItem($audienceRestriction);
    $assertion->setConditions($conditions);

    // Assertion AttributeStatement
    $attributeStatement = new AttributeStatement();

    $emailAttribute = new Attribute(ClaimTypes::EMAIL_ADDRESS, $account->getEmail());
    $emailAttribute->setFriendlyName('email_address');
    $attributeStatement->addAttribute($emailAttribute);

    $commonNameAttribute = new Attribute(ClaimTypes::COMMON_NAME, $account->getDisplayName());
    $commonNameAttribute->setFriendlyName('common_name');
    $attributeStatement->addAttribute($commonNameAttribute);
    $assertion->addItem($attributeStatement);

    // Assertion authnStatement
    $authnStatement = new AuthnStatement();
    $authnStatement->setAuthnInstant($this->time->getCurrentTime());

    $authnStatement->setAuthnContext($this->getAssertionAuthnContext());
    $assertion->addItem($authnStatement);

    if ($serviceProvider->isSigningAssertion()) {
      $signature = $this->getSignature($serviceProvider);
      $assertion->setSignature($signature);
    }
    return $assertion;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAssertionAuthnContext(): AuthnContext {
    $authnContext = new AuthnContext();
    $authnContext->setAuthnContextClassRef(SamlConstants::AUTHN_CONTEXT_PASSWORD_PROTECTED_TRANSPORT);
    return $authnContext;
  }

  /**
   * @param \Drupal\light_saml_idp\Entity\ServiceProviderInterface $serviceProvider
   * @param \LightSaml\Model\Protocol\Response $samlMessage
   * @param \LightSaml\Model\Assertion\Assertion $assertion
   */
  public function addAssertionToResponse(
    ServiceProviderInterface $serviceProvider,
    Response $samlMessage,
    Assertion $assertion
  ): void {
    if ($serviceProvider->isEncryptionAssertion()) {
      $encryptedAssertion = new EncryptedAssertionWriter();
      $encryptedAssertion->encrypt($assertion, $serviceProvider->getEncryptionPublicKey());
      $samlMessage->addEncryptedAssertion($encryptedAssertion);
    }
    else {
      $samlMessage->addAssertion($assertion);
    }
  }
}
