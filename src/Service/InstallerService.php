<?php
/**
 * @date        02/11/2017
 * @author      Korneliusz Kirsz <kkirsz@divante.pl>
 * @copyright   Copyright (c) 2017 DIVANTE (http://divante.pl)
 */

declare(strict_types=1);

namespace PimcoreDevkitBundle\Service;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectbrickDefinition;
use Pimcore\Model\DataObject\Fieldcollection\Definition as FieldcollectionDefinition;
use Pimcore\Model\DataObject\Folder as DataObject_Folder;
use Pimcore\Model\Document\Folder as Document_Folder;
use Pimcore\Model\Asset\Folder as Asset_Folder;
use Pimcore\Model\Asset;
use Pimcore\Model\WebsiteSetting;

/**
 * Class InstallerService
 * @package PimcoreDevkitBundle\Service
 * @SuppressWarnings(PHPMD)
 */
class InstallerService
{
    /** @var array $errors */
    private $errors = [];

    /**
     * @param string $wsName
     * @param int $parentId
     * @param string $key
     * @return DataObject_Folder
     * @throws \Exception
     */
    public function createDataObjectFolderAndWebsiteSettings(string $wsName, int $parentId, string $key)
    {
        $setting = null;
        try {
            $setting = WebsiteSetting::getByName($wsName);
        } catch (\Exception $exception) {
            $this->errors[] = $exception;
        }

        if ($setting instanceof WebsiteSetting && $setting->getData()) {
            $folder = DataObject_Folder::getById($setting->getData());
            if ($folder instanceof DataObject_Folder) {
                return $folder;
            }
        }

        $dataObjectService = new DataObjectService();
        $folder = $dataObjectService->getOrCreateObjectFolder($parentId, $key);
        $this->setWebsiteSetting(
            [
                'name' => $wsName,
                'type' => 'object',
                'data' => $folder->getId(),
            ]
        );

        if (!$folder instanceof DataObject_Folder) {
            throw new \Exception("Cannot get folder $key ");
        }

        return $folder;
    }

    /**
     * @param string $wsName
     * @param int $parentId
     * @param string $key
     * @return Document_Folder
     * @throws \Exception
     */
    public function createDocumentFolderAndWebsiteSettings(string $wsName, int $parentId, string $key)
    {
        $setting = null;
        try {
            $setting = WebsiteSetting::getByName($wsName);
        } catch (\Exception $exception) {
            $this->errors[] = $exception;
        }

        if ($setting instanceof WebsiteSetting && $setting->getData()) {
            $folder = Document_Folder::getById($setting->getData());
            if ($folder instanceof Document_Folder) {
                return $folder;
            }
        }

        $documentService = new DocumentService();
        $folder = $documentService->getOrCreateDocumentFolder($parentId, $key);
        $this->setWebsiteSetting(
            [
                'name' => $wsName,
                'type' => 'document',
                'data' => $folder->getId(),
            ]
        );

        if (!$folder instanceof Document_Folder) {
            throw new \Exception("Cannot get document folder $key ");
        }

        return $folder;
    }

    /**
     * @param string $wsName
     * @param int $parentId
     * @param string $key
     * @return Asset_Folder
     * @throws \Exception
     */
    public function createAssetFolderAndWebsiteSettings(string $wsName, int $parentId, string $key)
    {
        $setting = null;
        try {
            $setting = WebsiteSetting::getByName($wsName);
        } catch (\Exception $exception) {
            $this->errors[] = $exception;
        }

        if ($setting instanceof WebsiteSetting && $setting->getData()) {
            $folder = Asset_Folder::getById($setting->getData());
            if ($folder instanceof Asset_Folder) {
                return $folder;
            }
        }

        $assetService = new AssetService();
        $folder = $assetService->getOrCreateAssetFolder($parentId, $key);
        $this->setWebsiteSetting(
            [
                'name' => $wsName,
                'type' => 'asset',
                'data' => $folder->getId(),
            ]
        );

        if (!$folder instanceof Asset) {
            throw new \Exception("Cannot get asset folder $key ");
        }

        return $folder;
    }

    /**
     * @param string $name
     * @param string $jsonFilePath
     * @return bool
     */
    public function createClassDefinition(string $name, string $jsonFilePath)
    {
        $json = file_get_contents($jsonFilePath);

        return $this->createClassDefinitionFromString($name, $json);
    }

    /**
     * @param string $name
     * @param string $json
     * @return bool
     */
    public function createClassDefinitionFromString(string $name, string $json)
    {
        $class = null;
        try {
            $class = ClassDefinition::getByName($name);
        } catch (\Exception $exception) {
            $this->errors[] = $exception;
        }
        if (false === $class instanceof ClassDefinition) {
            $class = ClassDefinition::create(['name' => $name, 'userOwner' => 0]);
        }

        $success = ClassDefinition\Service::importClassDefinitionFromJson($class, $json);

        return $success;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function removeClassDefinition(string $name)
    {
        $class = null;
        try {
            $class = ClassDefinition::getByName($name);
            if ($class) {
                $class->delete();
                return true;
            }

            return false;
        } catch (\Exception $exception) {
            $this->errors[] = $exception;
        }

        return false;
    }

    /**
     * @param string $key
     * @param string $jsonFilePath
     * @return bool
     */
    public function createObjectBrickDefinition(string $key, string $jsonFilePath)
    {
        try {
            $brick = ObjectbrickDefinition::getByKey($key);
        } catch (\Exception $exception) {
            $this->errors[] = $exception;
        }

        if (!$brick) {
            $brick = new ObjectbrickDefinition();
            $brick->setKey($key);
        }

        $json = file_get_contents($jsonFilePath);

        $success = ClassDefinition\Service::importObjectBrickFromJson($brick, $json, true);

        return $success;
    }

    /**
     * @param string $key
     * @param string $jsonFilePath
     * @return bool
     */
    public function createFieldcollectionDefinition(string $key, string $jsonFilePath): bool
    {
        try {
            $fieldcollection = FieldcollectionDefinition::getByKey($key);
        } catch (\Exception $exception) {
            $this->errors[] = $exception;
        }

        if (!$fieldcollection) {
            $fieldcollection = new FieldcollectionDefinition();
            $fieldcollection->setKey($key);
        }

        $json = file_get_contents($jsonFilePath);

        $success = ClassDefinition\Service::importFieldCollectionFromJson($fieldcollection, $json, true);

        return $success;
    }

    /**
     * Creates or updates WebsiteSettings.
     *
     * @param array $params
     * @return WebsiteSetting
     */
    public function setWebsiteSetting(array $params)
    {
        $siteId   = array_key_exists('siteId', $params) ? $params['siteId'] : null;
        $language = array_key_exists('language', $params) ? $params['language'] : null;
        $setting  = WebsiteSetting::getByName($params['name'], $siteId, $language);

        if (!$setting instanceof WebsiteSetting) {
            $setting = new WebsiteSetting();
        }

        $setting->setValues($params);
        $setting->save();

        return $setting;
    }

    /**
     * @param string $permission
     * @throws \Exception
     */
    public function createPermission(string $permission)
    {
        \Pimcore\Model\User\Permission\Definition::create($permission);
    }
}
