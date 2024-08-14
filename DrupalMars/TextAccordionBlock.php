<?php

namespace Drupal\mars_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\mars_common\LanguageHelper;
use Drupal\mars_common\ThemeConfiguratorParser;
use Drupal\mars_lighthouse\Traits\EntityBrowserFormTrait;
use Drupal\mars_media\MediaHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TextAccordionBlock is responsible for text accordion block.
 *
 * @Block(
 *   id = "text_accordion_block",
 *   admin_label = @Translation("MARS: Accordion"),
 *   category = @Translation("Page components"),
 * )
 *
 * @package Drupal\mars_common\Plugin\Block
 */
class TextAccordionBlock extends BlockBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface {

  use EntityBrowserFormTrait;

  /**
   * Lighthouse entity browser id.
   */
  const LIGHTHOUSE_ENTITY_BROWSER_ID = 'lighthouse_browser';

  /**
   * Mars Media Helper service.
   *
   * @var \Drupal\mars_media\MediaHelper
   */
  protected $mediaHelper;

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
   * Theme configurator parser.
   *
   * @var \Drupal\mars_common\ThemeConfiguratorParser
   */
  protected $themeConfiguratorParser;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    LanguageHelper $language_helper,
    ThemeConfiguratorParser $theme_configurator_parser,
    MediaHelper $media_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->languageHelper = $language_helper;
    $this->themeConfiguratorParser = $theme_configurator_parser;
    $this->mediaHelper = $media_helper;
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
      $container->get('mars_common.language_helper'),
      $container->get('mars_common.theme_configurator_parser'),
      $container->get('mars_media.media_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $items = [];
    $build = [];

    foreach ($config['accordion'] as $item_value) {

      $item = [
        'title' => $item_value['title']['value'],
        'description' => $item_value['description']['value'],
      ];
      $image_url = $image_alt = NULL;
      if (!empty($item_value['image'])) {

        $media_id = $this->mediaHelper->getIdFromEntityBrowserSelectValue($item_value['image']);
        if ($media_id) {
          $media_params = $this->mediaHelper->getMediaParametersById($media_id);
          if (!isset($media_params['error'])) {
            $image_url = $media_params['src'];
            $image_alt = $media_params['alt'];
          }
        }
      }
      $item['img'] = $image_url;
      $item['alt'] = $image_alt;
      $items[] = $item;
    }
    $build['#title'] = $this->languageHelper->translate($config['text_label'] ?? '');
    $build['#text_alignment'] = $config['text_alignment'] ?? FALSE;
    $build['#text_item_alignment'] = $config['text_item_alignment'] ?? FALSE;
    $build['#items'] = $items;
    $build['#theme'] = 'text_accordion_component';

    $text_color_override = FALSE;
    if (!empty($this->configuration['override_text_color']['override_color'])) {
      $text_color_override = static::$overrideColor;
    }
    if (!empty($config['override_text_color']['override_filter_title_color'])) {
      $build['#override_filter_title_color'] = static::$overrideColor;
    }
    $block_build_styles = mars_common_block_build_style($config);
    $build['#head_style'] = $block_build_styles['block_build_head_style'];
    $build['#dynamic_data_theme_id'] = $block_build_styles['block_build_theme_id'];
    $build['#text_color_override'] = $text_color_override;
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();
    $character_limit_config = $this->configFactory->getEditable('mars_common.character_limit_page');
    $form['text_label'] = [
      '#title'         => $this->t('Title'),
      '#type'          => 'textfield',
      '#default_value' => $config['text_label'],
      '#maxlength' => !empty($character_limit_config->get('accordion_block_title')) ? $character_limit_config->get('accordion_block_title') : 255,
    ];

    $form['text_alignment'] = [
      '#type' => 'radios',
      '#title' => $this->t('Text alignment'),
      '#default_value' => $config['text_alignment'] ?? 'center',
      '#attributes' => ['class' => ['enable-single-text-aligment']],
      '#options' => [
        'left' => $this->t('Left'),
        'center' => $this->t('Center'),
        'right' => $this->t('Right'),
      ],
    ];
    $form['text_item_alignment'] = [
      '#type' => 'radios',
      '#title' => $this->t('Text accordion item alignment'),
      '#default_value' => $config['text_item_alignment'] ?? 'center',
      '#attributes' => ['class' => ['enable-single-text-aligment']],
      '#options' => [
        'left' => $this->t('Left'),
        'center' => $this->t('Center'),
        'right' => $this->t('Right'),
      ],
    ];

    $form['accordion'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('Text Accordion Items Details'),
      '#prefix' => '<div id="accordion-wrapper">',
      '#suffix' => '</div>',
    ];

    $submitted_input = $form_state->getUserInput()['settings'] ?? [];
    $saved_items = !empty($config['accordion']) ? $config['accordion'] : [];
    $submitted_items = $submitted_input['accordion'] ?? [];
    $current_items_state = $form_state->get('accordion_storage');

    if (empty($submitted_items) && empty($saved_items)) {
      $current_items_state = [1];
    }

    if (empty($current_items_state)) {
      if (!empty($submitted_items)) {
        $current_items_state = $submitted_items;
      }
      else {
        $current_items_state = $saved_items;
      }
    }

    $form_state->set('accordion_storage', $current_items_state);

    foreach ($current_items_state as $key => $value) {

      $form['accordion'][$key]['title'] = [
        '#title' => $this->t('item title'),
        '#type' => 'text_format',
        '#format' => $this->configuration['body']['format'] ?? 'rich_text',
        '#default_value' => $config['accordion'][$key]['title']['value'] ?? NULL,
        '#maxlength' => !empty($character_limit_config->get('accordion_item_title')) ? $character_limit_config->get('accordion_item_title') : 255,
        '#required' => TRUE,
      ];

      $form['accordion'][$key]['description'] = [
        '#title' => $this->t('item description'),
        '#type' => 'text_format',
        '#format' => $this->configuration['body']['format'] ?? 'rich_text',
        '#default_value' => $config['accordion'][$key]['description']['value'] ?? NULL,
        '#maxlength' => !empty($character_limit_config->get('accordion_item_description')) ? $character_limit_config->get('accordion_item_description') : 255,
        '#required' => TRUE,
      ];

      $form['accordion'][$key]['image'] = $this->getEntityBrowserForm(self::LIGHTHOUSE_ENTITY_BROWSER_ID,
        $config['accordion'][$key]['image'], $form_state, 1, 'thumbnail', FALSE);
      $form['accordion'][$key]['image']['#type'] = 'details';
      $form['accordion'][$key]['image']['#title'] = $this->t('image');
      $form['accordion'][$key]['image']['#open'] = TRUE;

      $form['accordion'][$key]['remove_item'] = [
        '#type' => 'submit',
        '#name' => 'accordion_' . $key,
        '#value' => $this->t('Remove text accordion item'),
        '#ajax' => [
          'callback' => [$this, 'ajaxRemoveTextAccordionItemCallback'],
          'wrapper' => 'accordion-wrapper',
        ],
        '#limit_validation_errors' => [],
        '#submit' => [[$this, 'removeTextAccordionItemSubmitted']],
      ];
    }

    $form['accordion']['add_item'] = [
      '#type'  => 'submit',
      '#name'  => 'accordion_add_item',
      '#value' => $this->t('Add new text accordion item'),
      '#ajax'  => [
        'callback' => [$this, 'ajaxAddTextAccordionItemCallback'],
        'wrapper'  => 'accordion-wrapper',
      ],
      '#limit_validation_errors' => [],
      '#submit' => [[$this, 'addTextAccordionItemSubmitted']],
    ];

    // Block Title color override.
    $form['bg_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Accordion Background Color'),
      '#default_value' => $config['bg_color'] ?? '',
      '#attributes' => ['class' => ['show-clear']],
      '#description'   => $this->t('If this field is left empty, it falls back to color E.'),
    ];

