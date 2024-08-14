<?php

namespace Drupal\mars_common\Plugin\Block;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\mars_common\LanguageHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Alert banner block.
 *
 * @Block(
 *   id = "alertbanner_block",
 *   admin_label = @Translation("MARS: Alert banner block")
 * )
 */
class AlertBannerBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Language helper service.
   *
   * @var \Drupal\mars_common\LanguageHelper
   */
  private $languageHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config, EntityTypeManagerInterface $entityTypeManager, LanguageHelper $language_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config;
    $this->entityTypeManager = $entityTypeManager;
    $this->languageHelper = $language_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('mars_common.language_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();
    $character_limit_config = $this->configFactory->get('mars_common.character_limit_page');

    $form['alert_banner'] = [
      '#type' => 'details',
      '#title' => $this->t('Alert banner'),
      '#open' => TRUE,
    ];
    $form['alert_banner']['alert_banner_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Alert Banner text'),
      '#description' => $this->t('This text will appear in Alert Banner.'),
      '#default_value' => $config['alert_banner']['alert_banner_text']['value'] ?? '',
      '#format' => $config['alert_banner']['alert_banner_text']['format'] ?? 'plain_text',
      '#maxlength' => !empty($character_limit_config->get('alert_banner_text')) ? $character_limit_config->get('alert_banner_text') : 100,
    ];
    $form['alert_banner']['alert_banner_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alert Banner link'),
      '#description' => $this->t('Ex. http://mars.com, /products'),
      '#default_value' => $config['alert_banner']['alert_banner_url'] ?? '',
    ];
    $form['alert_banner']['cta_button_target'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open  link in a new tab'),
      '#default_value' => $config['alert_banner']['cta_button_target'] ?? TRUE,
    ];
    $form['alert_banner']['override_color_scheme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override Alert Banner Color scheme'),
      '#default_value' => $config['alert_banner']['override_color_scheme'] ?? NULL,
    ];
    $form['alert_banner']['multiple_alert_banner_block'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override colors for Alert Banner'),
      '#default_value' => $config['alert_banner']['multiple_alert_banner_block'] ?? NULL,
      '#states' => [
        'visible' => [
          [':input[name="settings[alert_banner][override_color_scheme]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['alert_banner']['bg_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Override banner background color'),
      '#default_value' => $config['alert_banner']['bg_color'] ?? NULL,
      '#states' => [
        'visible' => [
          [':input[name="settings[alert_banner][override_color_scheme]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['alert_banner']['text_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Override banner text color'),
      '#default_value' => $config['alert_banner']['text_color'] ?? NULL,
      '#states' => [
        'visible' => [
          [':input[name="settings[alert_banner][override_color_scheme]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if ($alert_banner_url = $form_state->getValue('alert_banner')['alert_banner_url']) {
      // Check if textfield contains relative or absolute url.
      if (!(UrlHelper::isValid($alert_banner_url, TRUE) ||
        UrlHelper::isValid($alert_banner_url))) {
        $message = $this->t('Please check url (internal or external)');
        $form_state->setErrorByName('alert_banner_url', $message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $config_defualt_color = $this->configFactory->get('emulsifymars.settings');
    $color_b_for_bg = $config_defualt_color->get('color_b');
    $color_e_for_text = $config_defualt_color->get('color_e');
    $build['#alert_banner_text'] = $this->languageHelper->translate($config['alert_banner']['alert_banner_text']['value']);
    $build['#alert_banner_url'] = $this->languageHelper->translate($config['alert_banner']['alert_banner_url']);
    // Resolve warning Undefined array key cta_button_target.
    if (!empty($config['alert_banner']['cta_button_target'])) {
      $build['#cta_button_target'] = ($config['alert_banner']['cta_button_target'] == TRUE) ? '_blank' : '_self';
    }
    $build['#alert_banner_color_overide'] = $config['alert_banner']['override_color_scheme'];
    $build['#multiple_alert_banner_block'] = !empty($config['alert_banner']['multiple_alert_banner_block']) ? $config['alert_banner']['multiple_alert_banner_block'] : '';
    $build['#alert_banner_bg_color'] = !empty($config['alert_banner']['bg_color']) ? $config['alert_banner']['bg_color'] : $color_b_for_bg;
    $build['#alert_banner_text_color'] = !empty($config['alert_banner']['text_color']) ? $config['alert_banner']['text_color'] : $color_e_for_text;
    $build['#theme'] = 'alert_banner_block';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $formStateValues = $form_state->getValues();
    $this->setConfiguration($formStateValues);
  }

}
