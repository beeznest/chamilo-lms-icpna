<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;

$cidReset = true;

require_once '../inc/global.inc.php';

$userId = intval($_REQUEST['user_id']);
$sessionId = intval($_REQUEST['id_session']);

if (empty($userId) && empty($sessionId)) {
    api_not_allowed(true);
}

SessionManager::protectSession($sessionId);

$em = Database::getManager();
/** @var Session $session */
$session = $em->find('ChamiloCoreBundle:Session', $sessionId);
$user = api_get_user_entity($userId);

if (api_is_platform_admin()) {
    $sessions = SessionManager::get_sessions_admin(array('order' => 'name'));
} else {
    $sessions = SessionManager::get_sessions_by_general_coach($session->getId());
}

$sessionToSelect = [];

foreach ($sessions as $sessionInfo) {
    if ($session->getId() != $sessionInfo['id']) {
        $sessionToSelect[$sessionInfo['id']] = $sessionInfo['name'];
    }
}

//Check if user was already moved
$userStatusInSession = SessionManager::getUserStatusInSession($userId, $sessionId);

if ($userStatusInSession
    && $userStatusInSession->getMovedTo() != 0
    || $userStatusInSession->getMovedStatus() == SessionManager::SESSION_CHANGE_USER_REASON_ENROLLMENT_ANNULATION
) {
    api_not_allowed(true);
}

$form = new FormValidator('change_user_session');
$form->addElement('hidden', 'user_id', $user->getId());
$form->addElement('hidden', 'id_session', $session->getId());
$form->addElement('header', get_lang('ChangeUserSession'));
$form->addText('user_name', get_lang('User'), false);
$form->addText('session_name', get_lang('CurrentSession'), false);
$form->addElement(
    'select',
    'reason_id',
    get_lang('Action'),
    SessionManager::getSessionChangeUserReasons(),
    ['id' => 'reason_id']
);
$form->addElement(
    'select',
    'new_session_id',
    get_lang('SessionDestination'),
    $sessionToSelect,
    ['id' => 'new_session_id']
);
$form->addButtonMove(get_lang('Change'));
$form->freeze(['user_name', 'session_name']);
$form->setDefaults([
    'user_name' => $user->getCompleteName(),
    'session_name' => $session->getName()
]);

$content = $form->returnForm();

if ($form->validate()) {
    $values = $form->getSubmitValues();
    SessionManager::changeUserSession(
        $values['user_id'],
        $values['id_session'],
        $values['new_session_id'],
        $values['reason_id']
    );

    Display::addFlash(
        Display::return_message(get_lang('UserSessionWasChanged'), 'success')
    );

    header('Location: resume_session.php?id_session='.$values['id_session']);
    exit;
}

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'session_list.php', 'name' => get_lang('SessionList')];
$interbreadcrumb[] = [
    'url' => 'resume_session.php?id_session='.$sessionId,
    'name' => get_lang('SessionOverview')
];
$interbreadcrumb[] = array('url' => '#', 'name' => get_lang('ChangeUserSession'));

$htmlHeadXtra[] = '<script>
    $(document).ready(function() {
        $("#reason_id").change(function() {
            var value = $(this).val();
            if (value == "'.SessionManager::SESSION_CHANGE_USER_REASON_ENROLLMENT_ANNULATION.'") {
                $("#new_session_id").parent().parent().hide();
            } else {
                $("#new_session_id").parent().parent().show();
            }
        });
    });
</script>';

$tpl = new Template();
$tpl->assign('content', $content);
$tpl->display_one_col_template();
