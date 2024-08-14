<?php

namespace Drupal\mars_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\mars_common\LanguageHelper;
use Drupal\mars_common\ThemeConfiguratorParser;
use Drupal\mars_lighthouse\Traits\EntityBrowserFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Contact Form block.
 *
 * @Block(
 *   id = "contact_form",
 *   admin_label = @Translation("MARS: Contact Form"),
 *   category = @Translation("Mars Common")
 * )
 */
class ContactFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use EntityBrowserFormTrait;
  /**
   * Default background style.
   */
  const KEY_OPTION_OTHER_COLOR = 'other';

  /**
   * Default background style.
   */
  const KEY_OPTION_TEXT_COLOR_DEFAULT = 'color_b';

  /**
   * The configFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Theme configurator parser.
   *
   * @var \Drupal\mars_common\ThemeConfiguratorParser
   */

  private $themeConfiguratorParser;

  /**
   * Language helper service.
   *
   * @var \Drupal\mars_common\LanguageHelper
   */
  private $languageHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('mars_common.language_helper'),
      $container->get('mars_common.theme_configurator_parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    LanguageHelper $language_helper,
    ThemeConfiguratorParser $theme_configurator_parser
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->languageHelper = $language_helper;
    $this->themeConfiguratorParser = $theme_configurator_parser;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $conf = $this->getConfiguration();
    $block_build_styles = mars_common_block_build_style($conf);
    $build['#form_id'] = $conf['form_id'] ?? '';
    $build['#override_letter_case_title'] = $conf['override_letter_case_title'];
    $build['#head_style'] = $block_build_styles['block_build_head_style'];
    $build['#dynamic_data_theme_id'] = $block_build_styles['block_build_theme_id'];
    $build['#theme'] = 'contact_form_block';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'label_display' => FALSE,
      'text_color' => self::KEY_OPTION_TEXT_COLOR_DEFAULT,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['form_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form script endpoint'),
      '#default_value' => $this->configuration['form_id'] ?? '',
      '#required' => TRUE,
      '#size' => 65,
    ];
    $form['override_letter_case_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override letter case for Contact Title labels'),
      '#default_value' => $this->configuration['override_letter_case_title'] ?? FALSE,
    ];
    $form['text_color'] = [
      '#type' => 'radios',
      '#title' => $this->t('Text color'),
      '#options' => $this->getTextColorOptions(),
      '#default_value' => $this->configuration['text_color'] ?? NULL,
    ];
    $form['text_color_other'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Custom text color'),
      '#default_value' => $this->configuration['text_color_other'] ?? NULL,
      '#attributes' => ['class' => ['show-clear']],
      '#description' => $this->t('If this field is left empty, it falls back to Theme colors.'),
      '#states' => [
        'visible' => [
          [':input[name="settings[text_color]"]' => ['value' => self::KEY_OPTION_OTHER_COLOR]],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);
    $this->configuration['form_id'] = $form_state->getValue('form_id');
    $this->configuration['override_letter_case_title'] = $form_state->getValue('override_letter_case_title');
    $this->configuration['text_color'] = $form_state->getValue('text_color');
    $this->configuration['text_color_other'] = $form_state->getValue('text_color_other');
  }

  /**
   * Get text color options.
   *
   * @return array
   *   Options.
   */
  private function getTextColorOptions() {
    return [
      'color_a' => '<span class="theme-color-label">Color A</span> ' . mars_common_get_color_palette('color_a'),
      'color_b' => '<span class="theme-color-label">Color B</span> ' . mars_common_get_color_palette('color_b'),
      'color_c' => '<span class="theme-color-label">Color C</span> ' . mars_common_get_color_palette('color_c'),
      'color_d' => '<span class="theme-color-label">Color D</span> ' . mars_common_get_color_palette('color_d'),
      'color_e' => '<span class="theme-color-label">Color E</span> ' . mars_common_get_color_palette('color_e'),
      'color_f' => '<span class="theme-color-label">Color F</span> ' . mars_common_get_color_palette('color_f'),
      self::KEY_OPTION_OTHER_COLOR => 'Other',
    ];
  }

}
