<?php

namespace Drupal\mars_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\mars_common\ThemeConfiguratorParser;
use Drupal\mars_common\Traits\OverrideThemeTextColorTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a feedback block.
 *
 * @Block(
 *   id = "feedback_block",
 *   admin_label = @Translation("MARS: Feedback Block"),
 *   category = @Translation("Mars Common")
 * )
 */
class FeedbackBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use OverrideThemeTextColorTrait;

  /**
   * Entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $pollEntityStorage;

  /**
   * View builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $pollViewBuilder;

  /**
   * ThemeConfiguratorParser.
   *
   * @var \Drupal\mars_common\ThemeConfiguratorParser
   */
  protected $themeConfiguratorParser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
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
    EntityTypeManagerInterface $entity_type_manager,
    ThemeConfiguratorParser $themeConfiguratorParser
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pollEntityStorage = $entity_type_manager->getStorage('poll');
    $this->pollViewBuilder = $entity_type_manager->getViewBuilder('poll');
    $this->themeConfiguratorParser = $themeConfiguratorParser;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'label_display' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $conf = $this->getConfiguration();
    if (!$conf['poll']) {
      return [];
    }

    $pollEntity = $this->pollEntityStorage->load($conf['poll']);
    if (!$pollEntity) {
      return [];
    }

    $build['#poll'] = $this->pollViewBuilder->view($pollEntity);
    $text_color_override = FALSE;
    if (!empty($conf['override_text_color']['override_color'])) {
      $text_color_override = static::$overrideColor;
    }
    $build['#text_color_override'] = $text_color_override;
    $build['#theme'] = 'poll_block';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $conf = $this->getConfiguration();

    $form['poll'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Poll Entity'),
      '#target_type' => 'poll',
      '#selection_handler' => 'default:poll_by_field',
      '#selection_settings' => [
        'filter' => ['field_type' => 'feedback'],
      ],
      '#default_value' => $this->getPollEntity(),
      '#required' => TRUE,
    ];

    $this->buildOverrideColorElement($form, $conf);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['poll'] = $form_state->getValue('poll');
    $this->configuration['override_text_color'] = $form_state->getValue('override_text_color');
  }

  /**
   * Returns the poll entity that's saved to the block.
   */
  private function getPollEntity(): ?EntityInterface {
    $pollEntityId = $this->getConfiguration()['poll'] ?? NULL;
    if (!$pollEntityId) {
      return NULL;
    }

    return $this->pollEntityStorage->load($pollEntityId);
  }

}
