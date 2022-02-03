<?php
namespace Drupal\light_saml_idp\lightsaml\model\metadata;

use LightSaml\Model\Context\SerializationContext;
use LightSaml\Model\Metadata\KeyDescriptor as BaseKeyDescriptor;
use LightSaml\SamlConstants;

class KeyDescriptor extends BaseKeyDescriptor {

  public function serialize(\DOMNode $parent, SerializationContext $context) {
    $result = $this->createElement('KeyDescriptor', SamlConstants::NS_METADATA, $parent, $context);

    $this->attributesToXml(array('use'), $result);

    $keyInfo = $this->createElement('ds:KeyInfo', SamlConstants::NS_XMLDSIG, $result, $context);
    $xData = $this->createElement('ds:X509Data', SamlConstants::NS_XMLDSIG, $keyInfo, $context);
    $xCert = $this->createElement('ds:X509Certificate', SamlConstants::NS_XMLDSIG, $xData, $context);

    $xCert->nodeValue = chunk_split($this->getCertificate()->getData(), 64, "\n");
  }

}
