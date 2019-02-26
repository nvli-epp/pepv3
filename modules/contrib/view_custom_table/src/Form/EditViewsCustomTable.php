<?php

namespace Drupal\view_custom_table\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;

/**
 * Edit views custom table form.
 */
class EditViewsCustomTable extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'view_custom_table_edit_table_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $table_name = NULL) {
    $table_info = view_custom_table_load_table_info($table_name);
    $all_database_connections = Database::getAllConnectionInfo();
    foreach ($all_database_connections as $connection_name => $connection) {
      $displyName = $connection['default']['database'];
      $databaseOptions[$connection_name] = $displyName;
    }
    $form['table_database'] = [
      '#type' => 'select',
      '#options' => $databaseOptions,
      '#title' => $this->t('Database'),
      '#default_value' => $table_info->table_database,
      '#disabled' => TRUE,
      '#description' => $this->t('Database of the table cannot be changed.'),
    ];
    $form['table_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Table'),
      '#default_value' => $table_name,
      '#disabled' => TRUE,
      '#required' => TRUE,
      '#description' => $this->t('Table name cannot be changed.'),
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => ($table_info->description != NULL) ? $table_info->description : '',
      '#rows' => 5,
      '#description' => $this->t('Maximum 255 letters are allowed.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $this->buildCancelLinkUrl(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $description = $form_state->getValue('description');
    if (strlen($description) > 254) {
      $form_state->setErrorByName('description', $this->t("Description can not be more then 255 letters. Please update it and try again."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $table_name = $form_state->getValue('table_name');
    $description = $form_state->getValue('description');
    $drupal_connection = Database::getConnection();
    $result = $drupal_connection->update('custom_table_view_data')
      ->fields([
        'description' => $description,
      ])
      ->condition('table_name', $table_name)
      ->execute();
    if ($result) {
      drupal_set_message($this->t('@table is updated.', [
        '@table' => $table_name,
      ]));
    }
    else {
      drupal_set_message($this->t('Could not update @table data, please check log messages for error.', [
        '@table' => $table_name,
      ]), 'error');
    }
    $form_state->setRedirect('view_custom_table.customtable');
  }

  /**
   * Builds the cancel link url for the form.
   *
   * @return Drupal\Core\Url
   *   Cancel url
   */
  private function buildCancelLinkUrl() {
    $query = $this->getRequest()->query;

    if ($query->has('destination')) {
      $options = UrlHelper::parse($query->get('destination'));
      $url = Url::fromUri('internal:/' . $options['path'], $options);
    }
    else {
      $url = Url::fromRoute('view_custom_table.customtable');
    }

    return $url;
  }

}
