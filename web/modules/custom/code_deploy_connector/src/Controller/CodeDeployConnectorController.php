<?php

namespace Drupal\code_deploy_connector\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\code_deploy_connector\GitRepository;

/**
 * Class CodeDeployConnectorController.
 */
class CodeDeployConnectorController extends ControllerBase {

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Describes a logger instance
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs the Config factory.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The configuration object factory.
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->configFactory = $configFactory;
    $this->logger = $this->getLogger('code_deploy_connector');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Will trigger the code deploy using git. PHP will run git commands using exec
   * 
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request represents an HTTP request.
   * @param string $command
   *   The paramater to determine the webhook URI
   */
  public function triggerDeploy(Request $request, $command) {
    $config = $this->configFactory->get('code_deploy_connector.codedeployconfig');

    // $git = new GitRepository(DRUPAL_ROOT);
    $trigger_uri = $config->get('webhook_trigger');

    // If trigger command is correct
    if ($trigger_uri == $command && $request->getMethod() == 'POST') {
      if (strpos($request->getContent(), '"ref":"refs/tags/') !== FALSE && strpos($request->getContent(), '"projectKey":"MITU_DEV"') !== FALSE) {
        exec('sudo git fetch --all latestTag=$(git describe --tags `git rev-list --tags --max-count=1`) git checkout $latestTag', $output);
        $this->logger->notice("Code Deploy Detected \n" . $request->getContent());
      }
    } else {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();  
    }
  }

  /**
   * Will trigger the code deploy using git. PHP will run git commands using exec
   * 
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request represents an HTTP request.
   * @param string $command
   *   The paramater to determine the webhook URI
   */
  public function triggerHook(Request $request, $command) {
    $config = $this->configFactory->get('code_deploy_connector.codedeployconfig');
    $this->logger->notice("Web Hook is about to run\n" . $request->getContent());

    // Fetch all registered webhooks
    $webhooks = $config->get('webhooks');
    unset($webhooks['action']);

    // Detect if webhook was triggered
    foreach ($webhooks as $key => $webhook) {
      // Execute command after webhook was triggered
      if ($webhook['url_trigger'] === $command && $webhook['status'] && $request->getMethod() == $webhook['method']) {

        if ($webhook['auth'] == TRUE && in_array('administrator', \Drupal::currentUser()->getRoles()) == FALSE) {
          throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        $output = null;
        $retval = null;
        $output = shell_exec($webhook['command'] . ' 2>&1');
        $this->logger->notice("Web Hook Triggered \n" . $request->getContent() . "\n Results: " . $output);
        return [
          '#markup' => '<p>"'.$webhook['command'].'" was executed. <br>Results:<br>'.$retval.' '.$output.'</p>',
        ];
      }
    }

    throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();  
  }

  /**
   * Display the current git log
   * 
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request represents an HTTP request.
   * @return array $render
   *   The rendered markup for the log results
   */
  public function checkLog(Request $request) {
    try {
      $repo = new GitRepository(__DIR__);
      $total = count($repo->getAllLogs());
      $num_per_page = 80;
      // Initialize pager and gets current page.
      $pager = \Drupal::service('pager.manager')->createPager($total, $num_per_page);
      $chunks = array_chunk($repo->getAllLogs(), $num_per_page);

      // Get the items for our current page.
      $current_page_items = $chunks[$pager->getCurrentPage()];
      $render = [];
      $render[] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => $this->t("GIT Logs"),
        '#items' => $current_page_items,
        '#attributes' => [
          'class' => [
            'drupal-git',
            'drupal-git-all-logs',
          ],
        ],
        '#wrapper_attributes' => [
          'class' => 'container',
        ],
      ];
      $render['#attached'] = [
        'library' => 'code_deploy_connector/log_css',
      ];
      $render[] = ['#type' => 'pager'];
    }
    catch (GitException $ex) {
      \Drupal::messenger()->addError($ex->getMessage());
      $render[] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => $this->t("GIT Logs"),
        '#items' => [t("No logs found")],
        '#attributes' => [
          'class' => [
            'drupal-git',
            'drupal-git-no-data',
          ],
        ],
        '#wrapper_attributes' => [
          'class' => 'container',
        ],
      ];
    }
    return $render;
  }
}
