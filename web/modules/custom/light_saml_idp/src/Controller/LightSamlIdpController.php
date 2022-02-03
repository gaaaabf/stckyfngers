<?php

namespace Drupal\light_saml_idp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\light_saml_idp\Entity\ServiceProvider;
use Drupal\light_saml_idp\Entity\ServiceProviderRepository;
use Drupal\light_saml_idp\IdentityProvider;
use Drupal\light_saml_idp\SamlGeneratorInterface;
use Drupal\light_saml_idp\SamlParser;
use LightSaml\Error\LightSamlSecurityException;
use LightSaml\SamlConstants;
use LightSaml\Validator\Model\Xsd\XsdValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class LightSamlIdpController
 *
 * Provides controllers for login and logout via SAML requests.
 *
 * @package Drupal\light_saml_idp\Controller
 */
class LightSamlIdpController extends ControllerBase {

  /**
   * A SamlGenerator instance.
   *
   * @var \Drupal\light_saml_idp\SamlGenerator
   */
  protected $samlGenerator;

  /**
   * A SamlParser instance.
   *
   * @var \Drupal\light_saml_idp\SamlParser
   */
  protected $samlParser;

  /**
   * @var \LightSaml\Validator\Model\Xsd\XsdValidator
   */
  protected $xsdValidator;

  /**
   * Service provider repository.
   *
   * @var \Drupal\light_saml_idp\Entity\ServiceProviderRepository
   */
  protected $serviceProviderRepository;

  /**
   * A IdentityProvider instance.
   *
   * @var \Drupal\light_saml_idp\IdentityProvider
   */
  protected $identityProvider;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * LightSamlIdpController constructor.
   *
   * @param \Drupal\light_saml_idp\SamlGeneratorInterface $samlGenerator
   * @param \Drupal\light_saml_idp\SamlParser $samlParser
   * @param \LightSaml\Validator\Model\Xsd\XsdValidator $xsdValidator
   * @param \Drupal\light_saml_idp\Entity\ServiceProviderRepository $serviceProviderRepository
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   * @param \Drupal\light_saml_idp\IdentityProvider $identityProvider
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  public function __construct(
    SamlGeneratorInterface $samlGenerator,
    SamlParser $samlParser,
    XsdValidator $xsdValidator,
    ServiceProviderRepository $serviceProviderRepository,
    AccountProxy $currentUser,
    IdentityProvider $identityProvider,
    MessengerInterface $messenger
  ) {
    $this->samlGenerator = $samlGenerator;
    $this->samlParser = $samlParser;
    $this->xsdValidator = $xsdValidator;
    $this->serviceProviderRepository = $serviceProviderRepository;
    $this->currentUser = $currentUser;
    $this->identityProvider = $identityProvider;
    $this->messenger = $messenger;
  }

  /**
   * Display the IDP Metadata as formatted XML.
   *
   * @return array
   */
  public function adminMetaData() {
    $build = ['#theme' => 'metadata'];
    if ($xml = $this->identityProvider->getMetaData()) {
      $xml->formatOutput = true;
      $xml->preserveWhiteSpace = false;
      $build['#attached']['library'][] = 'light_saml_idp/prettify';

      $xmlContent = $xml->saveXML();
      $errors = $this->xsdValidator->validateMetadata($xmlContent);
      foreach ($errors as $error) {
        $this->messenger->addError((string) $error);
      }

      $build['#xml_content'] = $xmlContent;
    }

    return $build;
  }

  /**
   * Display the IDP Metadata as raw XML.
   */
  public function metaData() {
    $xml = $this->identityProvider->getMetaData();
    if (!$xml) {
      throw new NotFoundHttpException();
    }

    $xmlContent = $xml->saveXML();
    $errors = $this->xsdValidator->validateMetadata($xmlContent);
    if (!empty($errors)) {
      throw new HttpException(500);
    }

    return new Response($xmlContent, Response::HTTP_OK, [
      'Content-type' => 'application/xml; charset=utf-8',
      'X-Robots-Tag' => 'noindex',
    ]);
  }

  /**
   * Logs in a user by parsing a AuthnRequest from a ServiceProvider.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function login(Request $request) {
    $session = $request->getSession();
    if ($samlRequest = $request->request->get('SAMLRequest', $session->get('light_saml_idp_saml_request'))) {
      $relayState = $request->request->get('RelayState', $session->get('light_saml_idp_relaystate'));
      $session->remove('light_saml_idp_saml_request');
      $session->remove('light_saml_idp_relaystate');

      $authnRequest = $this->samlParser->buildAuthnRequest($samlRequest);
      $signatureReader = $authnRequest->getSignature();
      $requestId = $authnRequest->getID();

      if ($entityId = $this->samlParser->extractEntityId($authnRequest)) {
        if ($serviceProvider = $this->serviceProviderRepository->loadEntityByEntityId($entityId)) {
          $key = $serviceProvider->getSigningPublicKey();
          try {
            $valid = $signatureReader->validate($key);
          }
          catch (LightSamlSecurityException $exception) {
            $valid = FALSE;
          }
          if ($valid) {
            return $this->getSamlHttpPostResponse($serviceProvider, $requestId, $relayState);
          }
        }
      }
    }

    return new Response('', Response::HTTP_FORBIDDEN);
  }

  /**
   * @param $serviceProvider
   * @param string $requestId
   *
   * @param null $relayState
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  protected function getSamlHttpPostResponse($serviceProvider, $requestId = '', $relayState = NULL): Response {
    $samlMessage = $this->samlGenerator->buildSamlMessage($serviceProvider, $requestId);
    if ($relayState) {
      $samlMessage->setRelayState($relayState);
    }
    $assertion = $this->samlGenerator->buildAssertion($serviceProvider, $this->currentUser, $requestId);
    $this->samlGenerator->addAssertionToResponse($serviceProvider, $samlMessage, $assertion);
    $bindingFactory = new \LightSaml\Binding\BindingFactory();

    $messageContext = new \LightSaml\Context\Profile\MessageContext();
    $messageContext->setMessage($samlMessage)->asResponse();

    // We only support SAML 2.0 HTTP POST binding for now.
    // The service provider(s) will need to comply.
    $postBinding = $bindingFactory->create(SamlConstants::BINDING_SAML2_HTTP_POST);

    return $postBinding->send($messageContext);
  }

  public function initiateLoginOnSP(ServiceProvider $serviceProvider) {
    return $this->getSamlHttpPostResponse($serviceProvider);
  }

  public function logout() {

  }
}
