<?php

namespace Drupal\ai_provider_amazeeio\Plugin\AiProvider;

use Drupal\ai_provider_amazeeio\AmazeeIoApi\AmazeeClient;
use Drupal\ai\Attribute\AiProvider;
use Drupal\ai\Base\OpenAiBasedProviderClientBase;
use Drupal\ai\Exception\AiSetupFailureException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'amazee.ai AI' provider.
 */
#[AiProvider(
  id: 'amazeeio',
  label: new TranslatableMarkup('amazee.ai AI'),
)]
class AmazeeioAiProvider extends OpenAiBasedProviderClientBase {

  /**
   * The AmazeeAI API client.
   *
   * @var \Drupal\ai_provider_amazeeio\AmazeeIoApi\AmazeeClient
   */
  protected AmazeeClient $amazeeClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  protected function loadClient(): void {
    try {
      $authToken = $this->loadApiKey();

      $this->amazeeClient = new AmazeeClient(
        $this->httpClient,
        $this->loggerFactory,
      );
      $this->amazeeClient->setToken($authToken);

      if (!$this->apiKey) {
        $this->setAuthentication($authToken);
      }
      $host = $this->amazeeClient->getHost();
      $this->setEndpoint($host);
      $this->client = $this->createClient();
    }
    catch (AiSetupFailureException $e) {
      throw new AiSetupFailureException('Failed to initialize amazee.ai client: ' . $e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguredModels(?string $operation_type = NULL, array $capabilities = []): array {
    $this->loadClient();
    return $this->getModels($operation_type ?? '', $capabilities);
  }

  /**
   * Retrieves and filters a list of models from the AmazeeAI client.
   *
   * Filters out deprecated or unsupported models based on the operation type.
   * The AmazeeAI API does not natively filter these models.
   *
   * @param string $operation_type
   *   The bundle to filter models by.
   * @param array $capabilities
   *   The capabilities to filter models by.
   *
   * @return array
   *   A filtered list of public models.
   */
  public function getModels(string $operation_type, array $capabilities): array {
    $models = [];
    foreach ($this->amazeeClient->models() as $model) {
      switch ($operation_type) {
        case 'text_to_image':
          if ($model->supportsImageOutput) {
            $models[$model->name] = $model->name;
          }
          break;

        case 'text_to_speech':
          if ($model->supportsAudioOutput) {
            $models[$model->name] = $model->name;
          }
          break;

        case 'audio_to_audio':
          if ($model->supportsAudioInput && $model->supportsAudioOutput) {
            $models[$model->name] = $model->name;
          }
          break;

        case 'moderation':
          if ($model->supportsModeration) {
            $models[$model->name] = $model->name;
          }
          break;

        case 'embeddings':
          if ($model->supportsEmbeddings) {
            $models[$model->name] = $model->name;
          }
          break;

        case 'chat':
          if ($model->supportsChat) {
            $models[$model->name] = $model->name;
          }
          break;

        case 'image_and_audio_to_video':
          if ($model->supportsImageAndAudioToVideo) {
            $models[$model->name] = $model->name;
          }
          break;

        default:
          break;
      }
    }
    return $models;
  }

  /**
   * {@inheritdoc}
   */
  public function getModelSettings(string $model_id, array $generalConfig = []): array {
    $this->loadClient();
    $model_info = $this->amazeeClient->models()[$model_id] ?? NULL;

    if (!$model_info) {
      return $generalConfig;
    }

    foreach (array_keys($generalConfig) as $name) {
      if (!in_array($name, $model_info->supportedOpenAiParams)) {
        unset($generalConfig[$name]);
      }
    }

    return $generalConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedOperationTypes(): array {
    return [
      'chat',
      'chat_with_complex_json',
      'chat_with_image_vision',
      'chat_with_structured_response',
      'chat_with_tools',
      'embeddings',
    ];
  }

}
