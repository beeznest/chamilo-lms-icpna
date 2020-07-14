<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Shows the exercise results.
 *
 * @author Julio Montoya - Simple exercise result page
 */
require_once __DIR__.'/../inc/global.inc.php';

$id = isset($_REQUEST['id']) ? (int) $_GET['id'] : null; //exe id
$show_headers = isset($_REQUEST['show_headers']) ? (int) $_REQUEST['show_headers'] : null;
$origin = api_get_origin();

if (in_array($origin, ['learnpath', 'embeddable'])) {
    $show_headers = false;
}

api_protect_course_script($show_headers);

if (empty($id)) {
    api_not_allowed($show_headers);
}

$is_allowedToEdit = api_is_allowed_to_edit(null, true) || $is_courseTutor;

// Getting results from the exe_id. This variable also contain all the information about the exercise
$trackExerciseInfo = ExerciseLib::get_exercise_track_exercise_info($id);

// No track info
if (empty($trackExerciseInfo)) {
    api_not_allowed($show_headers);
}

$exercise_id = $trackExerciseInfo['exe_exo_id'];
$studentId = $trackExerciseInfo['exe_user_id'];
$current_user_id = api_get_user_id();

$objExercise = new Exercise();
if (!empty($exercise_id)) {
    $objExercise->read($exercise_id);
}

if (empty($objExercise)) {
    api_not_allowed($show_headers);
}

// Only users can see their own results
if (!$is_allowedToEdit) {
    if ($studentId != $current_user_id) {
        api_not_allowed($show_headers);
    }
}

$isAdaptive = EXERCISE_FEEDBACK_TYPE_PROGRESSIVE_ADAPTIVE == $objExercise->selectFeedbackType();

$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/css/hotspot.css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/js/hotspot.js"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'annotation/js/annotation.js"></script>';

if ($show_headers) {
    $interbreadcrumb[] = [
        'url' => 'exercise.php?'.api_get_cidreq(),
        'name' => get_lang('Exercises'),
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Result')];
    $this_section = SECTION_COURSES;
} else {
    $htmlHeadXtra[] = '<style>
        body { background: none;}
    </style>';
}

$message = Session::read('attempt_remaining');
Session::erase('attempt_remaining');

if ($isAdaptive) {
    $em = Database::getManager();

    $destinationResult = Database::getManager()
        ->getRepository('ChamiloCourseBundle:CQuizDestinationResult')
        ->findOneBy(['user' => $studentId, 'exe' => $id]);

    if (empty($destinationResult)) {
        Display::addFlash(
            Display::return_message(get_lang('NoData'), 'warning')
        );
    }

    $studentInfo = api_get_user_info($studentId);
    $quizzesDir = ExerciseLib::checkQuizzesPath($studentId);
    $qrUrl = api_get_path(WEB_CODE_PATH).'exercise/progressive_adaptive_results.php?'
        .http_build_query(['hash' => $destinationResult->getHash(), 'origin' => $origin]);

    $pageContent = $objExercise->showExerciseResultHeader(
        api_get_user_info($studentId),
        $trackExerciseInfo
    );
    $pageContent .= PHP_EOL;
    $pageContent .= '
        <div class="row">
            <div class="col-md-4 text-right">
                '.Display::img($quizzesDir['web'].$destinationResult->getHash().'.png').'
            </div>
            <div class="col-md-8 text-left">
                <p class="lead">'.sprintf(get_lang('LevelReachedX'), $destinationResult->getAchievedLevel()).'</p>
                <p>'.$studentInfo['complete_name_with_username'].'</p>
                <p>'.sprintf(get_lang('ResultHashX'), $destinationResult->getHash()).'</p>
                <p>'.Display::url(get_lang('SeeResults'), $qrUrl, ['target' => '_blank']).'</p>
            </div>
        </div>
    ';
} else {
    ob_start();
    ExerciseLib::displayQuestionListByAttempt(
        $objExercise,
        $id,
        false,
        $message
    );
    $pageContent = ob_get_contents();
    ob_end_clean();
}

$template = new Template('', $show_headers, $show_headers);
$template->assign('page_content', $pageContent);
$layout = $template->fetch(
    $template->get_template('exercise/result.tpl')
);
$template->assign('content', $layout);
$template->display_one_col_template();
