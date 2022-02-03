<?php

namespace Drupal\light_saml_idp;

use Drupal\Core\Session\AccountProxy;
use Drupal\light_saml_idp\Entity\ServiceProviderInterface;
use LightSaml\Model\Assertion\Assertion;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\Response as SAMLResponse;

interface SamlGeneratorInterface {

  public function buildSamlMessage(
    ServiceProviderInterface $serviceProvider,
    string $requestId = ''
  ): SAMLResponse;

  /**
   * @return \LightSaml\Model\Assertion\Issuer|null
   */
  public function getAssertionIssuer(): ?Issuer;

  public function buildAssertion(
    ServiceProviderInterface $serviceProvider,
    AccountProxy $account,
    string $requestId = ''
  ): Assertion;

  /**
   * @param \Drupal\light_saml_idp\Entity\ServiceProviderInterface $serviceProvider
   * @param \LightSaml\Model\Protocol\Response $samlMessage
   * @param \LightSaml\Model\Assertion\Assertion $assertion
   */
  public function addAssertionToResponse(
    ServiceProviderInterface $serviceProvider,
    Response $samlMessage,
    Assertion $assertion
  ): void;
}
