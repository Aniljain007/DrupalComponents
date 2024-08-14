<?php

namespace Drupal\mars_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\mars_common\LanguageHelper;
use Drupal\mars_common\ThemeConfiguratorParser;
use Drupal\mars_common\Traits\OverrideThemeTextColorTrait;
use Drupal\mars_lighthouse\Traits\EntityBrowserFormTrait;
use Drupal\mars_media\MediaHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MARS: Supercharged Card Component' Block.
 *
 * @Block(
 *   id = "supercharged_card_component",
 *   admin_label = @Translation("MARS: Supercharged Card Component"),
 *   category = @Translation("Mars Common"),
 * )
 */
class SuperChargedCardComponent extends BlockBase implements ContainerFactoryPluginInterface {

  use EntityBrowserFormTrait;
  use OverrideThemeTextColorTrait;

  /**
   * Lighthouse entity browser id.
   */
  const LIGHTHOUSE_ENTITY_BROWSER_ID = 'lighthouse_browser';

  /**
   * Aligned by center.
   */
  const TOP_ALIGNED = 'top';

  /**
   * Aligned by center.
   */
  const BOTTOM_ALIGNED = 'bottom';

  /**
   * The configFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Theme configurator parser.
   *
   * @var \Drupal\mars_common\ThemeConfiguratorParser
   */
  protected $themeConfiguratorParser;

