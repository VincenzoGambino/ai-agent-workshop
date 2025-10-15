<?php

namespace Drupal\ai_provider_amazeeio\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\AiVdbProviderPluginManager;
use Drupal\ai_provider_amazeeio\AmazeeIoApi\ClientInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\key\KeyRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure amazee.ai AI API access Form.
 */
class AmazeeioAiConfigForm extends ConfigFormBase {

  /**
   * Config settings.
   */
  const CONFIG_NAME = 'ai_provider_amazeeio.settings';

  /**
   * The known key name for the amazee.ai API key.
   */
  const API_KEY_NAME = 'amazeeio_ai';

  /**
   * The known key name for the amazee.ai database password.
   */
  const VDB_PASSWORD_NAME = 'amazeeio_ai_database';

  /**
   * The default Postgres port.
   */
  const POSTGRES_PORT_DEFAULT = 5432;

  /**
   * Not connected to amazee.ai.
   */
  const STATE_DISCONNECTED = 'disconnected';

  /**
   * Email address has been entered, waiting for  verification code.
   */
  const STATE_VERIFICATION = 'validation';

  /**
   * Email verification successful, region selection.
   */
  const STATE_VERIFIED = 'validated';

  /**
   * Region has been selected, keys are generated, everything is set up.
   */
  const STATE_CONNECTED = 'connected';

  /**
   * Show a confirmation step before disconnecting.
   */
  const STATE_CONFIRM_DISCONNECT = 'confirm_disconnect';

