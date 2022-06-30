<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (class_exists("crimson_runmailevents"))
    return;

class crimson_runmailevents extends CModule {

    var $MODULE_ID = 'crimson.runmailevents';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;
    public $MODULE_GROUP_RIGHTS = 'N';
    public $NEED_MAIN_VERSION = '';
    public $NEED_MODULES = array();

    public function __construct() {

        $arModuleVersion = array();

        $path = str_replace('\\', '/', __FILE__);
        $path = substr($path, 0, strlen($path) - strlen('/index.php'));
        include($path . '/version.php');

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage("CRIMSON_RUNMAILEVENTS_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("CRIMSON_RUNMAILEVENTS_MODULE_DISCRIPTION");

        $this->PARTNER_NAME = Loc::getMessage("CRIMSON_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("CRIMSON_PARTNER_URI");
    }

    public function DoInstall() {
//        if (is_array($this->NEED_MODULES) && !empty($this->NEED_MODULES)) {
//            foreach ($this->NEED_MODULES as $module) {
//                if (!IsModuleInstalled($module)) {
//                    $this->ShowForm('ERROR', GetMessage('CRIMSON_NEED_MODULES', array('#MODULE#' => $module)));
//                }
//            }
//        }

        RegisterModuleDependences('main', 'OnAdminListDisplay', $this->MODULE_ID, 'CrimsonRunMailEventsHelper', 'OnAdminListDisplayHandler');
        RegisterModuleDependences('main', 'OnBeforeProlog', $this->MODULE_ID, 'CrimsonRunMailEventsHelper', 'OnBeforePrologHandler');
        RegisterModule($this->MODULE_ID);
        $this->ShowForm('OK', GetMessage('MOD_INST_OK'));
    }

    public function DoUninstall() {
        UnRegisterModuleDependences('main', 'OnAdminListDisplay', $this->MODULE_ID, 'CrimsonRunMailEventsHelper', 'OnAdminListDisplayHandler');
        UnRegisterModuleDependences('main', 'OnBeforeProlog', $this->MODULE_ID, 'CrimsonRunMailEventsHelper', 'OnBeforePrologHandler');
        UnRegisterModule($this->MODULE_ID);
        $this->ShowForm('OK', GetMessage('MOD_UNINST_OK'));
    }

    private function ShowForm($type, $message, $buttonName = '') {
        // Костыль ёпт
        $keys = array_keys($GLOBALS);
        for($i=0, $intCount = count($keys); $i < $intCount; $i++) {
                if($keys[$i]!='i' && $keys[$i]!='GLOBALS' && $keys[$i]!='strTitle' && $keys[$i]!='filepath') {
                        global ${$keys[$i]};
                }
        }
        
        $APPLICATION->SetTitle($this->MODULE_NAME);
        include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');
        echo CAdminMessage::ShowMessage(array('MESSAGE' => $message, 'TYPE' => $type));
        ?>
        <form action="<?= $APPLICATION->GetCurPage() ?>" method="get">
            <p>
                <input type="hidden" name="lang" value="<? echo LANGUAGE_ID; ?>" />
                <input type="submit" value="<?= strlen($buttonName) ? $buttonName : GetMessage('MOD_BACK') ?>" />
            </p>
        </form>
        <?
        include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
        die();
    }

}
