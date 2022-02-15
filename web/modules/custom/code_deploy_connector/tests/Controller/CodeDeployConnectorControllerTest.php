<?php

namespace Drupal\code_deploy_connector\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the code_deploy_connector module.
 */
class CodeDeployConnectorControllerTest extends WebTestBase {


  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "code_deploy_connector CodeDeployConnectorController's controller functionality",
      'description' => 'Test Unit for module code_deploy_connector and controller CodeDeployConnectorController.',
      'group' => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests code_deploy_connector functionality.
   */
  public function testCodeDeployConnectorController() {
    // Check that the basic functions of module code_deploy_connector.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
