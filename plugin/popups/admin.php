<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

use Chamilo\PluginBundle\Entity\Popups\Popup;
use Knp\Component\Pager\Paginator;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

api_protect_admin_script();

$plugin = PopupsPlugin::create();

if ('true' !== $plugin->get(PopupsPlugin::SETTING_ENABLED)) {
    api_not_allowed(true);
}

$request = HttpRequest::createFromGlobals();

$action = $request->get('action');

$em = Database::getManager();

$pageTitle = $plugin->get_title();
$template = new Template($pageTitle);

switch ($action) {
    case 'add':
    case 'edit':
        actionAddEdit($request, $template);
        break;
    case 'visible':
    case 'invisible':
        actionVisibility($request);
        break;
    case 'delete':
        actionDelete($request, $template);
        break;
    default:
        actionList($request, $template);
        break;
}

$template->assign('header', $pageTitle);
$template->display_one_col_template();

/**
 * @param HttpRequest $request
 * @param Template    $template
 *
 * @throws \Doctrine\ORM\ORMException
 * @throws \Doctrine\ORM\OptimisticLockException
 * @throws \Doctrine\ORM\TransactionRequiredException
 */
function actionAddEdit(HttpRequest $request, Template $template)
{
    $plugin = PopupsPlugin::create();

    $id = (int) $request->get('id');
    $action = $request->get('action');

    $isEdit = $id && 'edit' === $action;

    $em = Database::getManager();

    /** @var Popup $popup */
    $popup = null;

    if ($isEdit) {
        $popup = $em->find('ChamiloPluginBundle:Popups\Popup', $id);

        if (!$popup) {
            Display::addFlash(
                Display::return_message(get_lang('NoItem'), 'error')
            );

            header('Location: '.api_get_self());
            exit;
        }
    }

    $form = new FormValidator('frm_popup');
    $form->addText('title', get_lang('Title'), true, []);
    $form->addHtmlEditor('content', get_lang('Content'), true, false, ['ToolbarSet' => 'Minimal']);
    $form->addSelect(
        'visible_for',
        $plugin->get_lang('IsVisibleFor'),
        [
            COURSEMANAGER => get_lang('Teacher'),
            STUDENT => get_lang('Learner'),
            DRH => get_lang('Drh'),
            SESSIONADMIN => get_lang('SessionsAdmin'),
            STUDENT_BOSS => get_lang('RoleStudentBoss'),
            INVITEE => get_lang('Invitee'),
        ],
        ['multiple' => 'multiple']
    );
    $form->addCheckBox('visible', get_lang('FieldVisibility'), get_lang('Yes'));
    $form->addHidden('action', $action);

    if ($isEdit) {
        $form->addButtonUpdate($plugin->get_lang('EditPopup'));
    } else {
        $form->addButtonCreate($plugin->get_lang('AddPopup'));
    }

    $form->addRule('visible_for', get_lang('ThisFieldIsRequired'), 'required');
    $form->applyFilter('title', 'trim');
    $form->applyFilter('content', 'trim');

    if ($form->validate()) {
        $values = $form->exportValues();

        if (!$isEdit) {
            $popup = new Popup();
        }

        if (isset($values['visible'])) {
            $em
                ->createQuery('UPDATE ChamiloPluginBundle:Popups\Popup p SET p.visible = false WHERE p.id != :id')
                ->execute(['id' => $id]);
        }

        $popup
            ->setTitle($values['title'])
            ->setContent($values['content'])
            ->setVisible(isset($values['visible']))
            ->setShownIn(PopupsPlugin::SHOWN_IN_USERPORTAL)
            ->setVisibleFor(
                isset($values['visible_for']) ? $values['visible_for'] : []
            );

        $em->persist($popup);
        $em->flush();

        Display::addFlash(
            Display::return_message(
                $isEdit ? $plugin->get_lang('PopupUpdated') : $plugin->get_lang('PopupAdded'),
                'success'
            )
        );

        header('Location: '.api_get_self());
        exit;
    }

    if ($isEdit) {
        $form->addHidden('id', $popup->getId());
        $form->setDefaults(
            [
                'title' => $popup->getTitle(),
                'content' => $popup->getContent(),
                'visible' => $popup->isVisible(),
                'visible_for' => $popup->getVisibleFor(),
            ]
        );
    }

    $actionLinks = Display::url(
        Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
        api_get_self()
    );

    $template->assign('actions', Display::toolbarAction('popup-actions', [$actionLinks]));
    $template->assign('content', $form->returnForm());
}

/**
 * @param HttpRequest $request
 * @param Template    $template
 */
function actionList(HttpRequest $request, Template $template)
{
    $plugin = PopupsPlugin::create();

    $pageSelf = api_get_self();

    $query = Database::getManager()->createQuery('SELECT p FROM ChamiloPluginBundle:Popups\Popup p');

    $paginator = new Paginator();
    $pagination = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1)
    );
    $pagination->renderer = function ($data) use ($pageSelf) {
        if ($data['first'] === $data['last']) {
            return '';
        }

        $html = '';

        for ($i = 1; $i <= $data['pageCount']; $i++) {
            if ($data['current'] == $i) {
                $html .= '<li class="active"><span>'.$i.' <span class="sr-only">'.get_lang('Current')
                    .'</span></span></li>';
            } else {
                $html .= '<li><a href="'.$pageSelf.'?page='.$i.'">'.$i.'</a></li>';
            }
        }

        return '<ul class="pagination">'.$html.'</ul>';
    };

    $template->assign('pagination', $pagination);
    $content = $template->fetch('popups/view/popups_admin.tpl');

    $actionLinks = Display::url(
        Display::return_icon('add.png', $plugin->get_lang('AddPopup'), [], ICON_SIZE_MEDIUM),
        "$pageSelf?action=add"
    );

    $template->assign('actions', Display::toolbarAction('popup-actions', [$actionLinks]));
    $template->assign('content', $content);
}

/**
 * @param HttpRequest $request
 *
 * @throws \Doctrine\ORM\ORMException
 * @throws \Doctrine\ORM\OptimisticLockException
 * @throws \Doctrine\ORM\TransactionRequiredException
 */
function actionVisibility(HttpRequest $request)
{
    $id = $request->query->getInt('id');
    $action = $request->query->getAlpha('action');

    $em = Database::getManager();

    /** @var Popup $popup */
    $popup = $em->find('ChamiloPluginBundle:Popups\Popup', $id);

    if (!$popup) {
        $message = Display::return_message(get_lang('NoItem'), 'warning');
    } else {
        $em
            ->createQuery('UPDATE ChamiloPluginBundle:Popups\Popup p SET p.visible = false')
            ->execute();

        $popup->setVisible('visible' === $action);

        $em->persist($popup);
        $em->flush();

        $message = Display::return_message(get_lang('ItemUpdated'), 'success');
    }

    Display::addFlash($message);

    header('Location: '.api_get_self());
    exit;
}

function actionDelete(HttpRequest $request)
{
    $id = $request->query->getInt('id');

    $em = Database::getManager();
    $plugin = PopupsPlugin::create();

    /** @var Popup $popup */
    $popup = $em->find('ChamiloPluginBundle:Popups\Popup', $id);

    if (!$popup) {
        $message = Display::return_message(get_lang('NoItem'), 'warning');
    } else {
        $em->remove($popup);
        $em->flush();

        $message = Display::return_message($plugin->get_lang('PopupDeleted'), 'success');
    }

    Display::addFlash($message);

    header('Location: '.api_get_self());
    exit;
}
