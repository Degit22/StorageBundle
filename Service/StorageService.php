<?php

namespace Degit22\StorageBundle\Service;

use Degit22\StorageBundle\Model\StorageQuery;
use Degit22\StorageBundle\Model\StorageResult;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StorageService implements \ArrayAccess
{

    protected $container;
    protected $cacheTime = 8640000; // 1 day

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function get($key, $arg1 = null, $arg2 = null)
    {
        $keys = explode('.', $key);

        $cache = $this->container->get('cache.app');
        $cacheKey = $this->dumpKey(func_get_args());

        /** @var CacheItem $cacheItem */
        $cacheItem = $cache->getItem($cacheKey);
        $cacheItem->expiresAfter($this->cacheTime);

        if ($cacheItem->isHit()) {
            $value = $cacheItem->get();

        } else {
            if (count($keys) == 1) {
                $value = StorageQuery::create()->filterByCode($key)->filterByVisible(true)->findOne();
            } else {
                $setting = StorageQuery::create()->filterByCode($keys[0])->filterByVisible(true)->findOne();
                if (!$setting) {
                    throw new \Exception('�� ������� ��������� ' . $key);
                }
                if (is_array($arg1)) {
                    $value = $setting->getValue($keys[1], $arg1, $arg2);
                } else {
                    $value = $setting->getValue($keys[1], [], $arg1);
                }
            }
            $cacheItem->set($value);
            $cache->save($cacheItem);
        }

        //return $this->container->get('serializer')->normalize($value);
        return $value;
    }

    public function getSectionStorages($code)
    {
        return StorageQuery::create()
            ->useStorageSectionQuery()
            ->filterByCode($code)
            ->endUse()
            ->orderBySortableRank()
            ->filterByVisible(true)
            ->find();
    }

    protected function dumpKey($args)
    {
        $output = [];
        foreach ($args as $k => $arg) {
            $output[] = $k;
            if (is_array($arg)) {
                $output[] = $this->dumpKey($arg);
            } else {
                $output[] = $arg;
            }
        }

        return implode('|', $output);
    }


    /**
     * @param StorageResult $result
     * @return \AppBundle\Model\StorageValue[]|\Propel\Runtime\Collection\ObjectCollection
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function getResultValues(StorageResult $result)
    {
        return $result->getStorageValues();
    }

    public function getStorage($storageCode)
    {
        return $this->container->get('serializer')->normalize(StorageQuery::create()->findOneByCode($storageCode));
    }

    public function generateStorageCacheKey($storageCode)
    {
        return http_build_query([__METHOD__, $storageCode]);
    }

    public function getStorageFieldData($storageCode, $fieldCode)
    {
        $storageData = $this->getStorageData($storageCode);

        if (array_key_exists($fieldCode, $storageData)) {
            return $storageData[$fieldCode];
        }

        return null;
    }

    public function getStorageData($storageCode)
    {
        $cache = $this->container->get('cache.app');
        $cacheKey = $this->generateStorageCacheKey($storageCode);

        /** @var CacheItem $cacheItem */
        $cacheItem = $cache->getItem($cacheKey);
        $cacheItem->expiresAfter($this->cacheTime);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $storage = $this->getStorage($storageCode);
        $cacheItem->set($storage);
        $cache->save($cacheItem);

        return $storage;
    }

    public function clearStorageDataCache($storageCode)
    {
        $cache = $this->container->get('cache.app');
        $cacheKey = $this->generateStorageCacheKey($storageCode);
        $cache->deleteItem($cacheKey);
    }

    public function offsetExists($offset)
    {
        return true;
    }

    public function offsetGet($offset)
    {
        if (preg_match('/^([a-z\d_-]+)\.([a-z\d_-]+)$/usi', $offset, $match)) {
            return $this->getStorageFieldData($match[1], $match[2]);
        }

        return $this->getStorageData($offset);
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset)
    {
    }

}
