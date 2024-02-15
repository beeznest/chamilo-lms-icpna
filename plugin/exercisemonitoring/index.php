<?php

/* For licensing terms, see /license.txt */

$plugin = ExerciseMonitoringPlugin::create();
$em = Database::getManager();

$isEnabled = $plugin->isEnabled(true);
$showOverviewRegion = $isEnabled && strpos($_SERVER['SCRIPT_NAME'], '/main/exercise/overview.php') !== false;
$showSubmitRegion = $isEnabled && strpos($_SERVER['SCRIPT_NAME'], '/main/exercise/exercise_submit.php') !== false;

$_template['enabled'] = false;
$_template['show_overview_region'] = $showOverviewRegion;
$_template['show_submit_region'] = $showSubmitRegion;

$exercise = null;
$readExercise = false;

if ($showOverviewRegion || $showSubmitRegion) {
    $exerciseId = (int) $_GET['exerciseId'];

    $exercise = new Exercise(api_get_course_int_id());
    $readExercise = $exercise->read($exerciseId);

    $objFieldValue = new ExtraFieldValue('exercise');
    $values = $objFieldValue->get_values_by_handler_and_field_variable(
        $exerciseId,
        ExerciseMonitoringPlugin::FIELD_SELECTED
    );

    $_template['enabled'] = $values && (bool) $values['value'];
    $_template['exercise_id'] = $exerciseId;
}

$_template['enable_snapshots'] = true;

$isAdult = $plugin->isAdult();

if ($showOverviewRegion && $_template['enabled']) {
    $_template['instructions'] = $plugin->get(ExerciseMonitoringPlugin::SETTING_INSTRUCTIONS);

    if ('true' === $plugin->get(ExerciseMonitoringPlugin::SETTING_INSTRUCTION_AGE_DISTINCTION_ENABLE)) {
        $_template['instructions'] = $plugin->get(ExerciseMonitoringPlugin::SETTING_INSTRUCTIONS_MINORS);

        if ($isAdult) {
            $_template['instructions'] = $plugin->get(ExerciseMonitoringPlugin::SETTING_INSTRUCTIONS_ADULTS);
        } else {
            $_template['enable_snapshots'] = false;
        }
    }

    $_template['instructions'] = Security::remove_XSS($_template['instructions']);

    $learnpathId = isset($_REQUEST['learnpath_id']) ? (int) $_REQUEST['learnpath_id'] : 0;
    $learnpathItemId = isset($_REQUEST['learnpath_item_id']) ? (int) $_REQUEST['learnpath_item_id'] : 0;

    $exerciseStatInfo = $exercise->get_stat_track_exercise_info($learnpathId, $learnpathItemId);

    $_template['exercise_is_started'] = (int) !empty($exerciseStatInfo);
}

if ($showSubmitRegion && $_template['enabled']) {
    if ($readExercise) {
        $_template['exercise_type'] = (int) $exercise->selectType();

        if ('true' === $plugin->get(ExerciseMonitoringPlugin::SETTING_INSTRUCTION_AGE_DISTINCTION_ENABLE)
            && !$isAdult
        ) {
            $_template['enable_snapshots'] = false;
        }

        if (true === $_template['enable_snapshots']) {
            if ($exercise->expired_time != 0) {
                $quizTimeLeft = ChamiloSession::read('quiz_time_left');

                $_template['quiz_time_left'] = rand(1, $quizTimeLeft) * 1000;
            }
        }
    }
}
