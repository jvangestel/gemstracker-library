<?php

namespace Gems\Model\Transform;

use MUtil\Registry\TargetTrait;

class TranslateDatabaseFields extends \MUtil_Model_ModelTransformerAbstract implements \MUtil_Registry_TargetInterface
{
    use TargetTrait;

    /**
     * @var \Zend_Cache_Core
     */
    protected $cache;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * @var \Zend_Locale
     */
    public $locale;

    /**
     * @var \Gems_Project_ProjectSettings
     */
    public $project;

    protected $tableKeys = [];

    protected $translateTables = [];

    protected $translations;

    public function __construct($config=null)
    {
        if ($config) {
            $this->config = $config;
        }
    }

    public function getFieldInfo(\MUtil_Model_ModelAbstract $model)
    {
        //$labeledNames = $model->getColNames('label');
        $tablesWithTranslations = $this->getTablesWithTranslations();

        if ($model instanceof \MUtil_Model_UnionModel) {
            $tableKeys = $model->getKeys();
            $table = null;
            foreach($tableKeys as $columnName) {
                $item = $model->get($columnName);

                if (isset($item['table']) && !in_array($item['table'], $this->translateTables) && isset($tablesWithTranslations[$item['table']])) {
                    $this->translateTables[] = $item['table'];
                    $table = $item['table'];
                }

            }
            if ($table !== null) {
                $this->tableKeys[$table] = $tableKeys;
            }
            $itemNames = $model->getColNames('table');
            foreach ($itemNames as $itemName) {
                $item = $model->get($itemName);
                if (!isset($item['table']) || !$item['table'] || !isset($tablesWithTranslations[$item['table']])) {
                    continue;
                }
                if (!in_array($item['table'], $this->translateTables)) {
                    $this->translateTables[] = $item['table'];
                }
                if (!isset($this->tableKeys[$item['table']])) {
                    $this->tableKeys[$item['table']] = $tableKeys;
                }
            }
        }

        if ($model instanceof \MUtil_Model_DatabaseModelAbstract) {
            $itemNames = $model->getColNames('table');
            foreach ($itemNames as $itemName) {
                $item = $model->get($itemName);
                if (!isset($item['table']) || !$item['table'] || !isset($tablesWithTranslations[$item['table']])) {
                    continue;
                }
                if (!in_array($item['table'], $this->translateTables)) {
                    $this->translateTables[] = $item['table'];
                }
                if (isset($item['key']) && $item['key'] && (!isset($tableKeys[$item['table']]) || !in_array($item['key'], $tableKeys[$item['table']]))) {
                    $this->tableKeys[$item['table']][] = $itemName;
                }
            }
        }

        return parent::getFieldInfo($model); // TODO: Change the autogenerated stub
    }

    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (is_array($this->tableKeys)) {
            foreach($this->tableKeys as $tableName => $itemNames) {
                foreach($itemNames as $itemName) {
                    // Makes the key column available in the query
                    $model->get($itemName, 'label');
                }
            }
        }

        return parent::transformFilter($model, $filter);
    }

    protected function translateData(\MUtil_Model_ModelAbstract $model, $data)
    {
        $tablesWithTranslations = $this->getTablesWithTranslations();
        $translations = $this->getTranslations();
        foreach($this->translateTables as $tableName) {
            if (isset($tablesWithTranslations[$tableName])) {
                foreach($tablesWithTranslations[$tableName] as $field) {
                    foreach($data as $key=>$row) {
                        if (isset($row[$field])) {
                            $tableKeys = $this->tableKeys[$tableName];
                            $keyValues = [];
                            foreach($tableKeys as $tableKey) {
                                $keyValues[] = $row[$tableKey];
                            }
                            $keyname = $tableName . '_' . $field . '_' .  join('_', $keyValues);
                            if (isset($translations[$keyname])) {
                                $data[$key][$field] = $translations[$keyname];
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }

    protected function getTablesWithTranslations()
    {
        $cacheId = 'dataBaseTablesWithTranslations';

        $tables = $this->cache->load($cacheId);
        if ($tables) {
            return $tables;
        }

        $select = $this->db->select();
        $select->from('gems__translations', ['gtrs_table', 'gtrs_field'])
            ->group(['gtrs_table', 'gtrs_field']);

        $rows = $this->db->fetchAll($select);

        $tables = [];
        foreach($rows as $row) {
            $tables[$row['gtrs_table']][] = $row['gtrs_field'];
        }

        $this->cache->save($tables, $cacheId, ['database_translations']);

        return $tables;
    }

    protected function getTranslations()
    {
        if (!$this->translations) {

            $cacheId = 'dataBaseTranslations' . '_' . $this->locale->getLanguage();

            $translations = $this->cache->load($cacheId);
            if ($translations) {
                return $translations;
            }

            $select = $this->db->select();
            $select->from('gems__translations', [])
                ->columns(
                    [
                        'key' => new \Zend_Db_Expr("CONCAT(gtrs_table, '_', gtrs_field, '_', gtrs_keys)"),
                        'gtrs_translation'
                    ]
                )
                ->where('gtrs_iso_lang = ?', $this->locale->getLanguage());

            $this->translations = $this->db->fetchPairs($select);

            $this->cache->save($this->translations, $cacheId, ['database_translations']);
        }

        return $this->translations;
    }



    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        if (!($model instanceof \MUtil_Model_DatabaseModelAbstract || $model instanceof \MUtil_Model_UnionModel)) {
            return $data;
        }

        $language = $this->locale->getLanguage();

        if ($language == $this->project->getLocaleDefault()) {
            return $data;
        }

        /*if ($model instanceof \MUtil_Model_JoinModel) {
            $tables = array_keys($model->getTableNames());
        } elseif ($model instanceof \MUtil_Model_TableModel) {
            $tables = $model->getTableName();
        } elseif ($model instanceof \MUtil_Model_SelectModel) {
            $select = $model->getSelect();
        }*/

        $translatedData = $this->translateData($model, $data);

        return $translatedData;
    }
}
