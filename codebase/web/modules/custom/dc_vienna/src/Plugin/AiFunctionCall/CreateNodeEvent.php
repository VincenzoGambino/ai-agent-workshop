<?php

namespace Drupal\dc_vienna\Plugin\AiFunctionCall;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\ai\Attribute\FunctionCall;
use Drupal\ai\Base\FunctionCallBase;
use Drupal\ai\Service\FunctionCalling\ExecutableFunctionCallInterface;
use Drupal\ai\Service\FunctionCalling\FunctionCallInterface;
use Drupal\ai_agents\PluginInterfaces\AiAgentContextInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the describe config function.
 */
#[FunctionCall(
  id: 'ai_agent:create_node_event',
  function_name: 'ai_agent_create_node_event',
  name: 'Create Node Event',
  description: 'Create node events from a prompt.',
  group: 'modification_tools',
  context_definitions: [
    'title' => new ContextDefinition(
      data_type: 'string',
      label: 'Event title',
      description: 'The title of the event.',
      required: TRUE,
    ),
    'description' => new ContextDefinition(
      data_type: 'string',
      label: 'Description',
      description: 'The event description.',
      required: TRUE,
    ),
    'start_date' => new ContextDefinition(
      data_type: 'string',
      label: 'Start date',
      description: 'The event start date.',
      required: TRUE,
    ),
    'end_date' => new ContextDefinition(
      data_type: 'string',
      label: 'End Date',
      description: 'The event end date.',
      required: TRUE,
    ),
    'tid' => new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup("Taxonomy ID"),
      description: new TranslatableMarkup("The term to list."),
      required: FALSE,
    ),
  ],
)]
class CreateNodeEvent extends FunctionCallBase implements ExecutableFunctionCallInterface, AiAgentContextInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

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
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * The result string.
   *
   * @var string
   */
  protected string $response;

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // Check for permission to administer site configuration.
    if (!$this->currentUser->hasPermission('create events content')) {
      $this->response = $this->t('You do not have permission to create events.');
      return;
    }

    // Collect the context values.
    $title = $this->getContextValue('title');
    $description = $this->getContextValue('description');
    $start_date = $this->getContextValue('start_date');
    $end_date = $this->getContextValue('end_date');
    $tid = $this->getContextValue('tid');

    // Validate config name and value.
    if (empty($title) || empty($description) || empty($start_date) || empty($end_date) || empty($tid)) {
      $this->response = $this->t('Missing data');
      return;
    }

    $event = $this->entityTypeManager->getStorage('node')->create([
      'title' => $title,
      'type' => 'events',

    ]);
    $event->set('field_description', $description);
    $event->set('field_start_date', $start_date);
    $event->set('field_end_date', $end_date);
    $event->set('field_category', ['target_id' => $tid]);

    try {
      $event->save();
    }
    catch (\Exception $e) {
      $this->response = $e->getMessage();
    }

    // Set a success response.
    $this->response = $this->t(
      'The event @title event on @start_date to @end_date has been created.',[
        '@title' => $title, '@start_date' => $start_date, '@end_date' => $end_date
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getReadableOutput(): string {
    return $this->response;
  }

}
