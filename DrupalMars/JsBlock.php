<?php

namespace Drupal\mars_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a javascript code component block.
 *
 * @Block(
 *   id = "js_block",
 *   admin_label = @Translation("MARS: Js block"),
 *   category = @Translation("Mars Common"),
 * )
 */
class JsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['js_code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Javascript code'),
      '#default_value' => $config['js_code'] ?? '',
      '#description' => $this->t('Enter js code without "script" tags. Example: el.addSmth(s).'),
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

    $build['#js_code'] = '<script>' . $config['js_code'] . '</script>';
    $build['#theme'] = 'js_block';

    return $build;
  }

}
