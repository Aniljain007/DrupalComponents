<?php

namespace Drupal\mars_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\mars_common\LanguageHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Text component block.
 *
 * @Block(
 *   id = "text_block",
 *   admin_label = @Translation("MARS: Text block"),
 *   category = @Translation("Page components"),
 * )
 */
class TextBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The configFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Language helper service.
   *
   * @var \Drupal\mars_common\LanguageHelper
   */
  private $languageHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    LanguageHelper $language_helper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->languageHelper = $language_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('mars_common.language_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();
    $character_limit_config = $this->configFactory->getEditable('mars_common.character_limit_page');

    $form['header'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header'),
      '#maxlength' => !empty($character_limit_config->get('text_block_header')) ? $character_limit_config->get('text_block_header') : 55,
      '#default_value' => $config['header'] ?? '',
    ];

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#default_value' => $config['body']['value'] ?? '',
      '#format' => $config['body']['format'] ?? 'rich_text',
    ];

    $form['iframe_width_full'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('iFrame full width'),
      '#default_value' => !empty($config['iframe_width_full']) ? $config['iframe_width_full'] : FALSE,
    ];
    // Element Id for Text block.
    $form['element_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element ID (Text block)'),
      '#description' => $this->t('Use same element ID("ele_id")directly in Page Link of Quick link component to navigate within the page'),
      '#default_value' => $config['element_id'] ?? '',
    ];
    $form['add_top_spacing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add top spacing'),
      '#default_value' => $this->configuration['add_top_spacing'] ?? TRUE,
    ];
    $form['add_bottom_spacing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add bottom spacing'),
      '#default_value' => $this->configuration['add_bottom_spacing'] ?? TRUE,
    ];
    $form['remove_left_right_spacing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add left and right spacing'),
      '#default_value' => $this->configuration['remove_left_right_spacing'] ?? TRUE,
    ];
    $form['text_color_override'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Override Text color'),
      '#default_value' => $this->configuration['text_color_override'] ?? '',
      '#description'   => $this->t('If this field is left empty, it falls back to Color A.'),
      '#attributes' => ['class' => ['show-clear']],
    ];
    $form['text_bg_color_override'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Override background color'),
      '#default_value' => $this->configuration['text_bg_color_override'] ?? '',
      '#description'   => $this->t('If this field is left empty, it falls back to Color E.'),
      '#attributes' => ['class' => ['show-clear']],
    ];

    $form['custom_class'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable table layout(eg: Nutrition Info) in Recipe'),
      '#default_value' => $config['custom_class'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * This method processes the blockForm() form fields when the block
   * configuration form is submitted.
   *
   * The blockValidate() method can be used to validate the form submission.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $block_build_styles = mars_common_block_build_style($config);
    $build['#content'] = $this->languageHelper->translate($config['body']['value']);
    $build['#header'] = $config['header'];
    $build['#iframe_width_full'] = !empty($config['iframe_width_full']) ? TRUE : FALSE;
    $build['#add_top_spacing'] = $config['add_top_spacing'] ?? TRUE;
    $build['#add_bottom_spacing'] = $config['add_bottom_spacing'] ?? TRUE;
    $build['#remove_left_right_spacing'] = $config['remove_left_right_spacing'] ?? TRUE;
    $build['#custom_class'] = !empty($config['custom_class']) ? TRUE : FALSE;
    $build['#element_id'] = $config['element_id'] ?? '';
    $build['#head_style'] = $block_build_styles['block_build_head_style'];
    $build['#dynamic_data_theme_id'] = $block_build_styles['block_build_theme_id'];
    $build['#theme'] = 'text_block';
    return $build;
  }

}