    // Block Title color override.
    $form['accordion_heading_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Text Accordion Heading Title Color Override'),
      '#default_value' => $config['accordion_heading_color'] ?? '',
      '#attributes' => ['class' => ['show-clear']],
      '#description'   => $this->t('If this field is left empty, it falls back to color B.'),
    ];

    // Background color override.
    $form['accordion_background_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Text Accordion Background Color Override'),
      '#default_value' => $config['accordion_background_color'] ?? '',
      '#attributes' => ['class' => ['show-clear']],
      '#description'   => $this->t('If this field is left empty, it falls back to color A.'),
    ];

    // Title color override.
    $form['accordion_title_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Text Accordion Item Title Color Override'),
      '#default_value' => $config['accordion_title_color'] ?? '',
      '#attributes' => ['class' => ['show-clear']],
      '#description'   => $this->t('If this field is left empty, it falls back to color B.'),
    ];

    // Description color override.
    $form['accordion_desc_color'] = [
      '#type' => 'jquery_colorpicker',
      '#title' => $this->t('Text Accordion Item Description Color Override'),
      '#default_value' => $config['accordion_desc_color'] ?? '',
      '#attributes' => ['class' => ['show-clear']],
      '#description'   => $this->t('If this field is left empty, it falls back to color B.'),
    ];

    return $form;
  }

  /**
   * Add new text accordion item callback.
   *
   * @param array $form
   *   Theme settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Theme settings form state.
   *
   * @return array
   *   List container of configuration settings.
   */
  public function ajaxAddTextAccordionItemCallback(array $form, FormStateInterface $form_state) {
    return $form['settings']['accordion'];
  }

  /**
   * Add remove text accordion item callback.
   *
   * @param array $form
   *   Theme settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Theme settings form state.
   *
   * @return array
   *   List container of configuration settings.
   */
  public function ajaxRemoveTextAccordionItemCallback(array $form, FormStateInterface $form_state) {
    return $form['settings']['accordion'];
  }

  /**
   * Custom submit text accordion configuration settings form.
   *
   * @param array $form
   *   Theme settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Theme settings form state.
   */
  public function addTextAccordionItemSubmitted(
    array $form,
    FormStateInterface $form_state
  ) {
    $storage = $form_state->get('accordion_storage');
    array_push($storage, 1);
    $form_state->set('accordion_storage', $storage);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Custom submit text accordion configuration settings form.
   *
   * @param array $form
   *   Theme settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Theme settings form state.
   */
  public function removeTextAccordionItemSubmitted(
    array $form,
    FormStateInterface $form_state
  ) {
    $triggered = $form_state->getTriggeringElement();
    if (isset($triggered['#parents'][3]) && $triggered['#parents'][3] == 'remove_item') {
      $text_accordion_storage = $form_state->get('accordion_storage');
      $id = $triggered['#parents'][2];
      unset($text_accordion_storage[$id]);
      $form_state->set('accordion_storage', $text_accordion_storage);
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    unset($values['accordion']['add_item']);
    $this->setConfiguration($values);

    if (isset($values['accordion']) && !empty($values['accordion'])) {
      foreach ($values['accordion'] as $key => $item) {
        $this->configuration['accordion'][$key]['image'] = $this->getEntityBrowserValue($form_state, [
          'accordion',
          $key,
          'image',
        ]);
      }
    }
  }

}