  /**
   * Constructs a new AmazeeioAiConfigForm object.
   */
  public function __construct(
    protected AiProviderPluginManager $aiProviderManager,
    protected KeyRepositoryInterface $keyRepository,
    protected Client $client,
    protected ClientInterface $amazeeClient,
    protected PrivateTempStoreFactory $tempStoreFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AiVdbProviderPluginManager $vdbProviderPluginManager,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
    $config = $this->config(static::CONFIG_NAME);
    $this->amazeeClient->setHost($config->get('amazee_host') ?? '');
    $this->amazeeClient->setToken($this->getTempStore()->get('access_token') ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ai.provider'),
      $container->get('key.repository'),
      $container->get('http_client'),
      $container->get('ai_provider_amazeeio.api_client'),
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('ai.vdb_provider'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'amazeeio_ai_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [static::CONFIG_NAME];
  }

  /**
   * Determine the current form state.
   *
   * Based on the current `$form_state` as well as the authorization
   * status.
   */
  public function currentState(FormStateInterface $form_state): string {
    return $form_state->get('state')
      ? $form_state->get('state')
      : ($this->amazeeClient->authorized()
        ? static::STATE_CONNECTED
        : static::STATE_DISCONNECTED
      );
  }

  /**
   * Determine if the module is in "test mode".
   */
  protected function testMode(): bool {
    return $this->moduleHandler->moduleExists('ai_provider_amazeeio_test');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(static::CONFIG_NAME);
    $this->amazeeClient->setToken($this->getTempStore()->get('access_token') ?? '');

    if ($auth_token = $this->getTempStore()->get('access_token')) {
      $this->amazeeClient->setToken($auth_token);
    }

    $buttonAjax = [
      'callback' => '::ajaxUpdate',
      'event' => 'click',
      'wrapper' => 'amazee-ai-config-form',
      'progress' => [
        'type' => 'throbber',
      ],
    ];

    $state = $this->currentState($form_state);
    $form['image'] = [
      "#markup" => '<p><img src="http://assets.amazee.ai/logo.png" alt="amazee.ai" width="250"/>',
    ];
    $ajax = [
      '#prefix' => '<div id="amazee-ai-config-form">',
      '#suffix' => '</div>',
    ];
    if ($state === static::STATE_DISCONNECTED) {
      $ajax['markup'] = [
        '#markup' => '<p><em>' . $this->t("Let's get you started! Enter your email address and we'll send you a code to sign in to <strong>amazee.ai</strong>.") . '</em></p>',
      ];
      $ajax['email'] = [
        // When in 'test mode' we use a simple text field, so the BrowserTest
        // is actually able to enter an invalid email address.
        '#type' => $this->testMode() ? 'textfield' : 'email',
        '#title' => $this->t('Email'),
        '#description' => $this->t('By entering your email address, you agree to amazee.ai\'s <a href="https://amazee.ai/terms-and-conditions">Terms of Service.</a>'),
      ];
      $ajax['submit_email'] = [
        '#type' => 'submit',
        '#value' => $this->t('Sign in'),
        '#ajax' => $buttonAjax,
        '#attributes' => ['class' => ['button', 'button--primary']],
      ];
    }

    if ($state === static::STATE_VERIFICATION) {
      $ajax['markup'] = [
        '#markup' => '<p><em>' . $this->t('Check your inbox. Enter the verification code we just sent to your email.') . '</em></p>',
      ];
      $ajax['code'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Code'),
      ];
      $ajax['submit_code'] = [
        '#type' => 'submit',
        '#value' => $this->t('Validate'),
        '#ajax' => $buttonAjax,
        '#attributes' => ['class' => ['button', 'button--primary']],
      ];
    }

    if ($state === static::STATE_VERIFIED) {
      try {
        $regions = $this->amazeeClient->getRegions();
      }
      catch (ClientException $e) {
        $this->messenger->addError($this->t('An error occurred while retrieving the available regions. Please consult the Drupal error log.'));
      }

      // Check if we already have a key
      $key_name = static::generatePrivateKeyName();
      $api_keys = array_filter(
        $this->amazeeClient->getPrivateApiKeys(),
        fn($key) => $key->name === $key_name
      );
      $api_key = reset($api_keys);
      if ($api_key) {
        $regions = [
          $api_key->region
        ];

        $ajax['markup'] = [
          '#markup' => '<p><em>' . $this->t('We found an existing key for this host (@host) with the following region.', ['@host' => static::generatePrivateKeyName()]) . '</em></p>',
        ];
      } else {
        $ajax['markup'] = [
          '#markup' => '<p><em>' . $this->t('Choose where your AI features will be hosted.') . '</em></p>',
        ];
      }

      $ajax['region'] = [
        '#type' => 'select',
        '#title' => $this->t('Region'),
        '#options' => $regions ?? [],
        '#title_display' => 'before',
        '#access' => !empty($regions),
      ];
      $ajax['submit_region'] = [
        '#type' => 'submit',
        '#value' => $this->t('Connect'),
        '#access' => !empty($regions),
        '#attributes' => ['class' => ['button', 'button--primary']],
      ];
    }

    if ($state === static::STATE_CONNECTED) {
      $ajax['submit_disconnect'] = [
        '#type' => 'submit',
        '#value' => $this->t('Disconnect'),
        '#attributes' => ['class' => ['button', 'button--danger']],
      ];

      $host = $config->get('host');
      if (!(empty($host) || !$this->keyRepository->getKey(static::API_KEY_NAME)->getKeyValue())) {
        $ajax['usage'] = [
          '#theme' => 'table',
          '#rows' => [],
          '#weight' => 20,
        ];

        // Show the key name (hostname)
        $ajax['usage']['#rows'][] = [
          $this->t('Name'),
          static::generatePrivateKeyName(),
        ];

        if ($database = $config->get('postgres_default_database')) {
          $ajax['usage']['#rows'][] = [
            $this->t('VectorDB Database'),
            $database,
          ];
        }

        foreach ($ajax['usage']['#rows'] as &$row) {
          $row[0] = [
            'data' => ['#markup' => $row[0]],
            'header' => TRUE,
          ];
        }
      }
    }
    if ($state === static::STATE_CONFIRM_DISCONNECT) {
      $ajax['markup'] = [
        '#markup' => '<p><em>' . $this->t('Are you sure you want to disconnect from <strong>amazee.ai</string>?') . '</em></p>',
      ];
      $ajax['submit_confirm_disconnect'] = [
        '#type' => 'submit',
        '#value' => $this->t('Disconnect'),
        '#attributes' => ['class' => ['button', 'button--danger']],
      ];
      $ajax['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->t('No! Go back!'),
        '#ajax' => $buttonAjax,
        '#attributes' => ['class' => ['button', 'button--secondary']],
      ];
    }
    $form['ajax'] = $ajax;

    return $form;
  }

  /**
   * Ajax callback to dynamically update the form.
   */
  public static function ajaxUpdate(array &$form, FormStateInterface $form_state) {
    return $form['ajax'];
  }

  /**
   * Signup form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $state = $this->currentState($form_state);

    if ($state === static::STATE_DISCONNECTED) {
      $email = $form_state->getValue('email');
      if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_state->set('email', $email);
        $this->amazeeClient->requestCode($email);
        $form_state->set('state', static::STATE_VERIFICATION);
      }
      else {
        $form_state->setErrorByName('email', $this->t('Invalid email address.'));
      }
    }
    if ($state === static::STATE_VERIFICATION) {
      $email = $form_state->get('email');
      $code = $form_state->getValue('code');
      $token = $this->amazeeClient->validateCode($email, $code);
      if ($token) {
        $this->getTempStore()->set('access_token', $token);
        $form_state->set('state', static::STATE_VERIFIED);
      }
      else {
        $form_state->setErrorByName('code', $this->t('The provided code is incorrect or has expired.'));
      }
    }
    if ($state === static::STATE_VERIFIED) {
      $region = $form_state->getValue('region');
      $key_name = static::generatePrivateKeyName();
      $api_keys = array_filter(
        $this->amazeeClient->getPrivateApiKeys(),
        fn($key) => $key->name === $key_name
      );
      if (count($api_keys) > 0) {
        // Return now to not rebuild the form but submit it.
        return;
      }
      else {
        $private_key = $this->amazeeClient->createPrivateAiKey(
          $region,
          static::generatePrivateKeyName(),
          $this->amazeeClient->getTeamId()
        );
        if (!$private_key) {
          $form_state->setErrorByName('region', $this->t('An error occurred while generating the private key. Please consult the Drupal error log.'));
        }
        else {
          // Return now to not rebuild the form but submit it.
          return;
        }
      }
    }
    if ($state === static::STATE_CONNECTED) {
      $element = $form_state->getTriggeringElement();
      if ($element['#id'] === 'edit-submit-disconnect') {
        $form_state->set('state', static::STATE_CONFIRM_DISCONNECT);
      }
    }
    if ($state === static::STATE_CONFIRM_DISCONNECT) {
      $element = $form_state->getTriggeringElement();
      if ($element['#id'] === 'edit-submit-confirm-disconnect') {
        $form_state->set('state', static::STATE_CONFIRM_DISCONNECT);
        // Return now to not rebuild the form but submit it.
        return;
      }
      else {
        $form_state->set('state', static::STATE_CONNECTED);
      }
    }
    $form_state->setRebuild();
  }

  /**
   * Generate a key name for this installation.
   *
   * Assumes that each Drupal installation has a single API key.
   */
  public static function generatePrivateKeyName(): string {
    return \Drupal::request()->getHost();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if ($form_state->get('state') === static::STATE_VERIFIED) {
      $config = $this->config(static::CONFIG_NAME);
      $this->amazeeClient->setToken($this->getTempStore()->get('access_token') ?? '');
      $this->amazeeClient->setHost($config->get('host') ?? '');
      $key_name = static::generatePrivateKeyName();
      $api_keys = array_filter(
        $this->amazeeClient->getPrivateApiKeys(),
        fn($key) => $key->name === $key_name
      );
      $api_key = reset($api_keys);

      if ($api_key) {
        // Set the provider config, using a known key name to ease support
        // preconfigured environments.
        $this->config(static::CONFIG_NAME)
          ->set('host', $api_key->litellm_api_url)
          ->set('postgres_host', $api_key->database_host)
          ->set('postgres_port', $api_key->database_port ?? static::POSTGRES_PORT_DEFAULT)
          ->set('postgres_default_database', $api_key->database_name)
          ->set('postgres_username', $api_key->database_username)
          ->set('postgres_password', static::VDB_PASSWORD_NAME)
          ->set('api_key', static::API_KEY_NAME)
          ->save();

        // Load or create the amazee.ai key.
        /** @var \Drupal\Core\Entity\EntityStorageInterface $key_storage */
        $key_storage = $this->entityTypeManager->getStorage('key');
        /** @var \Drupal\key\Entity\Key $key */
        $key = $key_storage->load(static::API_KEY_NAME) ??
          $key_storage->create(
            [
              'id' => static::API_KEY_NAME,
              'label' => 'amazee.ai AI API Key',
              'description' => 'Automatically created by the amazee.ai AI provider.',
            ]
          );
        // Update the key config.
        $key
          ->set('key_provider', 'config')
          ->set('key_provider_settings', ['key_value' => $api_key->litellm_token])
          ->set('key_input', 'text_field')
          ->save();

        // Load or create the amazee.ai Postgres key.
        /** @var \Drupal\key\Entity\Key $database_key */
        $database_key = $key_storage->load(static::VDB_PASSWORD_NAME) ??
          $key_storage->create(
            [
              'id' => static::VDB_PASSWORD_NAME,
              'label' => 'amazee.ai AI Database Key',
              'description' => 'Automatically created by the amazee.ai AI provider.',
            ]
          );
        // Update the key config.
        $database_key
          ->set('key_provider', 'config')
          ->set('key_provider_settings', ['key_value' => $api_key->database_password])
          ->set('key_input', 'text_field')
          ->save();

        // Set the default models where available.
        /** @var \Drupal\ai_provider_amazeeio\Plugin\AiProvider\AmazeeioAiProvider $provider */
        $provider = $this->aiProviderManager->createInstance('amazeeio');
        // Run post-setup when not in unit tests, since it connects to the
        // real LLM.
        if (!$this->testMode()) {
          $provider->postSetup();
        }
        $this->messenger()->addStatus($this->t('This website has been connected to <strong>amazee.ai</strong>.'));
      }
    }
    if ($form_state->get('state') === static::STATE_CONFIRM_DISCONNECT) {
      $this->getTempStore()->delete('access_token');
      $this->config(static::CONFIG_NAME)
        ->set('host', '')
        ->set('postgres_host', '')
        ->set('postgres_port', static::POSTGRES_PORT_DEFAULT)
        ->set('postgres_default_database', '')
        ->set('postgres_username', '')
        ->set('postgres_password', static::VDB_PASSWORD_NAME)
        ->set('api_key', static::API_KEY_NAME)
        ->save();
      /** @var EntityStorageInterface $key_storage */
      $key_storage = $this->entityTypeManager->getStorage('key');
      $apiKey = $key_storage->load(static::API_KEY_NAME);
      $dbKey = $key_storage->load(static::VDB_PASSWORD_NAME);
      if ($apiKey) {
        $apiKey->delete();
      }
      if ($dbKey) {
        $dbKey->delete();
      }
      $this->messenger()->addWarning($this->t('This website has been disconnected from <strong>amazee.ai</strong>.'));
    }
  }

  /**
   * Get the temp store.
   *
   * @return \Drupal\Core\TempStore\PrivateTempStore
   *   The temp store.
   */
  protected function getTempStore(): PrivateTempStore {
    return $this->tempStoreFactory->get('amazeeio_ai');
  }

}
