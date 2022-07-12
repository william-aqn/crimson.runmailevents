<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CrimsonRunMailEventsHelper {

    CONST ACTION_NAME = 'crimson_message_send';

    public static $msg = ['type' => "OK", 'text' => ''];
    public static $email = '';

    public static function OnBeforePrologHandler() {

        if (!defined("ADMIN_SECTION") || ADMIN_SECTION !== true) {
            return;
        }

        if (isset($_REQUEST['action_button']) && !isset($_REQUEST['action'])) {
            $_REQUEST['action'] = $_REQUEST['action_button'];
        }
        if (!isset($_REQUEST['action'])) {
            return;
        }

        global $USER, $APPLICATION;

        if ($_REQUEST['action'] == static::ACTION_NAME && $USER->CanDoOperation('view_other_settings') &&
                $GLOBALS['APPLICATION']->GetCurPage() == '/bitrix/admin/message_admin.php' && check_bitrix_sessid() &&
                is_numeric($_REQUEST['message_id'])
        ) {

            $rsMess = \CEventMessage::GetList($by = "site_id", $order = "desc", ['ID' => (int) $_REQUEST['message_id']]);
            if ($arMess = $rsMess->GetNext()) {
                $rsUser = \CUser::GetByID($GLOBALS['USER']->GetID());
                if ($arUser = $rsUser->Fetch()) {
                    static::$email = $arUser['EMAIL'];
                    \Bitrix\Main\EventManager::getInstance()->addEventHandler(
                            'main',
                            'OnBeforeMailSend', //https://estrin.pw/bitrix-d7-snippets/s/mail-events/
                            '\CrimsonRunMailEventsHelper::OnBeforeMailSend',
                            false,
                            10
                    );

                    static::$msg['text'] = "TO:{$arUser['EMAIL']}; ID:{$arMess['ID']}; LID:{$arUser['LID']}; LANG:{$arMess['LANGUAGE_ID']};";
                    $result = \Bitrix\Main\Mail\Event::sendImmediate(array(
                                "EVENT_NAME" => $arMess['EVENT_NAME'],
                                "MESSAGE_ID" => $arMess['ID'],
                                "LID" => $arUser['LID'],
                                "C_FIELDS" => [],
                    ));
                    static::$msg['text'] .= " RESULT:" . $result;

                    static::$msg['type'] = ($result != "Y") ? "ERR" : "OK";
                }
            } else {
                static::$msg['text'] = "ERR SEND: {$_REQUEST['message_id']}";
                static::$msg['type'] = "ERR";
            }
            unset($_REQUEST['action']);
        }
    }

    /**
     * Заменяем получателя
     * @param \Bitrix\Main\Event $event
     * @return \Bitrix\Main\EventResult
     */
    public static function OnBeforeMailSend(\Bitrix\Main\Event $event) {
        //$mailParams = $event->getParameter(0);
        $mod = ['TO' => static::$email];
        if (!defined("ONLY_EMAIL")) {
            define("ONLY_EMAIL", static::$email);
        }
        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, $mod);
    }

    /**
     * Добавляем пункт в контекстное меню списка почтовых шаблонов
     * @global type $USER
     * @param type $list
     */
    public static function OnAdminListDisplayHandler(&$list) {
        if ($GLOBALS['APPLICATION']->GetCurPage() == '/bitrix/admin/message_admin.php') {

            if (strlen(static::$msg['text'])) {
                $message = new CAdminMessage(array('TYPE' => static::$msg['type'], 'MESSAGE' => static::$msg['text']));
                echo $message->Show();
                $text = "Успешно отправлено на " . static::$email;
                if (static::$msg['type'] != "OK") {
                    $text = "Ошибка при отправке на " . static::$email;
                }
                echo "<script>alert('{$text}')</script>";
            }
            global $USER;
            $email = $USER->getEmail();
            $lAdmin = new CAdminList($list->table_id, $list->sort);
            foreach ($list->aRows as $id => &$v) {
                // Добавляем применённый шаблон
                if ($v->arRes['SITE_TEMPLATE_ID']) {
                    $v->aFields['ID']['view']['value'] .= " / " . $v->arRes['SITE_TEMPLATE_ID'];
                }
                array_unshift($v->aActions,
                        [
                            'ICON' => 'view',
                            'TEXT' => Loc::getMessage('CRIMSON_ACTION_RUN', ["#EMAIL#" => $email]),
                            'ACTION' => $lAdmin->ActionDoGroup($v->id, static::ACTION_NAME, '&lang=' . LANGUAGE_ID . '&message_id=' . $v->id)
                        ]
                );
            }
        }
    }

}
