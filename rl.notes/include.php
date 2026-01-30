<?php
/**
 * Main module class for RL.Notes
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

class RLNotes extends CModule
{
    public $MODULE_ID = 'rl.notes';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . '/install/version.php');

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = GetMessage('RL_NOTES_MODULE_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('RL_NOTES_MODULE_DESC');
        $this->PARTNER_NAME = 'red---line';
        $this->PARTNER_URI = 'https://red---line.ru';
    }

    public function isModuleInstalled()
    {
        return \Bitrix\Main\ModuleManager::isModuleInstalled($this->MODULE_ID);
    }

    public function GetModuleRightList()
    {
        return [
            'reference_id' => ['W', 'R'],
            'reference' => [
                '[W] ' . GetMessage('RL_NOTES_RIGHT_WRITE'),
                '[R] ' . GetMessage('RL_NOTES_RIGHT_READ'),
            ],
        ];
    }
}
