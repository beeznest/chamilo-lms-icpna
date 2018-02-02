<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.document
 */
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

api_protect_course_script(true);

$isAllowedToEdit = api_is_allowed_to_edit();

if (!isset($_REQUEST['id']) || !$isAllowedToEdit) {
    api_not_allowed(true);
}

$documentId = intval($_REQUEST['id']);
$userId = api_get_user_id();
$groupId = api_get_group_id();
$courseId = api_get_course_int_id();
$courseInfo = api_get_course_info_by_id($courseId);
$sessionId = api_get_session_id();
$cidreq = api_get_cidreq();

$documentData = DocumentManager::get_document_data_by_id(
    $documentId,
    $courseInfo['code'],
    true,
    $sessionId
);

if (empty($documentData)) {
    api_not_allowed(true);
}
Security::check_token();
$pathParts = array_map(
    'urldecode',
    explode('/', str_replace('\\', '/', $documentData['path']))
);
$documentData['path'] = implode('/', $pathParts);

if (!empty($groupId)) {
    $groupInfo = GroupManager::get_group_properties($groupId);
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.$cidreq,
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.$cidreq,
        'name' => get_lang('GroupSpace').' '.$groupInfo['name'],
    ];
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq().'&id=0',
    'name' => get_lang('Documents'),
];

$parentId = 0;

foreach ($documentData['parents'] as $documentParent) {
    if ($documentParent['id'] == $documentData['id']) {
        continue;
    }

    $interbreadcrumb[] = [
        'url' => $documentParent['document_url'],
        'name' => $documentParent['title'],
    ];
    $parentId = $documentParent['iid'];
}

Security::clear_token();
$token = Security::get_token();

$form = new FormValidator('validate', 'POST');
$form->addText('file_name', get_lang('Filename'), false);
$form->addText('file_size', get_lang('Size'), false);
$form->addElement(
    'password',
    'pass',
    [get_lang('Password'), 'This file is encrypted, please enter a password to play it'],
    ['required' => 'required']
);
$form->addHidden('id', $documentData['iid']);
$form->addElement('hidden', 'sec_token');
$form->setConstants(['sec_token' => $token]);
$form->setDefaults([
    'file_name' => $documentData['title'],
    'file_size' => format_file_size($documentData['size'])
]);
$form->freeze(['file_name', 'file_size']);
$form->addButtonNext(get_lang('Validate'));

// View
$htmlHeadXtra[] = api_get_js('fetch/fetch.js');
$htmlHeadXtra[] = '
    <style>
        #player audio {margin-top: 1.5em; transform: scale(1.2); width: 90%;}
        .has-error .freeze {color: #a94442;}
        .has-success .freeze {color: #3c763d;}
    </style>
';

$actionsLeft = Display::url(
    Display::return_icon('folder_up.png', get_lang('Up'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq().'&id='.$parentId
);

$toolName = get_lang('SecuredFile');

$template = new Template($toolName);
$template->assign('form', $form->returnForm());
$template->assign(
    'start_message',
    Display::return_message(get_lang('EnterPassword'))
);
$layout = $template->get_template('document/show_safely.tpl');
$template->assign('header', $toolName);
$template->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionsLeft])
);
$template->assign('content', $template->fetch($layout));
$template->display_one_col_template();
