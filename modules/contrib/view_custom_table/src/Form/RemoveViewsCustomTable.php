<?php

namespace Drupal\view_custom_table\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;

/**
 * Add views custom table form.
 */
class RemoveViewsCustomTable extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'view_custom_table_remove_table_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $table_name = NULL) {
    $form['message'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Do you want to remove @table from views custom tables ?', [
        '@table' => $table_name,
      ]),
    ];
    $form['table_name'] = [
      '#type' => 'hidden',
      '#value' => $table_name,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
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
    $table_name = $form_state->getValue('table_name');
    $connection = Database::getConnection();
    if ($connection->schema()->tableExists('custom_table_view_data')) {
      $query = $connection->select('custom_table_view_data', 'vd')
        ->fields('vd', [
          'id',
        ])
        ->condition('table_name', $table_name);
      $table_exist = $query->execute()->fetchField();
      if (!$table_exist) {
        $form_state->setErrorByName('table_name', $this->t("@table not found in views custom table records.", [
          '@table' => $table_name,
        ]));
      }

      $query = $connection->select('custom_table_view_data', 'vd')
        ->fields('vd', [
          'table_name',
        ])
        ->condition('column_relations', '%"' . $table_name . '"%', 'LIKE');
      $table_dependancy = $query->execute()->fetchCol();
      if ($table_dependancy) {
        $dependent_table_names = implode(', ', $table_dependancy);
        $form_state->setErrorByName('table_name', $this->t("@table can not be deletede because @dependent_tables are dependent on @table.", [
          '@table' => $table_name,
          '@dependent_tables' => $dependent_table_names,
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $table_name = $form_state->getValue('table_name');
    $connection = Database::getConnection();
    $result = $connection->delete('custom_table_view_data')
      ->condition('table_name', $table_name)
      ->execute();
    if ($result) {
      drupal_set_message($this->t('@table is removed from views custom table data.', [
        '@table' => $table_name,
      ]));
    }
    else {
      drupal_set_message($this->t('Could not remove table from views custom table data, please check log messages for error.'), 'error');
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