  /**
   * Mars Media Helper service.
   *
   * @var \Drupal\mars_media\MediaHelper
   */
  protected $mediaHelper;

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
    EntityTypeManagerInterface $entity_type_manager,
    ThemeConfiguratorParser $theme_configurator_parser,
    LanguageHelper $language_helper,
    MediaHelper $media_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->themeConfiguratorParser = $theme_configurator_parser;
    $this->languageHelper = $language_helper;
    $this->mediaHelper = $media_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('mars_common.theme_configurator_parser'),
      $container->get('mars_common.language_helper'),
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
    foreach ($config['supercharged_card'] as $key => $item_value) {
      // Eyebrow.
      if ($config['supercharged_card'][$key]['eyebrow']['value']) {
        $items[$key]['eyebrow']['value'] = $this->languageHelper->translate($config['supercharged_card'][$key]['eyebrow']['value']);
        $items[$key]['eyebrow']['order'] = !empty($config['sequence']['eyebrow']) ? $config['sequence']['eyebrow'] : '1';
        $items[$key]['eyebrow']['key'] = 'eyebrow';
      }
      // Title/Icon.
      if (!$config['supercharged_card'][$key]['title_icon']['icon_toggle'] && $config['supercharged_card'][$key]['title_icon']['title']) {
        $items[$key]['title']['value'] = $this->languageHelper->translate($config['supercharged_card'][$key]['title_icon']['title']);
        $items[$key]['title']['order'] = !empty($config['sequence']['title_icon']) ? $config['sequence']['title_icon'] : '2';
        $items[$key]['title']['key'] = 'title';
      }
      // Subtitle.
      if ($config['supercharged_card'][$key]['subtitle']['value']) {
        $items[$key]['subtitle']['value'] = $this->languageHelper->translate($config['supercharged_card'][$key]['subtitle']['value']);
        $items[$key]['subtitle']['order'] = !empty($config['sequence']['subtitle']) ? $config['sequence']['subtitle'] : '3';
        $items[$key]['subtitle']['key'] = 'subtitle';
      }
      // Description.
      if ($config['supercharged_card'][$key]['description']['value']['value']) {
        $items[$key]['description']['value'] = $this->languageHelper->translate($config['supercharged_card'][$key]['description']['value']['value']);
        $items[$key]['description']['order'] = !empty($config['sequence']['description']) ? $config['sequence']['description'] : '4';
        $items[$key]['description']['key'] = 'description';
        $items[$key]['description']['format'] = $config['supercharged_card'][$key]['description']['value']['format'];
      }
      // Icon/Image.
      if ($config['supercharged_card'][$key]['title_icon']['icon_toggle'] && !empty($this->configuration['supercharged_card'][$key]['title_icon']['icon_image'])) {
        $mediaId = $this->mediaHelper->getIdFromEntityBrowserSelectValue($config['supercharged_card'][$key]['title_icon']['icon_image']);
        $mediaParams = $this->mediaHelper->getMediaParametersById($mediaId);
        if (!($mediaParams['error'] ?? FALSE) && ($mediaParams['src'] ?? FALSE)) {
          $items[$key]['icon_image']['icon_src'] = $mediaParams['src'];
          $items[$key]['icon_image']['icon_image_alt'] = $mediaParams['alt'];
          $items[$key]['icon_image']['order'] = !empty($config['sequence']['title_icon']) ? $config['sequence']['title_icon'] : '3';
          $items[$key]['icon_image']['key'] = 'icon_image';
        }
      }
      $items[$key]['icon_toggle'] = !empty($config['supercharged_card'][$key]['title_icon']['icon_toggle']) ? $config['supercharged_card'][$key]['title_icon']['icon_toggle'] : '';
      // With CTA.
      $items[$key]['with_cta'] = ($config['supercharged_card'][$key]['with_cta'] == TRUE) ? $config['supercharged_card'][$key]['with_cta'] : 0;
      // Link/Button 1.
      if ($config['supercharged_card'][$key]['with_cta'] == TRUE && $config['supercharged_card'][$key]['link_button']['link_button_1']['cta_title_1']) {
        $items[$key]['cta']['value']['cta_title_url_1']['text'] = $this->languageHelper->translate($config['supercharged_card'][$key]['link_button']['link_button_1']['cta_title_1']);
        $items[$key]['cta']['value']['cta_title_url_1']['url'] = $config['supercharged_card'][$key]['link_button']['link_button_1']['cta_url_1'];
        $items[$key]['cta']['value']['cta_title_url_1']['cta_button'] = ($config['supercharged_card'][$key]['link_button']['link_button_1']['use_link_button'] == TRUE) ? $config['supercharged_card'][$key]['link_button']['link_button_1']['use_link_button'] : FALSE;
        $items[$key]['cta']['value']['cta_title_url_1']['cta_new_window'] = $config['supercharged_card'][$key]['link_button']['link_button_1']['cta_new_window'] == 1 ? '_blank' : '_self';
        $items[$key]['cta']['value']['cta_title_url_1']['key'] = 'cta_title_url_1';
      }
      // Link/Button 2.
      if ($config['supercharged_card'][$key]['with_cta'] == TRUE && $config['supercharged_card'][$key]['link_button']['link_button_2']['cta_title_2']) {
        $items[$key]['cta']['value']['cta_title_url_2']['text'] = $this->languageHelper->translate($config['supercharged_card'][$key]['link_button']['link_button_2']['cta_title_2']);
        $items[$key]['cta']['value']['cta_title_url_2']['url'] = $config['supercharged_card'][$key]['link_button']['link_button_2']['cta_url_2'];
        $items[$key]['cta']['value']['cta_title_url_2']['cta_button'] = ($config['supercharged_card'][$key]['link_button']['link_button_2']['use_link_button'] == TRUE) ? $config['supercharged_card'][$key]['link_button']['link_button_2']['use_link_button'] : FALSE;
        $items[$key]['cta']['value']['cta_title_url_2']['cta_new_window'] = $config['supercharged_card'][$key]['link_button']['link_button_2']['cta_new_window'] == 1 ? '_blank' : '_self';
        $items[$key]['cta']['value']['cta_title_url_2']['key'] = 'cta_title_url_2';
      }
      if ($config['supercharged_card'][$key]['with_cta'] == TRUE) {
        $items[$key]['cta']['order'] = !empty($config['sequence']['cta']) ? $config['sequence']['cta'] : '5';
        $items[$key]['cta']['key'] = 'cta';
      }

      // Background Image.
      if (!empty($this->configuration['supercharged_card'][$key]['image'])) {
        $mediaId = $this->mediaHelper->getIdFromEntityBrowserSelectValue($config['supercharged_card'][$key]['image']);
        $mediaParams = $this->mediaHelper->getMediaParametersById($mediaId);
        if (!($mediaParams['error'] ?? FALSE) && ($mediaParams['src'] ?? FALSE)) {
          $items[$key]['background_image']['image_src'] = $mediaParams['src'];
          $items[$key]['background_image']['image_alt'] = $mediaParams['alt'];
        }
      }
      $items[$key]['block_aligned'] = $config['supercharged_card'][$key]['block_aligned'];
      $items[$key]['cta_up_down'] = !empty($config['supercharged_card'][$key]['cta_up_down']) ? $config['supercharged_card'][$key]['cta_up_down'] : '';
    }
    $build['#items'] = $items;
    $build['#card_label'] = !empty($config['card_label']) ? $config['card_label'] : '';
    $build['#theme'] = 'supercharged_card_component';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();
    $character_limit_config = $this->configFactory->getEditable('mars_common.character_limit_page');
    $form['card_label'] = [
      '#title'         => $this->t('Card title'),
      '#type'          => 'textfield',
      '#default_value' => $config['card_label'],
      '#maxlength' => !empty($character_limit_config->get('supercharged_card_component_heading')) ? $character_limit_config->get('supercharged_card_component_heading') : 55,
    ];
    // Sequence.
    $form['sequence'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $this->t('Sequence'),
      '#description' => $this->t('Sequence can be defined by changing the weight value from dropdown. If you choose "one" for title and "five" for subtitle, order will be changed in the frontend.'),
    ];
    $form['sequence']['eyebrow'] = [
      '#type' => 'select',
      '#title' => $this->t('Eyebrow Weight'),
      '#default_value' => $config['sequence']['eyebrow'] ?? '1',
      '#options' => [
        '1' => $this->t('One'),
        '2' => $this->t('Two'),
        '3' => $this->t('Three'),
        '4' => $this->t('Four'),
        '5' => $this->t('Five'),
      ],
    ];
    $form['sequence']['title_icon'] = [
      '#type' => 'select',
      '#title' => $this->t('Title/Icon Weight'),
      '#default_value' => $config['sequence']['title_icon'] ?? '2',
      '#options' => [
        '1' => $this->t('One'),
        '2' => $this->t('Two'),
        '3' => $this->t('Three'),
        '4' => $this->t('Four'),
        '5' => $this->t('Five'),
      ],
    ];
    $form['sequence']['subtitle'] = [
      '#type' => 'select',
      '#title' => $this->t('Subtitle Weight'),
      '#default_value' => $config['sequence']['subtitle'] ?? '3',
      '#options' => [
        '1' => $this->t('One'),
        '2' => $this->t('Two'),
        '3' => $this->t('Three'),
        '4' => $this->t('Four'),
        '5' => $this->t('Five'),
      ],
    ];
    $form['sequence']['description'] = [
      '#type' => 'select',
      '#title' => $this->t('Descrition Weight'),
      '#default_value' => $config['sequence']['description'] ?? '4',
      '#options' => [
        '1' => $this->t('One'),
        '2' => $this->t('Two'),
        '3' => $this->t('Three'),
        '4' => $this->t('Four'),
        '5' => $this->t('Five'),
      ],
    ];
    $form['sequence']['cta'] = [
      '#type' => 'select',
      '#title' => $this->t('CTA Weight'),
      '#default_value' => $config['sequence']['cta'] ?? '5',
      '#options' => [
        '1' => $this->t('One'),
        '2' => $this->t('Two'),
        '3' => $this->t('Three'),
        '4' => $this->t('Four'),
        '5' => $this->t('Five'),
      ],
    ];
    $form['supercharged_card'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('Cards'),
      '#prefix' => '<div id="supercharged-card-wrapper">',
      '#suffix' => '</div>',
    ];
    // Add more start.
    $submitted_input = $form_state->getUserInput()['settings'] ?? [];
    $saved_items = !empty($config['supercharged_card']) ? $config['supercharged_card'] : [];
    $submitted_items = $submitted_input['supercharged_card'] ?? [];
    $current_items_state = $form_state->get('supercharged_card_storage');

