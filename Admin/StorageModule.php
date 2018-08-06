<?php

namespace Creonit\StorageBundle\Admin;

use Creonit\AdminBundle\Module;

class StorageModule extends Module
{

    protected function configure()
    {
        $this
            ->setTitle('Управление контентом')
            ->setIcon('list')
            ->setTemplate('StorageTable')
            ->setPermission('ROLE_ADMIN_STORAGE');
    }

    public function initialize()
    {
        $this->addComponent(new StorageTable());
        $this->addComponent(new StorageEditor());
        $this->addComponent(new StorageFieldTable());
        $this->addComponent(new StorageFieldEditor());
        $this->addComponent(new StorageFillEditor());
        $this->addComponent(new StorageMultiresultTable());
        $this->addComponent(new StorageSectionEditor());
    }

}
