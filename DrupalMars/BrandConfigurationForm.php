<?php

namespace Drupal\pn_common\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Brand configuration form controller.
 */
class BrandConfigurationForm extends ConfigFormBase {

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The default cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Setting configuration ID.
   */
  public const CONFIG_NAME = 'pn_common.datalayerbrand_settings';

  /**
   * Font types.
   */
  public const FONT_TYPES = ['headline', 'primary', 'secondary', 'tertiary'];

  /**
   * Available country short codes.
   */
  public const COUNTRY_CODES = [
    'uk',
    'pl',
    'de',
    'fr',
    'mx',
    'us',
    'it',
    'id',
    'my',
    'ph',
    'sg',
    'th',
    'au',
    'in',
    'others',
  ];

  /**
   * Available regions.
   */
  public const REGIONS = [
    'asia pacific',
    'europe',
    'global',
    'latin america',
    'middle east & africa',
    'north america',
    'others',
  ];

  /**
   * Site file settings path name.
   */
  public const SITE_FILE_SETTINGS_PATH = '../site-config.json';

  /**
   * BrandConfiguration constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The default cache bin.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_manager, CacheBackendInterface $cache) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_manager;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pn_common_datalayerbrand_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Attempt to reload the site config from cache.
    $site_config_data = &drupal_static(__FUNCTION__);
    $cid = 'pn_common_site_config';
    if ($cache = $this->cache->get($cid)) {
      $site_config_data = $cache->data;
    }
    // If not cached load directly from file.
    else {
      $site_config_data = Json::decode(file_get_contents(self::SITE_FILE_SETTINGS_PATH));
      // Cache the JSON object for 24 hours.
      $this->cache->set($cid, $site_config_data, time() + 60 * 1440);
    }

    $config = $this->config(self::CONFIG_NAME);

    $form['brand'] = [
      '#type' => 'details',
      '#title' => $this->t('Brand settings'),
      '#open' => TRUE,
    ];

    if ($site_config_data['sites']) {
      $brands = array_keys($site_config_data['sites']);
    }
    else {
      $brands = [];
    }

    $form['brand']['brand_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Brand type'),
      '#options' => array_combine($brands, $brands),
      '#required' => TRUE,
      '#default_value' => $config->get('brand.brand_type_def'),
      '#description' => $this->t('Changing brand type will trigger cache clearing so be patient after form submitting.'),
    ];

    $form['brand']['brand_others'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other Brand'),
      '#default_value' => $config->get('brand.brand_others_def'),
      '#attributes' => [
        'placeholder' => $this->t('Provide brand type'),
      ],
      '#description' => $this->t('Give brand type when option not listing'),
    ];

    $form['country'] = [
      '#type' => 'details',
      '#title' => $this->t('Country Code settings'),
      '#open' => TRUE,
    ];

    $form['country']['country_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Country code'),
      '#options' => array_combine(self::COUNTRY_CODES, self::COUNTRY_CODES),
      '#required' => TRUE,
      '#default_value' => $config->get('country.country_code_def'),
    ];

    $form['country']['country_others'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other Country'),
      '#default_value' => $config->get('country.country_others_def'),
      '#attributes' => [
        'placeholder' => $this->t('Provide country'),
      ],
      '#description' => $this->t('Give country when option not listing'),
    ];

    $form['region'] = [
      '#type' => 'details',
      '#title' => $this->t('Region settings'),
      '#open' => TRUE,
    ];

    $form['region']['region_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Region'),
      '#options' => array_combine(self::REGIONS, self::REGIONS),
      '#required' => TRUE,
      '#default_value' => $config->get('region.region_name_def'),
    ];

    $form['region']['region_others'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other Region'),
      '#default_value' => $config->get('region.region_others_def'),
      '#attributes' => [
        'placeholder' => $this->t('Provide region'),
      ],
      '#description' => $this->t('Give region when option not listing'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Exclude unnecessary elements before saving, buttons etc.
    $form_state->cleanValues();

    $config = $this->config(self::CONFIG_NAME);
    if ($form_state->getValue('brand_type') == 'others') {
      $config->set('brand.brand_type', $form_state->getValue('brand_others'));
      $config->set('brand.brand_type_def', $form_state->getValue('brand_type'));
      $config->set('brand.brand_others_def', $form_state->getValue('brand_others'));
    }
    else {
      $config->set('brand.brand_type', $form_state->getValue('brand_type'));
      $config->set('brand.brand_type_def', $form_state->getValue('brand_type'));
      $config->set('brand.brand_others_def', $form_state->getValue('brand_others'));
    }

    if ($form_state->getValue('country_code') == 'others') {
      $config->set('country.country_code', $form_state->getValue('country_others'));
      $config->set('country.country_code_def', $form_state->getValue('country_code'));
      $config->set('country.country_others_def', $form_state->getValue('country_others'));
    }
    else {
      $config->set('country.country_code', $form_state->getValue('country_code'));
      $config->set('country.country_code_def', $form_state->getValue('country_code'));
      $config->set('country.country_others_def', $form_state->getValue('country_others'));
    }

    if ($form_state->getValue('region_name') == 'others') {
      $config->set('region.region_name', $form_state->getValue('region_others'));
      $config->set('region.region_name_def', $form_state->getValue('region_name'));
      $config->set('region.region_others_def', $form_state->getValue('region_others'));
    }
    else {
      $config->set('region.region_name', $form_state->getValue('region_name'));
      $config->set('region.region_name_def', $form_state->getValue('region_name'));
      $config->set('region.region_others_def', $form_state->getValue('region_others'));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
