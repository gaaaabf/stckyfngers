<?php

namespace Drupal\light_saml_idp;

use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Protocol\AuthnRequest;

class SamlParser {

  /**
   * @param string $samlRequest
   *
   * @return \LightSaml\Model\Protocol\AuthnRequest
   */
  public function buildAuthnRequest(string $samlRequest): AuthnRequest {
    $xml = base64_decode($samlRequest);

    $deserializationContext = new DeserializationContext();
    $deserializationContext->getDocument()->loadXML($xml);

    $authnRequest = new AuthnRequest();
    $authnRequest->deserialize($deserializationContext->getDocument()->firstChild, $deserializationContext);
    return $authnRequest;
  }

  /**
   * @param $authnRequest
   *
   * @return string|null
   */
  public function extractEntityId(AuthnRequest $authnRequest): ?string {
    if ($issuer = $authnRequest->getIssuer()) {
      if ($issuer instanceof Issuer) {
        return $issuer->getValue();
      }
    }
    return NULL;
  }

}
