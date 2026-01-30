<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;

Loc::loadMessages(__FILE__);

class rl_notes extends CModule
{
    public function __construct() {

        if(file_exists(__DIR__."/version.php")) {

            $arModuleVersion = array();

            include_once(__DIR__."/version.php");

            $this->MODULE_ID = str_replace("_", ".", get_class($this));
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
            $this->MODULE_NAME = Loc::getMessage("RL_NOTES_MODULE_NAME");
            $this->MODULE_DESCRIPTION = Loc::getMessage("RL_NOTES_MODULE_DESC");
        }

        return false;
    }

    public function DoInstall()
    {
        global $APPLICATION, $step;

        $step = (int)$step;

        if ($step < 2) {
            $this->CheckRequirements();
            if (!empty($this->errors)) {
                $APPLICATION->ThrowException(implode('<br>', $this->errors));
                return false;
            }

            $this->InstallIblockType();
            if (!empty($this->errors)) {
                $APPLICATION->ThrowException(implode('<br>', $this->errors));
                return false;
            }

            $this->InstallIblock();
            if (!empty($this->errors)) {
                $APPLICATION->ThrowException(implode('<br>', $this->errors));
                return false;
            }

            $this->InstallFiles();
            $this->InstallOptions();
            $this->AddUrlRewrite();

            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);

            return true;
        }

        return true;
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $this->UnInstallFiles();
        $this->RemoveOptions();
        $this->RemoveUrlRewrite();

        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);

        return true;
    }

    public function CheckRequirements()
    {
        $minModuleVersion = '20.0.0';

        if (!\Bitrix\Main\ModuleManager::isModuleInstalled('iblock')) {
            $this->errors[] = GetMessage('RL_NOTES_REQUIRE_IBLOCK');
        }

        return empty($this->errors);
    }

    public function InstallIblockType()
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            $this->errors[] = 'Module iblock not loaded';
            return false;
        }

        // Check if iblock type already exists
        $res = \CIBlockType::GetByID('notes');
        if ($res && $res->Fetch()) {
            return true;
        }

        $iblockType = new \CIBlockType();
        $res = $iblockType->Add([
            'ID' => 'notes',
            'SECTIONS' => 'N',
            'IN_RSS' => 'N',
            'SORT' => 500,
            'LANG' => [
                'ru' => [
                    'NAME' => GetMessage('RL_NOTES_IBLOCK_TYPE_NAME'),
                    'ELEMENT_NAME' => GetMessage('RL_NOTES_IBLOCK_TYPE_ELEMENT'),
                ],
                'en' => [
                    'NAME' => 'Notes',
                    'ELEMENT_NAME' => 'Notes',
                ],
            ],
        ]);

        if (!$res) {
            $this->errors[] = $iblockType->LAST_ERROR;
            return false;
        }

        return true;
    }


    public function InstallIblock()
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            $this->errors[] = 'Module iblock not loaded';
            return false;
        }

        $res = \CIBlock::GetList(
            [],
            ['CODE' => 'notes', 'IBLOCK_TYPE_ID' => 'notes'],
            false
        )->Fetch();

        if ($res) {
            return true;
        }

        $siteIds = [];
        $rsSites = \CSite::GetList($by = 'sort', $order = 'asc');
        while ($site = $rsSites->Fetch()) {
            $siteIds[] = $site['LID'];
        }

        $iblock = new \CIBlock();
        $iblockId = $iblock->Add([
            'IBLOCK_TYPE_ID' => 'notes',
            'CODE' => 'notes',
            'XML_ID' => 'notes',
            'API_CODE' => 'notes',
            'NAME' => GetMessage('RL_NOTES_IBLOCK_NAME'),
            'ACTIVE' => 'Y',
            'SORT' => 100,
            'VERSION' => 2,
            'SITE_ID' => $siteIds,
        ]);

        if (!$iblockId) {
            $this->errors[] = $iblock->LAST_ERROR;
            return false;
        }

        return true;
    }


    public function InstallFiles()
    {
        $path = dirname(__DIR__);

        // Install component
        CopyDirFiles(
            $path . '/install/components',
            $_SERVER['DOCUMENT_ROOT'] . '/local/components',
            true,
            true
        );

        // Install API file to /api/notes/
        $apiDir = $_SERVER['DOCUMENT_ROOT'] . '/api/notes';
        if (!file_exists($apiDir)) {
            mkdir($apiDir, 0755, true);
        }

        CopyDirFiles(
            $path . '/install/api/notes/index.php',
            $apiDir . '/index.php',
            true,
            true
        );

        return true;
    }

    public function UnInstallFiles()
    {
        // Remove component
        DeleteDirFilesEx('/local/components/rl/notes');

        // Remove API file
        DeleteDirFilesEx('/api/notes/index.php');

        return true;
    }

    public function AddUrlRewrite()
    {
        $urlrewritePath = $_SERVER['DOCUMENT_ROOT'] . '/urlrewrite.php';

        $arUrlRewrite = [];
        if (file_exists($urlrewritePath)) {
            $data = include $urlrewritePath;
            if (is_array($data)) {
                $arUrlRewrite = $data;
            }
        }

        // Check if rule already exists
        foreach ($arUrlRewrite as $rule) {
            if (isset($rule['PATH']) && $rule['PATH'] === '/api/notes/index.php') {
                return true;
            }
        }

        // Add new rules
        $arUrlRewrite[] = [
            'CONDITION' => '#^/api/notes/([0-9]+)/#',
            'RULE' => 'id=$1',
            'PATH' => '/api/notes/index.php',
            'SORT' => 10,
        ];
        $arUrlRewrite[] = [
            'CONDITION' => '#^/api/notes/?(.*)#',
            'RULE' => '',
            'PATH' => '/api/notes/index.php',
            'SORT' => 20,
        ];

        // Write back to file
        $content = "<?php\n\$arUrlRewrite = " . var_export($arUrlRewrite, true) . ";\n";
        file_put_contents($urlrewritePath, $content);

        return true;
    }


    public function RemoveUrlRewrite()
    {
        $urlrewritePath = $_SERVER['DOCUMENT_ROOT'] . '/urlrewrite.php';
        
        $arUrlRewrite = [];
        if (file_exists($urlrewritePath)) {
            $data = include $urlrewritePath;
            if (is_array($data)) {
                $arUrlRewrite = $data;
            }
        }

        // Remove module rule
        $arUrlRewrite = array_filter($arUrlRewrite, function ($rule) {
            return (isset($rule['PATH']) && $rule['PATH'] !== '/api/notes/index.php');
        });

        // Reindex array
        $arUrlRewrite = array_values($arUrlRewrite);

        // Write back to file
        $content = "<?php\n\$arUrlRewrite = " . var_export($arUrlRewrite, true) . ";\n";
        file_put_contents($urlrewritePath, $content);

        return true;
    }

    public function InstallOptions()
    {
        // Set default options
        Option::set($this->MODULE_ID, 'api_token', '1234567890abcdef');
        return true;
    }

    public function RemoveOptions()
    {
        // Remove all module options
        Option::delete($this->MODULE_ID);
        return true;
    }
}