    if (empty($current_items_state)) {
      if (!empty($submitted_items)) {
        $current_items_state = $submitted_items;
      }
      else {
        $current_items_state = $saved_items;
      }
    }

    $form_state->set('supercharged_card_storage', $current_items_state);
    foreach ($current_items_state as $key => $value) {
      $form['supercharged_card'][$key] = [
        '#type' => 'details',
        '#title' => $this->t('Card items'),
        '#open' => TRUE,
      ];
      // Block Alignment.
      $form['supercharged_card'][$key]['block_aligned'] = [
        '#type' => 'select',
        '#title' => $this->t('Block aligned'),
        '#default_value' => $config['supercharged_card'][$key]['block_aligned'],
        '#options' => [
          self::TOP_ALIGNED => $this->t('Top aligned'),
          self::BOTTOM_ALIGNED => $this->t('Bottom aligned'),
        ],
      ];
      // With CTA.
      $form['supercharged_card'][$key]['with_cta'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('With/without CTA'),
        '#default_value' => $config['supercharged_card'][$key]['with_cta'] ?? FALSE,
      ];
      $form['supercharged_card'][$key]['cta_up_down'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Align CTA in two lines'),
        '#default_value' => $config['supercharged_card'][$key]['cta_up_down'] ?? FALSE,
        '#states' => [
          'visible' => [
            [':input[name="settings[supercharged_card][' . $key . '][with_cta]"]' => ['checked' => TRUE]],
          ],
        ],
      ];
      // Eyebrow.
      $form['supercharged_card'][$key]['eyebrow'] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#tree' => TRUE,
        '#title' => $this->t('Eyebrow'),
      ];
      $form['supercharged_card'][$key]['eyebrow']['value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Eyebrow'),
        '#default_value' => $config['supercharged_card'][$key]['eyebrow']['value'] ?? '',
        '#maxlength' => !empty($character_limit_config->get('supercharged_card_component_eyebrow')) ? $character_limit_config->get('supercharged_card_component_eyebrow') : 35,
      ];
      // Title/Icon.
      $form['supercharged_card'][$key]['title_icon'] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#tree' => TRUE,
        '#title' => $this->t('Title/Icon'),
      ];
      $form['supercharged_card'][$key]['title_icon']['icon_toggle'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable icon/image'),
        '#default_value' => $config['supercharged_card'][$key]['title_icon']['icon_toggle'] ?? FALSE,
      ];

      $form['supercharged_card'][$key]['title_icon']['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('TItle'),
        '#default_value' => $config['supercharged_card'][$key]['title_icon']['title'] ?? '',
        '#maxlength' => !empty($character_limit_config->get('supercharged_card_component_title')) ? $character_limit_config->get('supercharged_card_component_title') : 30,
        '#states' => [
          'visible' => [
            [':input[name="settings[supercharged_card][' . $key . '][title_icon][icon_toggle]"]' => ['checked' => FALSE]],
          ],
        ],
      ];
      // Entity Browser element for icon.
      $form['supercharged_card'][$key]['title_icon']['icon_image'] = $this->getEntityBrowserForm(self::LIGHTHOUSE_ENTITY_BROWSER_ID,
        $config['supercharged_card'][$key]['title_icon']['icon_image'], $form_state, 1, 'thumbnail', FALSE);
      $form['supercharged_card'][$key]['title_icon']['icon_image']['#type'] = 'details';
      $form['supercharged_card'][$key]['title_icon']['icon_image']['#title'] = $this->t('Icon/Image');
      $form['supercharged_card'][$key]['title_icon']['icon_image']['#open'] = TRUE;
      $form['supercharged_card'][$key]['title_icon']['icon_image']['#required'] = TRUE;
      $form['supercharged_card'][$key]['title_icon']['icon_image']['#states'] = [
        'visible' => [
          [':input[name="settings[supercharged_card][' . $key . '][title_icon][icon_toggle]"]' => ['checked' => TRUE]],
        ],
      ];
      // Sub title.
      $form['supercharged_card'][$key]['subtitle'] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#tree' => TRUE,
        '#title' => $this->t('Subtitle'),
      ];
      $form['supercharged_card'][$key]['subtitle']['value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subtitle'),
        '#default_value' => $config['supercharged_card'][$key]['subtitle']['value'] ?? '',
        '#maxlength' => !empty($character_limit_config->get('supercharged_card_component_subtitle')) ? $character_limit_config->get('supercharged_card_component_subtitle') : 32,
      ];
      // Description.
      $form['supercharged_card'][$key]['description'] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#tree' => TRUE,
        '#title' => $this->t('Description'),
      ];
      $form['supercharged_card'][$key]['description']['value'] = [
        '#type' => 'text_format',
        '#format' => 'rich_text',
        '#title' => $this->t('Description'),
        '#default_value' => $config['supercharged_card'][$key]['description']['value']['value'] ?? '',
        '#maxlength' => !empty($character_limit_config->get('supercharged_card_component_description')) ? $character_limit_config->get('supercharged_card_component_description') : 120,
      ];
      // Link/button.
      $form['supercharged_card'][$key]['link_button'] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#tree' => TRUE,
        '#title' => $this->t('Link/Button'),
        '#states' => [
          'visible' => [
            [':input[name="settings[supercharged_card][' . $key . '][with_cta]"]' => ['checked' => TRUE]],
          ],
        ],
      ];
      // Link/button 1.
      $form['supercharged_card'][$key]['link_button']['link_button_1'] = [
        '#type' => 'fieldset',
        '#tree' => TRUE,
        '#title' => $this->t('CTA 1'),
        '#states' => [
          'visible' => [
            [':input[name="settings[supercharged_card][' . $key . '][with_cta]"]' => ['checked' => TRUE]],
          ],
        ],
      ];
      $form['supercharged_card'][$key]['link_button']['link_button_1']['cta_title_1'] = [
        '#type' => 'textfield',
        '#title' => $this->t('CTA Link Title'),
        '#default_value' => $config['supercharged_card'][$key]['link_button']['link_button_1']['cta_title_1'] ?? '',
        '#maxlength' => !empty($character_limit_config->get('supercharged_card_component_link_title')) ? $character_limit_config->get('supercharged_card_component_link_title') : 15,
        '#states' => [
          'visible' => [
            [':input[name="settings[supercharged_card][' . $key . '][with_cta]"]' => ['checked' => TRUE]],
          ],
        ],
      ];
      $form['supercharged_card'][$key]['link_button']['link_button_1']['cta_url_1'] = [
        '#type' => 'textfield',
        '#title' => $this->t('CTA Link URL'),
        '#description' => $this->t('Please check if string starts with: "/", "http://", "https://".'),
        '#maxlength' => !empty($character_limit_config->get('supercharged_card_component_link_url')) ? $character_limit_config->get('supercharged_card_component_link_url') : 2048,
        '#default_value' => $config['supercharged_card'][$key]['link_button']['link_button_1']['cta_url_1'] ?? '',
        '#states' => [
          'visible' => [
            [':input[name="settings[supercharged_card][' . $key . '][with_cta]"]' => ['checked' => TRUE]],
          ],
        ],
      ];
      $form['supercharged_card'][$key]['link_button']['link_button_1']['cta_new_window'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Open CTA link in a new tab'),
        '#default_value' => $config['supercharged_card'][$key]['link_button']['link_button_1']['cta_new_window'] ?? FALSE,
        '#states' => [
          'visible' => [
            [':input[name="settings[supercharged_card][' . $key . '][with_cta]"]' => ['checked' => TRUE]],
          ],
        ],
      ];
      $form['supercharged_card'][$key]['link_button']['link_button_1']['use_link_button'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display CTA link as button'),
        '#default_value' => $config['supercharged_card'][$key]['link_button']['link_button_1']['use_link_button'] ?? FALSE,
        '#states' => [
          'visible' => [
            [':input[name="settings[supercharged_card][' . $key . '][with_cta]"]' => ['checked' => TRUE]],
          ],
        ],
      ];
      // Link/button 2.
      $form['supercharged_card'][$key]['link_button']['link_button_2'] = [
        '#type' => 'fieldset',
        '#tree' => TRUE,
        '#title' => $this->t('CTA 2'),
      ];
      $form['supercharged_card'][$key]['link_button']['link_button_2']['cta_title_2'] = [
        '#type' => 'textfield',
        '#title' => $this->t('CTA Link Title'),
        '#default_value' => $config['supercharged_card'][$key]['link_button']['link_button_2']['cta_title_2'] ?? '',
        '#maxlength' => !empty($character_limit_config->get('supercharged_card_component_link_title')) ? $character_limit_config->get('supercharged_card_component_link_title') : 15,
        '#states' => [
          'visible' => [
            [':input[name="settings[supercharged_card][' . $key . '][with_cta]"]' => ['checked' => TRUE]],
          ],
        ],
      ];
      $form['supercharged_card'][$key]['link_button']['link_button_2']['cta_url_2'] = [
        '#type' => 'textfield',
        '#title' => $this->t('CTA Link URL'),
        '#description' => $this->t('Please check if string starts with: "/", "http://", "https://".'),
        '#maxlength' => !empty($character_limit_config->get('supercharged_card_component_link_url')) ? $character_limit_config->get('supercharged_card_component_link_url') : 2048,
        '#default_value' => $config['supercharged_card'][$key]['link_button']['link_button_2']['cta_url_2'] ?? '',
        '#states' => [
          'visible' => [
            [':input[name="settings[supercharged_card][' . $key . '][with_cta]"]' => ['checked' => TRUE]],
          ],
        ],
      ];
      $form['supercharged_card'][$key]['link_button']['link_button_2']['cta_new_window'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Open CTA link in a new tab'),
        '#default_value' => $config['supercharged_card'][$key]['link_button']['link_button_2']['cta_new_window'] ?? FALSE,
        '#states' => [
          'visible' => [
            [':input[name="settings[supercharged_card][' . $key . '][with_cta]"]' => ['checked' => TRUE]],
          ],
        ],
      ];
      $form['supercharged_card'][$key]['link_button']['link_button_2']['use_link_button'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display CTA link as button'),
        '#default_value' => $config['supercharged_card'][$key]['link_button']['link_button_2']['use_link_button'] ?? FALSE,
        '#states' => [
          'visible' => [
            [':input[name="settings[supercharged_card][' . $key . '][with_cta]"]' => ['checked' => TRUE]],
          ],
        ],
      ];
      // Background image.
      $form['supercharged_card'][$key]['image'] = $this->getEntityBrowserForm(self::LIGHTHOUSE_ENTITY_BROWSER_ID,
        $config['supercharged_card'][$key]['image'], $form_state, 1, 'thumbnail', TRUE);
      $form['supercharged_card'][$key]['image']['#type'] = 'details';
      $form['supercharged_card'][$key]['image']['#title'] = $this->t('Background Image');
      $form['supercharged_card'][$key]['image']['#open'] = TRUE;
      // Remove card item.
      $form['supercharged_card'][$key]['remove_item'] = [
        '#type' => 'submit',
        '#name' => 'supercharged_card_' . $key,
        '#value' => $this->t('Remove card item'),
        '#ajax' => [
          'callback' => [$this, 'ajaxRemoveCardItemCallback'],
          'wrapper' => 'supercharged-card-wrapper',
        ],
        '#limit_validation_errors' => [],
        '#submit' => [[$this, 'removeCardItemSubmitted']],
      ];
    }
    $form['supercharged_card']['add_item'] = [
      '#type'  => 'submit',
      '#name'  => 'supercharged_card_add_item',
      '#value' => $this->t('Add new card item'),
      '#ajax'  => [
        'callback' => [$this, 'ajaxAddCardItemCallback'],
        'wrapper'  => 'supercharged-card-wrapper',
      ],
      '#limit_validation_errors' => [],
      '#submit' => [[$this, 'addCardItemSubmitted']],
    ];
    return $form;
  }

  /**
   * Add new card item callback.
   *
   * @param array $form
   *   Theme settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Theme settings form state.
   *
   * @return array
   *   List container of configuration settings.
   */
  public function ajaxAddCardItemCallback(array $form, FormStateInterface $form_state) {
    return $form['settings']['supercharged_card'];
  }

  /**
   * Add remove card item callback.
   *
   * @param array $form
   *   Theme settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Theme settings form state.
   *
   * @return array
   *   List container of configuration settings.
   */
  public function ajaxRemoveCardItemCallback(array $form, FormStateInterface $form_state) {
    return $form['settings']['supercharged_card'];
  }

  /**
   * Custom submit card configuration settings form.
   *
   * @param array $form
   *   Theme settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Theme settings form state.
   */
  public function addCardItemSubmitted(
    array $form,
    FormStateInterface $form_state
  ) {
    $storage = $form_state->get('supercharged_card_storage');
    array_push($storage, 1);
    $form_state->set('supercharged_card_storage', $storage);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Custom submit card configuration settings form.
   *
   * @param array $form
   *   Theme settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Theme settings form state.
   */
  public function removeCardItemSubmitted(
    array $form,
    FormStateInterface $form_state
  ) {
    $triggered = $form_state->getTriggeringElement();
    if (isset($triggered['#parents'][3]) && $triggered['#parents'][3] == 'remove_item') {
      $supercharged_card_storage = $form_state->get('supercharged_card_storage');
      $id = $triggered['#parents'][2];
      unset($supercharged_card_storage[$id]);
      $form_state->set('supercharged_card_storage', $supercharged_card_storage);
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    unset($values['supercharged_card']['add_item']);
    $this->setConfiguration($values);
    if (isset($values['supercharged_card']) && !empty($values['supercharged_card'])) {
      foreach ($values['supercharged_card'] as $key => $item) {
        // Image.
        $this->configuration['supercharged_card'][$key]['image'] = $this->getEntityBrowserValue($form_state, [
          'supercharged_card',
          $key,
          'image',
        ]);
        // Icone/Image.
        $this->configuration['supercharged_card'][$key]['title_icon']['icon_image'] = $this->getEntityBrowserValue($form_state, [
          'supercharged_card',
          $key,
          'title_icon',
          'icon_image',
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $supercharged_card_storage = $form_state->get('supercharged_card_storage');
    $values = $form_state->getValues();
    $sequence = [];
    if (!empty($supercharged_card_storage)) {
      foreach ($supercharged_card_storage as $key => $supercharged_card) {
        $title_toggle = $values['supercharged_card'][$key]['title_icon']['icon_toggle'];
        $title = $values['supercharged_card'][$key]['title_icon']['title'];
        // Title validation.
        if ($title_toggle == 0 && !$title && empty($title)) {
          $form_state->setErrorByName('supercharged_card][' . $key . '][title_icon][title', $this->t('Title label is required'));
        }
        // Icon.
        $icon_id = $values['supercharged_card'][$key]['title_icon']['icon_image']['selected'];
        if ($title_toggle == 1 && !$icon_id && empty($icon_id)) {
          $form_state->setErrorByName('supercharged_card][' . $key . '][title_icon][icon_image', $this->t('Icon/Image field is required'));
        }
      }
    }
    // weight.
    $sequence['eyebrow'] = $form_state->getValue('sequence')['eyebrow'];
    $sequence['title_icon'] = $form_state->getValue('sequence')['title_icon'];
    $sequence['subtitle'] = $form_state->getValue('sequence')['subtitle'];
    $sequence['description'] = $form_state->getValue('sequence')['description'];
    $sequence['cta'] = $form_state->getValue('sequence')['cta'];
    $sequence_unique = array_unique($sequence);
    $sequence_duplicates = array_diff_assoc($sequence, $sequence_unique);
    foreach ($sequence_duplicates as $sequence_duplicates_keys => $sequence_duplicates_value) {
      if ($sequence_duplicates_keys == 'eyebrow') {
        $form_state->setErrorByName('sequence][eyebrow', $this->t('Fields has same sequence. Please change the value of weight field.'));
      }
      if ($sequence_duplicates_keys == 'title_icon') {
        $form_state->setErrorByName('sequence][title_icon', $this->t('Fields has same sequence. Please change the value of weight field.'));
      }
      if ($sequence_duplicates_keys == 'subtitle') {
        $form_state->setErrorByName('sequence][subtitle', $this->t('Fields has same sequence. Please change the value of weight field.'));
      }
      if ($sequence_duplicates_keys == 'description') {
        $form_state->setErrorByName('sequence][description', $this->t('Fields has same sequence. Please change the value of weight field.'));
      }
      if ($sequence_duplicates_keys == 'cta') {
        $form_state->setErrorByName('sequence][cta', $this->t('Fields has same sequence. Please change the value of weight field.'));
      }
    }
  }

}
