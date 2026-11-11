<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;

/** @var CUser $USER */
/** @var CMain $APPLICATION */
global $USER, $APPLICATION;

if (!$USER || !$USER->IsAdmin())
{
    return;
}

Loc::loadMessages(__FILE__);
$moduleId = 'maximapridex.taskmentions';
$request = Context::getCurrent()->getRequest();

if ($request->isPost()
    && ($request->getPost('save') !== null || $request->getPost('apply') !== null)
    && check_bitrix_sessid())
{
    $newRole = (string)$request->getPost('role');
    if ($newRole === '0' || $newRole === '1')
    {
        Option::set($moduleId, 'role', $newRole);
    }
    else
    {
        CAdminMessage::ShowMessage(Loc::getMessage('MP_TM_INVALID_ROLE'));
    }
}

$role = (string)Option::get($moduleId, 'role', '0');
$tabs = [
    [
        'DIV' => 'maximapridex_taskmentions_settings',
        'TAB' => Loc::getMessage('MP_TM_TAB'),
        'TITLE' => Loc::getMessage('MP_TM_TAB_TITLE'),
    ],
];
$tabControl = new CAdminTabControl('maximapridex_taskmentions_tab', $tabs);
?>
<form method="post" action="<?=htmlspecialcharsbx($APPLICATION->GetCurPage())?>?mid=<?=urlencode($moduleId)?>&amp;lang=<?=urlencode(LANGUAGE_ID)?>">
    <?php
    echo bitrix_sessid_post();
    $tabControl->Begin();
    $tabControl->BeginNextTab();
    ?>
    <tr>
        <td colspan="2" style="padding-bottom: 16px;">
            <strong><?=htmlspecialcharsbx(Loc::getMessage('MP_TM_ABOUT_TITLE'))?></strong>
            <p style="margin: 12px 0 8px;"><?=htmlspecialcharsbx(Loc::getMessage('MP_TM_ABOUT_TEXT'))?></p>
            <p style="margin: 0;"><?=htmlspecialcharsbx(Loc::getMessage('MP_TM_PERMISSIONS'))?></p>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="padding-top: 12px; padding-bottom: 16px;">
            <strong><?=htmlspecialcharsbx(Loc::getMessage('MP_TM_SETTINGS_TITLE'))?></strong>
            <div style="display: flex; align-items: center; flex-wrap: wrap; column-gap: 18px; row-gap: 10px; margin-top: 12px;">
                <span><?=htmlspecialcharsbx(Loc::getMessage('MP_TM_ROLE'))?></span>
                <label style="display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;">
                    <input type="radio" name="role" value="0" <?=$role !== '1' ? 'checked' : ''?>>
                    <?=htmlspecialcharsbx(Loc::getMessage('MP_TM_ACCOMPLICES'))?>
                </label>
                <label style="display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;">
                    <input type="radio" name="role" value="1" <?=$role === '1' ? 'checked' : ''?>>
                    <?=htmlspecialcharsbx(Loc::getMessage('MP_TM_AUDITORS'))?>
                </label>
            </div>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="padding-top: 12px; padding-bottom: 12px;">
            <strong><?=htmlspecialcharsbx(Loc::getMessage('MP_TM_REPOSITORY'))?></strong>
            <div style="margin-top: 12px;">
                <a href="https://github.com/avmaksimov/bitrix-task-mentions" target="_blank" rel="noopener noreferrer">https://github.com/avmaksimov/bitrix-task-mentions</a>
            </div>
        </td>
    </tr>
    <?php
    $tabControl->Buttons();
    ?>
    <input type="submit" name="save" value="<?=htmlspecialcharsbx(Loc::getMessage('MP_TM_SAVE'))?>" class="adm-btn-save">
    <input type="submit" name="apply" value="<?=htmlspecialcharsbx(Loc::getMessage('MP_TM_APPLY'))?>">
    <?php
    $tabControl->End();
    ?>
</form>
