<?php

namespace Degit22\StorageBundle\Admin;

use Degit22\StorageBundle\Model\StorageResultQuery;
use Creonit\AdminBundle\Component\Request\ComponentRequest;
use Creonit\AdminBundle\Component\Response\ComponentResponse;
use Creonit\AdminBundle\Component\Scope\Scope;
use Creonit\AdminBundle\Component\TableComponent;

class StorageMultiresultTable extends TableComponent
{

    /**
     * @title Коллекция элементов
     * @header
     * {{ button('Добавить элемент', {size: 'sm', type: 'success', icon: 'puzzle-piece'}) | open('Storage.StorageFillEditor' , {storage_id: _query.key}) }}
     *
     * @cols Название, .
     *
     * \StorageResult
     * @field title {load: 'entity.getTitle()'}
     * @sortable true
     *
     *
     * @col {{ (title | icon('puzzle-piece') | open('Storage.StorageFillEditor', {key: _key, storage_id: _query.key })) | controls }}
     * @col {{ buttons(_visible() ~ _delete()) }}
     *
     */
    public function schema()
    {
    }

    /**
     * @param ComponentRequest $request
     * @param ComponentResponse $response
     * @param StorageResultQuery $query
     * @param Scope $scope
     * @param $relation
     * @param $relationValue
     * @param $level
     */
    public function filter(ComponentRequest $request, ComponentResponse $response, $query, Scope $scope, $relation, $relationValue, $level)
    {
        $query->filterByStorageId($request->query->get('key'));
    }

}
