<?php

namespace Drupal\view_custom_table\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Edit views custom table form.
 */
class EditTableRelations extends FormBase {

  /**
   * Entity Manager for calss.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityManager;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManager $entityManager) {
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'view_custom_table_edit_table_relations_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $table_name = NULL) {
    $table_info = view_custom_table_load_table_info($table_name);
    $table_relations = unserialize($table_info->column_relations);
    $properties = ['base_table' => $table_name];
    $views = $this->entityManager->getStorage('view')->loadByProperties($properties);

    $form['table_name'] = [
      '#type' => 'hidden',
      '#value' => $table_name,
    ];
    $form['columns'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('"@table" Int Type Columns', [
        '@table' => $table_name,
      ]),
      '#tree' => TRUE,
    ];

    $entities['none'] = $this->t('None');
    if ($table_info->table_database == 'default') {
      $all_entities = $this->entityManager->getDefinitions();
      foreach ($all_entities as $entity_name => $entity) {
        if ($entity->getBaseTable()) {
          $entities[$entity_name] = $entity->getLabel()->render();
        }
      }
    }

    $all_custom_tables = view_custom_table_load_tables_info($table_name, $table_info->table_database);
    if (!empty($all_custom_tables)) {
      foreach ($all_custom_tables as $custom_table) {
        $entities[$custom_table->table_name] = $custom_table->table_name;
      }
    }

    $int_types = ['tinyint', 'smallint', 'mediumint', 'int', 'bigint'];
    $text_query = format_string('DESCRIBE { @table_name }', [
      '@table_name' => $table_name,
    ]);
    $connection = Database::getConnection('default', $table_info->table_database);
    $query = $connection->query($text_query);
    foreach ($query as $row) {
      $row_type = explode('(', $row->Type);
      if (in_array($row_type[0], $int_types)) {
        $form['columns']['column_' . $row->Field] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Relation of "@field_name" with', [
            '@field_name' => ucfirst($row->Field),
          ]),
          '#tree' => TRUE,
          '#attributes' => [
            'class' => [
              'container-inline',
            ],
          ],
        ];
        $form['columns']['column_' . $row->Field]['entity'] = [
          '#type' => 'select',
          '#title' => $this->t('Entity'),
          '#options' => $entities,
        ];
        if (!empty($views) && isset($table_relations[$row->Field])) {
          $form['columns']['column_' . $row->Field]['entity']['#disabled'] = TRUE;
        }
        if (isset($table_relations[$row->Field])) {
          $form['columns']['column_' . $row->Field]['entity']['#default_value'] = $table_relations[$row->Field];
          $form['columns']['column_' . $row->Field]['entity']['#description'] = $this->t('"@table_name" is used in views, that is why this field con not be updated.', [
            '@table_name' => $table_name,
          ]);
        }
        $form['columns']['column_' . $row->Field]['field'] = [
          '#type' => 'hidden',
          '#value' => $row->Field,
        ];
      }
    }
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $table_name = $form_state->getValue('table_name');
    $relations = $form_state->getValue('columns');
    $column_relations = [];
    foreach ($relations as $relation) {
      if ($relation['entity'] != 'none') {
        $column_relations[$relation['field']] = $relation['entity'];
      }
    }
    $serialize_relations = serialize($column_relations);
    $drupal_connection = Database::getConnection();
    $result = $drupal_connection->update('custom_table_view_data')
      ->fields([
        'column_relations' => $serialize_relations,
      ])
      ->condition('table_name', $table_name)
      ->execute();
    if ($result) {
      drupal_set_message($this->t('@table relations are updated.', [
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
