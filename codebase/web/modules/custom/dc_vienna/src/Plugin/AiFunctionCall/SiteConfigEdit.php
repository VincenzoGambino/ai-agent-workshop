<?php

namespace Drupal\dc_vienna\Plugin\AiFunctionCall;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\ai\Attribute\FunctionCall;
use Drupal\ai\Base\FunctionCallBase;
use Drupal\ai\Service\FunctionCalling\ExecutableFunctionCallInterface;
use Drupal\ai\Service\FunctionCalling\FunctionCallInterface;
use Drupal\ai\Utility\ContextDefinitionNormalizer;
use Drupal\ai_agents\PluginInterfaces\AiAgentContextInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the describe config function.
 */
#[FunctionCall(
  id: 'ai_agent:site_config_edit',
  function_name: 'ai_agent_site_config_edit',
  name: 'Site Config Edit',
  description: 'Edits the site configuration.',
  group: 'modification_tools',
  context_definitions: [
    'config_name' => new ContextDefinition(
      data_type: 'string',
      label: 'Configuration name',
      description: 'The configuration name.',
      required: TRUE,
    ),
    'config_value' => new ContextDefinition(
      data_type: 'string',
      label: 'Configuration value',
      description: 'The configuration value.',
      required: TRUE,
    ),
  ],
)]
class SiteConfigEdit extends FunctionCallBase implements ExecutableFunctionCallInterface, AiAgentContextInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The config typed data manager.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * Load from dependency injection container.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): FunctionCallInterface|static {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ai.context_definition_normalizer'),
    );
    $instance->configFactory = $container->get('config.factory');
    $instance->currentUser = $container->get('current_user');
    $instance->loggerFactory = $container->get('logger.factory');
    return $instance;
  }

  /**
   * The result string.
   *
   * @var array
   */
  protected string $response;

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // Check for permission to administer site configuration.
    if (!$this->currentUser->hasPermission('administer site configuration')) {
      $this->response = $this->t('You do not have permission to update site configuration.');
      return;
    }

    // Collect the context values.
    $config_name = $this->getContextValue('config_name');
    $config_value = $this->getContextValue('config_value');

    // Validate config name and value.
    if (empty($config_name) || $config_value === NULL) {
      $this->response = $this->t('Configuration name or value is missing.');
      return;
    }

    try {
      // Update the site configuration.
      $this->configFactory->getEditable('system.site')
        ->set($config_name, $config_value)
        ->save();

      // Set a success response.
      $this->response = $this->t(
        'The site configuration "@config_name" has been updated with the value "@config_value" successfully.',
        ['@config_name' => $config_name, '@config_value' => $config_value]
      );
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('dc_vienna')->error('Failed to update site config: @message', ['@message' => $e->getMessage()]);
      $this->response = $this->t('An error occurred while updating the site configuration. Please check the logs for details.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getReadableOutput(): string {
    return $this->response;
  }

}
