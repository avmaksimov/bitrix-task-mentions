<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}


use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class maximapridex_taskmentions extends CModule
{
    public $MODULE_ID = 'maximapridex.taskmentions';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI = '';

    public function __construct()
    {
        $arModuleVersion = [];
        require __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('MP_TM_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('MP_TM_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('MP_TM_PARTNER');
    }

    public function DoInstall()
    {
        if (!ModuleManager::isModuleInstalled('tasks') || !ModuleManager::isModuleInstalled('im'))
        {
            throw new \RuntimeException('The tasks and im modules must be installed.');
        }

        ModuleManager::registerModule($this->MODULE_ID);

        EventManager::getInstance()->registerEventHandlerCompatible(
            'im',
            'OnAfterMessagesAdd',
            $this->MODULE_ID,
            '\\MaximaPridex\\TaskMentions\\Handler',
            'onAfterMessagesAdd'
        );
    }

    public function DoUninstall()
    {
        EventManager::getInstance()->unRegisterEventHandler(
            'im',
            'OnAfterMessagesAdd',
            $this->MODULE_ID,
            '\\MaximaPridex\\TaskMentions\\Handler',
            'onAfterMessagesAdd'
        );

        ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}
