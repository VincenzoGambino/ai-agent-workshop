<?php

namespace Drupal\ai_provider_amazeeio\DTO;

/**
 * A AmazeeAI Model with information about which features are supported.
 */
final class Model {
  /**
   * The name of the model.
   *
   * @var string
   */
  public string $name;
  /**
   * Whether the model supports image input.
   *
   * @var bool
   */
  public bool $supportsImageInput;
  /**
   * Whether the model supports image output.
   *
   * @var bool
   */
  public bool $supportsImageOutput;
  /**
   * Whether the model supports audio input.
   *
   * @var bool
   */
  public bool $supportsAudioInput;
  /**
   * Whether the model supports audio output.
   *
   * @var bool
   */
  public bool $supportsAudioOutput;
  /**
   * Whether the model supports video output.
   *
   * @var bool
   */
  public bool $supportsVideoOutput;
  /**
   * Whether the model supports embeddings.
   *
   * @var bool
   */
  public bool $supportsEmbeddings;
  /**
   * Whether the model supports chat.
   *
   * @var bool
   */
  public bool $supportsChat;
  /**
   * Whether the model supports moderation.
   *
   * @var bool
   */
  public bool $supportsModeration;
  /**
   * The OpenAI compatible params supported by this model.
   *
   * @var string[]
   */
  public array $supportedOpenAiParams;

  /**
   * Whether the model supports image and audio to video.
   *
   * @var bool
   */
  public bool $supportsImageAndAudioToVideo;

  /**
   * Construct a Model object.
   *
   * @param string $name
   *   The name of the model.
   * @param bool $supportsImageInput
   *   Whether the model supports image input.
   * @param bool $supportsImageOutput
   *   Whether the model supports image output.
   * @param bool $supportsAudioInput
   *   Whether the model supports audio input.
   * @param bool $supportsAudioOutput
   *   Whether the model supports audio output.
   * @param bool $supportsVideoOutput
   *   Whether the model supports video output.
   * @param bool $supportsEmbeddings
   *   Whether the model supports embeddings.
   * @param bool $supportsChat
   *   Whether the model supports chat.
   * @param bool $supportsModeration
   *   Whether the model supports moderation.
   * @param string[] $supportedOpenAiParams
   *   The OpenAI compatible params supported by this model.
   */
  public function __construct(
    string $name,
    bool $supportsImageInput,
    bool $supportsImageOutput,
    bool $supportsAudioInput,
    bool $supportsAudioOutput,
    bool $supportsVideoOutput,
    bool $supportsEmbeddings,
    bool $supportsChat,
    bool $supportsModeration,
    array $supportedOpenAiParams,
  ) {
    $this->name = $name;
    $this->supportsImageInput = $supportsImageInput;
    $this->supportsImageOutput = $supportsImageOutput;
    $this->supportsAudioInput = $supportsAudioInput;
    $this->supportsAudioOutput = $supportsAudioOutput;
    $this->supportsVideoOutput = $supportsVideoOutput;
    $this->supportsEmbeddings = $supportsEmbeddings;
    $this->supportsChat = $supportsChat;
    $this->supportsModeration = $supportsModeration;
    $this->supportedOpenAiParams = $supportedOpenAiParams;
    $this->supportsImageAndAudioToVideo = $supportsImageInput && $this->supportsAudioInput && $this->supportsVideoOutput;
  }

  /**
   * Create a Model from an API response object.
   *
   * @param \stdClass $response
   *   The object returned by the API from model info.
   *
   * @return self
   *   A model constructed from the API response.
   */
  public static function createFromResponse(\stdClass $response): self {
    $model_info = $response->model_info;
    return new self(
      $response->model_name,
      $model_info->supports_image_input ?? FALSE,
      $model_info->supports_image_output ?? FALSE,
      $model_info->supports_audio_input ?? FALSE,
      $model_info->supports_audio_output ?? FALSE,
      $model_info->supports_video_output ?? FALSE,
      ($model_info->mode ?? NULL) === 'embedding',
      ($model_info->mode ?? NULL) === 'chat',
      $model_info->supports_moderation ?? FALSE,
      $model_info->supported_openai_params ?? [],
    );
  }

}
