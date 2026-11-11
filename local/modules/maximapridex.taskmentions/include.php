<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

\Bitrix\Main\Loader::registerAutoLoadClasses('maximapridex.taskmentions', [
    'MaximaPridex\\TaskMentions\\Handler' => 'lib/handler.php',
]);
