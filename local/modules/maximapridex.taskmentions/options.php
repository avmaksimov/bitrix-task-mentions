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

$role = Option::get($moduleId, 'role');
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
        <td colspan="2">
            <strong><?=htmlspecialcharsbx(Loc::getMessage('MP_TM_ABOUT_TITLE'))?></strong>
            <p><?=htmlspecialcharsbx(Loc::getMessage('MP_TM_ABOUT_TEXT'))?></p>
        </td>
    </tr>
    <tr>
        <td width="40%"><label for="maximapridex_taskmentions_role"><?=htmlspecialcharsbx(Loc::getMessage('MP_TM_ROLE'))?></label></td>
        <td width="60%">
            <select id="maximapridex_taskmentions_role" name="role">
                <option value="0" <?=$role === '0' ? 'selected' : ''?>><?=htmlspecialcharsbx(Loc::getMessage('MP_TM_ACCOMPLICES'))?></option>
                <option value="1" <?=$role === '1' ? 'selected' : ''?>><?=htmlspecialcharsbx(Loc::getMessage('MP_TM_AUDITORS'))?></option>
            </select>
        </td>
    </tr>
    <tr>
        <td width="40%"><?=htmlspecialcharsbx(Loc::getMessage('MP_TM_REPOSITORY'))?></td>
        <td width="60%"><a href="https://github.com/avmaksimov/bitrix-task-mentions" target="_blank" rel="noopener noreferrer">github.com/avmaksimov/bitrix-task-mentions</a></td>
    </tr>
    <tr>
        <td colspan="2" align="center"><?=htmlspecialcharsbx(Loc::getMessage('MP_TM_PERMISSIONS'))?></td>
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
