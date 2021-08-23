<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php';

use Bitrix\Main\Loader;
use Prokl\WebProfilierBundle\Controller\ProfilerAdminController;
use Proklung\Profilier\DI\Services;
use Symfony\Component\HttpFoundation\Request;

if (!function_exists('container')) {
    throw new \RuntimeException(
        'You must install https://github.com/ProklUng/bitrix.core.symfony or realize helper
               for recieve Symfony container instance. 
      '
    );
}

Loader::includeModule('proklung.profilier');

/** @var ProfilerAdminController $controller */
$controller = Services::get(ProfilerAdminController::class);

$content = $controller->action(new Request());

$content->sendContent();

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php';