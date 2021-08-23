<?php

use Bitrix\Main\Localization\Loc;
use ProklUng\Module\Boilerplate\Module;
use ProklUng\Module\Boilerplate\ModuleUtilsTrait;

Loc::loadMessages(__FILE__);

class proklung_profilier extends CModule
{
    use ModuleUtilsTrait;

    public function __construct()
    {
        $arModuleVersion = [];

        include __DIR__ . '/version.php';

        if (is_array($arModuleVersion)
            &&
            array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_FULL_NAME = 'profilier';
        $this->MODULE_VENDOR = 'proklung';
        $prefixLangCode = 'PROFILIER';

        $this->MODULE_NAME = Loc::getMessage($prefixLangCode . '_MODULE_NAME');
        $this->MODULE_ID = $this->MODULE_VENDOR . '.' . $this->MODULE_FULL_NAME;
        
        $this->MODULE_DESCRIPTION = Loc::getMessage($prefixLangCode . '_MODULE_DESCRIPTION');
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME = Loc::getMessage($prefixLangCode . '_MODULE_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage($prefixLangCode . 'MODULE_PARTNER_URI');

        $this->INSTALL_PATHS = [
            '/bitrix/modules/proklung.profilier/install/admin/_profiler_bitrix.php'
            => '/bitrix/admin/_profiler_bitrix.php',
            '/bitrix/modules/proklung.profilier/install/admin/_profiler_module.php'
            => '/bitrix/admin/_profiler_module.php',
            '/bitrix/modules/proklung.profilier/install/admin/_profilier_module_clearer.php'
            => '/bitrix/admin/_profilier_module_clearer.php',
            '/bitrix/modules/proklung.profilier/install/admin/_profilier_token.php'
            => '/bitrix/admin/_profilier_token.php',
            '/bitrix/modules/proklung.profilier/install/admin/_profilier_render_token.php'
            => '/bitrix/admin/_profilier_render_token.php',
            '/bitrix/modules/proklung.profilier/install/admin/symfony.png'
            => '/bitrix/images/symfony.png',
            '/bitrix/modules/proklung.profilier/install/admin/assets/bootstrap.bundle.min.js'
            => '/bitrix/admin/bootstrap.bundle.min.js',
            '/bitrix/modules/proklung.profilier/install/admin/assets/bootstrap.min.css'
            => '/bitrix/admin/bootstrap.min.css',
        ];
        
        $this->moduleManager = new Module(
            [
            'MODULE_ID' => $this->MODULE_ID,
            'VENDOR_ID' => $this->MODULE_VENDOR,
            'MODULE_VERSION' => $this->MODULE_VERSION,
            'MODULE_VERSION_DATE' => $this->MODULE_VERSION_DATE,
            'ADMIN_FORM_ID' => $this->MODULE_VENDOR . '_settings_form',
            ]
        );

        $this->moduleManager->addModuleInstance($this);
        $this->options();
    }

    /**
     * @inheritDoc
     */
    protected function getSchemaTabsAdmin(): array
    {
        $values =
            [
                'profilier_config' => [
                    'TAB' => 'Настройки',
                    'TITLE' => 'Настройки',
                ],
            ];

        return $values;
    }

    /**
     * @inheritDoc
     */
    protected function getSchemaOptionsAdmin(): array
    {
        return [
            'profiler_enabled' =>
                [
                    'label' => 'Активность',
                    'tab' => 'profilier_config',
                    'type' => 'checkbox',
                ],
        ];
    }
}
