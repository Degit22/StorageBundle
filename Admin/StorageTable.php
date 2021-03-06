<?php

namespace Degit22\StorageBundle\Admin;

use Creonit\AdminBundle\Component\Scope\Scope;
use Degit22\StorageBundle\Model\StorageQuery;
use Creonit\AdminBundle\Component\Request\ComponentRequest;
use Creonit\AdminBundle\Component\Response\ComponentResponse;
use Creonit\AdminBundle\Component\TableComponent;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\HttpFoundation\ParameterBag;

class StorageTable extends TableComponent
{

    /**
     * @title Блоки
     * @header
     * {{ button('Добавить блок', {size: 'sm', type: 'success', icon: 'puzzle-piece'}) | open('Storage.StorageEditor') }}
     * {{ button('Добавить секцию', {size: 'sm', type: 'success', icon: 'folder-o'}) | open('Storage.StorageSectionEditor') }}
     *
     * @action copy(options){
     *      var $row = this.findRowById(options.rowId);
     *
     *      this.request('copy', $.extend({storage_id: options.key}, this.getQuery()), {state: $row.hasClass('success')});
     *      this.loadData();
     * }
     *
     *
     * @cols Название, Индентификатор, ., .
     *
     * \StorageSection
     * @entity Degit22\StorageBundle\Model\StorageSection
     * @field title
     * @col {{ title | icon('folder-o') | open('Storage.StorageSectionEditor', {key: _key}) | controls }}
     * @col
     * @col {{ buttons(_delete()) }}
     * @sortable true
     * @collapsed true
     *
     * \Storage
     * @entity Degit22\StorageBundle\Model\Storage
     * @relation section_id > StorageSection.id
     * @field title
     * @field multiresult
     * @sortable true
     *
     *
     * @col
     * {% if multiresult %}
     *      {{ (title | icon('puzzle-piece') | open('Storage.StorageMultiresultTable', {key: _key})) | controls(buttons(button('', {icon: 'edit', size: 'xs'}) | open('Storage.StorageEditor', {key: _key}) ~ button('', {icon: 'clone', size: 'xs'}) | action('copy', {key: _key, rowId: _row_id}) )) }}
     * {% else %}
     *      {{ (title | icon('puzzle-piece') | open('Storage.StorageFillEditor', {key: _key})) | controls(buttons(button('', {icon: 'edit', size: 'xs'}) | open('Storage.StorageEditor', {key: _key}) ~ button('', {icon: 'clone', size: 'xs'}) | action('copy', {key: _key, rowId: _row_id}) )) }}
     * {% endif %}
     * @col {{ code }}
     * @col {{ buttons(_visible() ~ _delete()) }}
     *
     */
    public function schema()
    {
        $this->addHandler('copy', function (ComponentRequest $request, ComponentResponse $response) {
            $block = StorageQuery::create()->findPk($request->query->get('storage_id')) or $response->flushError('Блок не найден');
            $last = StorageQuery::create()->filterByStorageSection($block->getStorageSection())->orderBySortableRank(Criteria::DESC)->findOne();

            $copy = $block->copy(true);
            $copy
                ->setTitle($block->getTitle() . ' (Копия)')
                ->setCode($block->getCode() . '_copy')
                ->setSortableRank($last->getSortableRank() + 1)
                ->save();
        });
    }

    protected function loadData(ComponentRequest $request, ComponentResponse $response)
    {
        foreach ($this->fields as $field) {
            $response->data->set($field->getName(), $field->decorate($request->query->get($field->getName())));
        }

        foreach($this->scopes as $scope){
            if($scope->isIndependent()){
                //var_dump($scope->isRecursive());
                if($scope->isRecursive()){
                    $this->getData($request, $response, $scope, $this->findRelations($scope, $scope)[0]);
                }else{
                    //var_dump($scope->isRecursive());
                    $this->getData($request, $response, $scope);
                }
            }
        }
    }

}
