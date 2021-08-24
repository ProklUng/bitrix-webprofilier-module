<?php

use Bitrix\Main\Loader;
use ProklUng\Module\Boilerplate\Module;

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php';

Loader::includeModule('proklung.profilier');

// Странный эффект: на новой версии Битрикса на этой стадии класс модуля не инстанцирован.
// На старом - все OK.
try {
    $module = Module::getModuleInstance('proklung.profilier');
} catch (\LogicException $e) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/proklung.profilier/install/index.php';
    new proklung_profilier();
    $module = Module::getModuleInstance('proklung.profilier');
}

$module->showOptionsForm();


require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php';