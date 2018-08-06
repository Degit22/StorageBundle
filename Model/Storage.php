<?php

namespace Degit22\StorageBundle\Model;

use Degit22\StorageBundle\Model\Base\Storage as BaseStorage;

/**
 * Skeleton subclass for representing a row from the 'storage' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Storage extends BaseStorage
{

    public function getFields()
    {
        return StorageFieldQuery::create()->filterByStorage($this)->orderBySortableRank()->find();
    }

    public function getResult($params = [])
    {
        $query = StorageResultQuery::create()->filterByStorageId($this->id);
        if ($params) {
            //$query->filterBySomeThing();
        }

        $result = $query->findOne();

        if (!$result) {
            $result = new StorageResult();
            $result->setStorageId($this->id);
            if ($params) {
                //$result->setSomeThing();
            }

            $result->save();

        }

        return $result;
    }

    public function getResults()
    {
        return StorageResultQuery::create()->filterByStorage($this)->orderBySortableRank()->filterByVisible(true)->find();
    }

    public function getResultFields($params = [])
    {
        $result = $this->getResult($params);
        foreach ($result->getStorageValuesJoinStorageField() as $item) {
            $item->getStorageField()->getTitle();
            $item->getValue();
        }
    }

    protected $results = [];

    public function getValue($key, $params = [], $default = null)
    {
        $resultIndex = serialize($params);
        if (!isset($this->results[$resultIndex])) {
            $this->results[$resultIndex]['result'] = $this->getResult($params);
        }

        if (!isset($this->results[$resultIndex][$key])) {
            $field = StorageFieldQuery::create()->filterByStorageId($this->id)->filterByCode($key)->findOne();
            if (!$field) {
                $this->results[$resultIndex][$key] = $default;
                return $this->results[$resultIndex][$key];
            }
            $fieldValue = StorageValueQuery::create()->filterByResultId($this->results[$resultIndex]['result']->getId())->filterByFieldId($field->getId())->findOne();
            if (!$fieldValue) {
                $this->results[$resultIndex][$key] = $default;
                return null;
            }

            $this->results[$resultIndex][$key] = $fieldValue->getValue($params);

        }

        return $this->results[$resultIndex][$key];
    }


}
