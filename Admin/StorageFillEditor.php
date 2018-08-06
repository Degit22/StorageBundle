<?php

namespace Degit22\StorageBundle\Admin;

use Degit22\StorageBundle\Model\StorageQuery;
use Degit22\StorageBundle\Model\StorageResultQuery;
use Degit22\StorageBundle\Model\StorageResult;
use Degit22\StorageBundle\Model\StorageValue;
use Degit22\StorageBundle\Model\StorageValueQuery;
use Creonit\AdminBundle\Component\EditorComponent;
use Creonit\AdminBundle\Component\Request\ComponentRequest;
use Creonit\AdminBundle\Component\Response\ComponentResponse;

class StorageFillEditor extends EditorComponent
{

    /**
     * @title Блок
     * @entity Storage
     *
     * @template
     * {% for field in fields %}
     * {% if field.type == 'text' %}
     *      {{ field.value | text('field_'~field.id) | group(field.title) }}
     * {% elseif field.type == 'textarea' %}
     *      {{ field.value | textarea('field_'~field.id) | group(field.title) }}
     * {% elseif field.type == 'textedit' %}
     *      {{ field.value | textedit('field_'~field.id) | group(field.title) }}
     * {% elseif field.type == 'image' %}
     *      {{ field.value | image('field_'~field.id) | group(field.title) }}
     * {% elseif field.type == 'file' %}
     *      {{ field.value | file('field_'~field.id) | group(field.title) }}
     * {% elseif field.type == 'video' %}
     *      {{ field.value | video('field_'~field.id) | group(field.title) }}
     * {% elseif field.type == 'checkbox' %}
     *      {{ field.value | checkbox('field_'~field.id, field.title) | group }}
     * {% endif %}
     *
     * {% endfor %}
     *
     */
    public function schema()
    {
    }

    /**
     * @param ComponentRequest $request
     * @param ComponentResponse $response
     */
    public function loadData(ComponentRequest $request, ComponentResponse $response)
    {
        if ($request->query->get('storage_id')) {
            $storage = StorageQuery::create()->findPk($request->query->get('storage_id'));
            $result = StorageResultQuery::create()->findPk($request->query->get('key'));
        } else {
            $storage = StorageQuery::create()->findPk($request->query->get('key'));
            $result = $storage->getResult();
        }

        $out = [];
        foreach ($storage->getFields() as $field) {
            $fieldName = 'text';
            if ($field->getTypeCode() == 'image') $fieldName = 'image_id';
            if ($field->getTypeCode() == 'file') $fieldName = 'file_id';
            if ($field->getTypeCode() == 'video') $fieldName = 'video_id';
            if ($field->getTypeCode() == 'checkbox') $fieldName = 'bool';

            $componentFieldType = 'default';
            if ($field->getTypeCode() == 'image') $componentFieldType = 'image';
            if ($field->getTypeCode() == 'file') $componentFieldType = 'file';
            if ($field->getTypeCode() == 'video') $componentFieldType = 'video';
            if ($field->getTypeCode() == 'checkbox') $componentFieldType = 'checkbox';

            $componentField = $this->createField($fieldName, [], $componentFieldType);

            if ($result) {
                $value = $componentField->load(StorageValueQuery::create()
                    ->filterByStorageResult($result)
                    ->filterByStorageField($field)
                    ->findOne() ?: new StorageValue());
            } else {
                $value = '';
            }

            $out[] = [
                'title' => $field->getTitle(),
                'id' => $field->getId(),
                'type' => $field->getTypeCode(),
                'value' => $value
            ];
        }

        $response->data->set('fields', $out);
    }

    /**
     * @param ComponentRequest $request
     * @param ComponentResponse $response
     */
    public function saveData(ComponentRequest $request, ComponentResponse $response)
    {

        if ($request->query->get('storage_id')) {
            $storage = StorageQuery::create()->findPk($request->query->get('storage_id'));
            if (!$result = StorageResultQuery::create()->findPk($request->query->get('key'))) {
                $result = new StorageResult();
                $result
                    ->setStorage($storage)
                    ->save();
            }

        } else {
            $storage = StorageQuery::create()->findPk($request->query->get('key'));

            if (!$result = $storage->getResult()) {
                $result = new StorageResult();
                $result
                    ->setStorage($storage)
                    ->save();

            }
        }

        foreach ($storage->getFields() as $field) {
            $fieldId = 'field_' . $field->getId();

            if (!$resultValue = StorageValueQuery::create()->filterByFieldId($field->getId())->filterByStorageResult($result)->findOne()) {
                $resultValue = new StorageValue();
            }

            $componentFieldType = 'default';
            if ($field->getTypeCode() == 'image') $componentFieldType = 'image';
            if ($field->getTypeCode() == 'file') $componentFieldType = 'file';
            if ($field->getTypeCode() == 'video') $componentFieldType = 'video';
            if ($field->getTypeCode() == 'checkbox') $componentFieldType = 'checkbox';

            $componentField = $this->createField($fieldId, [], $componentFieldType);
            $data = $componentField->extract($request);

            $fieldName = 'text';
            if ($field->getTypeCode() == 'image') $fieldName = 'image_id';
            if ($field->getTypeCode() == 'file') $fieldName = 'file_id';
            if ($field->getTypeCode() == 'video') $fieldName = 'video_id';
            if ($field->getTypeCode() == 'checkbox') $fieldName = 'bool';

            $componentField->setName($fieldName);
            $componentField->save($resultValue, $data);


            $resultValue->setStorageField($field)->setStorageResult($result)->save();
        }

        $this->container->get('app.storage')->clearStorageDataCache($storage->getCode());

        $response->sendSuccess();
    }

}
