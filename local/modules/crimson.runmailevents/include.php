<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CrimsonRunMailEventsHelper {

    CONST ACTION_NAME = 'crimson_message_send';

    public static $msg = '';
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
                $rsUser = CUser::GetByID($GLOBALS['USER']->GetID());
                if ($arUser = $rsUser->Fetch()) {
                    static::$email = $arUser['EMAIL'];
                    \Bitrix\Main\EventManager::getInstance()->addEventHandler(
                            'main',
                            'OnBeforeMailSend', //https://estrin.pw/bitrix-d7-snippets/s/mail-events/
                            '\CrimsonRunMailEventsHelper::OnBeforeMailSend'
                    );

                    \Bitrix\Main\Mail\Event::sendImmediate(array(
                        "EVENT_NAME" => $arMess['EVENT_NAME'],
                        "MESSAGE_ID" => $arMess['ID'],
                        "LID" => $arUser['LID'],
                        "C_FIELDS" => [],
                    ));

                    static::$msg = "TO:{$arUser['EMAIL']}; ID:{$arMess['ID']}; LID:{$arUser['LID']};";
                }
            } else {
                static::$msg = "ERR SEND: {$_REQUEST['message_id']}";
            }
            unset($_REQUEST['action']);
        }
    }

    public static function OnBeforeMailSend(\Bitrix\Main\Event $event) {
        $mailParams = $event->getParameter(0);
        $mailParams['TO'] = static::$email;
        $result = new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, $mailParams);
        return $result;
    }

    public static function OnAdminListDisplayHandler(&$list) {

        if ($GLOBALS['APPLICATION']->GetCurPage() == '/bitrix/admin/message_admin.php') {

            if (strlen(static::$msg)) {
                $message = new CAdminMessage(array('TYPE' => 'OK', 'MESSAGE' => static::$msg));
                echo $message->Show();
            }

            $lAdmin = new CAdminList($list->table_id, $list->sort);
            foreach ($list->aRows as $id => &$v) {
                array_unshift($v->aActions,
                        [
                            'ICON' => 'view',
                            'TEXT' => Loc::getMessage('CRIMSON_ACTION_RUN'),
                            'ACTION' => $lAdmin->ActionDoGroup($v->id, static::ACTION_NAME, '&lang=' . LANGUAGE_ID . '&message_id=' . $v->id)
                        ]
                );
            }
        }
    }

}
