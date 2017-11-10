<?php
/* For licensing terms, see /license.txt */

/**
 * Generate a report to the English contest
 *
 * Refs BT#13600
 */

//exit();

if (PHP_SAPI !== 'cli') {
    //die('This script can only be executed from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

if (!api_is_platform_admin()) {
    die;
}

$courseCode = api_get_course_id();
$sessionId = api_get_session_id();

$courseInfo = api_get_course_info($courseCode);

$data = [];
$data[] = [
    'Datos candidato',
    '',
    '',
    '',
    '',
    '',
    'Etapa preliminar',
    '',
    //'',
    '',
    'Etapa final',
    '',
    '',
    '',
];
$data[] = [
    'Apellidos',
    'Nombres',
    'Colegio',
    'Nivel',
    'Grado',
    'Localidad',
    'Grammar',
    //'Listening',
    'Reading',
    'Vocabulary',
    'Grammar',
    'Listening',
    'Reading',
    'Vocabulary',
];

$studentSubscriptions = CourseManager::get_student_list_from_course_code($courseCode);

foreach ($studentSubscriptions as $subscriptionId => $subscription) {
    $student = api_get_user_info($subscription['user_id'], false, false, true);

    $userData = [];
    $userData[] = $student['lastname'];
    $userData[] = $student['firstname'];
    $userData[] = $student['extra_fields']['extra_colegio'];
    $userData[] = $student['extra_fields']['extra_nivel'];
    $userData[] = $student['extra_fields']['extra_grado'];
    $userData[] = $student['extra_fields']['extra_lugar'];


    $realExercisePRE = getExercise(
        substr($student['extra_fields']['extra_grado'], 0, 1).substr($student['extra_fields']['extra_nivel'], 0, 1),
        'PRE',
        $courseInfo['real_id']
    );

    $realExerciseFIN = getExercise(
        substr($student['extra_fields']['extra_grado'], 0, 1).substr($student['extra_fields']['extra_nivel'], 0, 1),
        'FIN',
        $courseInfo['real_id']
    );

    $resultsPRE = getCategoryResults(
        $courseInfo['real_id'],
        $courseInfo['id'],
        $realExercisePRE['id'],
        $student['user_id']
    );
    $resultsFIN = getCategoryResults(
        $courseInfo['real_id'],
        $courseInfo['id'],
        $realExerciseFIN['id'],
        $student['user_id']
    );

    if (!$resultsPRE && !$resultsFIN) {
        continue;
    }

    if ($resultsPRE) {
        foreach ($resultsPRE as $stageResult) {
            $userData[] = $stageResult;
        }
    } else {
        $userData[] = '-';
        //$userData[] = '-';
        $userData[] = '-';
        $userData[] = '-';
    }

    if ($resultsFIN) {
        foreach ($resultsFIN as $stageResult) {
            $userData[] = $stageResult;
        }
    } else {
        $userData[] = '-';
        $userData[] = '-';
        $userData[] = '-';
        $userData[] = '-';
    }

    $data[] = $userData;
}

//Export::export_table_csv($data, $courseInfo['title']);

function getExercise($requiredGrade, $stage, $courseId, $sessionId = 0)
{
    $tblExercise = Database::get_course_table(TABLE_QUIZ_TEST);
    $conditionSession = api_get_session_condition($sessionId, true, true);

    $exerciseList = [];
    $sql = "
        SELECT id, title, display_category_name
        FROM $tblExercise
        WHERE c_id = $courseId AND active != -1 $conditionSession";
    $result = Database::query($sql);

    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $exerciseList[$row['id']] = $row;
    }

    foreach ($exerciseList as $exerciseInfo) {
        $exerciseGrade = substr($exerciseInfo['title'], 0, 2);
        $exerciseStage = substr($exerciseInfo['title'], -3);

        if ($exerciseGrade === $requiredGrade && $exerciseStage === $stage) {
            return $exerciseInfo;
        }
    }

    return [];
}

function getBestAttemtpByUserWithLP($studentId, $execiseId, $courseCode, $sessionId = 0)
{
    $user_results = getAllExerciseResultsWithLP($execiseId, $courseCode, $sessionId, false, $studentId);
    $best_score_data = array();
    $best_score = 0;
    if (!empty($user_results)) {
        foreach ($user_results as $result) {
            if (!empty($result['exe_weighting']) && intval($result['exe_weighting']) != 0) {
                $score = $result['exe_result'] / $result['exe_weighting'];
                if ($score >= $best_score) {
                    $best_score = $score;
                    $best_score_data = $result;
                }
            }
        }
    }

    return $best_score_data;
}

function getAllExerciseResultsWithLP(
    $exercise_id,
    $course_code,
    $session_id = 0,
    $load_question_list = true,
    $user_id = null
) {
    $TABLETRACK_EXERCICES = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
    $TBL_TRACK_ATTEMPT = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $course_code = Database::escape_string($course_code);
    $exercise_id = intval($exercise_id);
    $session_id = intval($session_id);

    $user_condition = null;
    if (!empty($user_id)) {
        $user_id = intval($user_id);
        $user_condition = "AND exe_user_id = $user_id ";
    }
    $sql = "SELECT * FROM $TABLETRACK_EXERCICES
            WHERE   status = ''  AND
                    exe_cours_id = '$course_code' AND
                    exe_exo_id = '$exercise_id' AND
                    session_id = $session_id
                    $user_condition
            ORDER BY exe_id";
    $res = Database::query($sql);
    $list = array();
    while ($row = Database::fetch_array($res, 'ASSOC')) {
        $list[$row['exe_id']] = $row;
        if ($load_question_list) {
            $sql = "SELECT * FROM $TBL_TRACK_ATTEMPT WHERE exe_id = {$row['exe_id']}";
            $res_question = Database::query($sql);
            while ($row_q = Database::fetch_array($res_question, 'ASSOC')) {
                $list[$row['exe_id']]['question_list'][$row_q['question_id']] = $row_q;
            }
        }
    }

    return $list;
}

function getExerciseResult($attemptId, $student_id, $courseId)
{
    $TBL_TRACK_EXERCICES = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
    $TBL_TRACK_ATTEMPT = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
    $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);

    $user_restriction = "AND user_id=".intval($student_id);
    $query = "SELECT attempts.question_id, answer FROM ".$TBL_TRACK_ATTEMPT." as attempts
				INNER JOIN ".$TBL_TRACK_EXERCICES." AS stats_exercices ON stats_exercices.exe_id=attempts.exe_id
				INNER JOIN ".$TBL_EXERCICE_QUESTION." AS quizz_rel_questions
				    ON quizz_rel_questions.exercice_id=stats_exercices.exe_exo_id
				    AND quizz_rel_questions.question_id = attempts.question_id
				    AND quizz_rel_questions.c_id=".$courseId."
				INNER JOIN ".$TBL_QUESTIONS." AS questions
				    ON questions.id=quizz_rel_questions.question_id
				    AND questions.c_id = ".$courseId."
		  WHERE attempts.exe_id='".Database::escape_string($attemptId)."' $user_restriction
		  GROUP BY quizz_rel_questions.question_order, attempts.question_id";

    $result = Database::query($query);
    $question_list_from_database = array();
    $exerciseResult = array();

    while ($row = Database::fetch_array($result)) {
        $question_list_from_database[] = $row['question_id'];
        $exerciseResult[$row['question_id']] = $row['answer'];
    }

    return $exerciseResult;
}

function getCategoryResults($courseId, $courseCode, $exerciseId, $studentId)
{
    $tblTrackAttempt = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $totalScore = $totalWeighting = 0;
    $showResults = false;

    $exercise = new Exercise($courseId);
    $exercise->read($exerciseId);
    $questionList = $exercise->selectQuestionList();

    $categoryNameList = Testcategory::getListOfCategoriesNameForTest($exercise->id);
    asort($categoryNameList);

    $categoryList = [];

    $attempt = getBestAttemtpByUserWithLP($studentId, $exercise->id, $courseCode);

    if (!$attempt) {
        return [];
    }

    $exerciseResult = getExerciseResult($attempt['exe_id'], $studentId, $courseId);

    foreach ($questionList as $questionId) {
        $choice = $exerciseResult[$questionId];

        $objQuestionTmp = Question::read($questionId, $courseId);
        $questionWeighting = $objQuestionTmp->selectWeighting();
        $answerType = $objQuestionTmp->selectType();

        if ($answerType == MULTIPLE_ANSWER || $answerType == MULTIPLE_ANSWER_TRUE_FALSE) {
            $question_result = $exercise->manageAnswerWithoutHTML(
                $attempt['exe_id'],
                $questionId,
                $choice,
                'exercise_show',
                array(),
                false,
                true,
                $showResults,
                $exercise->selectPropagateNeg()
            );
            $questionScore = $question_result['score'];
            $totalScore += $question_result['score'];
        } elseif ($answerType == MULTIPLE_ANSWER_COMBINATION || $answerType == MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE) {
            $choice = array();
            $question_result = $exercise->manageAnswerWithoutHTML(
                $attempt['exe_id'],
                $questionId,
                $choice,
                'exercise_show',
                array(),
                false,
                true,
                $showResults,
                $exercise->selectPropagateNeg()
            );
            $questionScore = $question_result['score'];
            $totalScore += $question_result['score'];
        } elseif ($answerType == UNIQUE_ANSWER || $answerType == UNIQUE_ANSWER_NO_OPTION) {
            $question_result = $exercise->manageAnswerWithoutHTML(
                $attempt['exe_id'],
                $questionId,
                $choice,
                'exercise_show',
                array(),
                false,
                true,
                $showResults,
                $exercise->selectPropagateNeg()
            );
            $questionScore = $question_result['score'];
            $totalScore += $question_result['score'];
        } elseif ($answerType == FILL_IN_BLANKS) {
            $question_result = $exercise->manageAnswerWithoutHTML(
                $attempt['exe_id'],
                $questionId,
                $choice,
                'exercise_show',
                array(),
                false,
                true,
                $showResults,
                $exercise->selectPropagateNeg()
            );
            $questionScore = $question_result['score'];
            $totalScore += $question_result['score'];
        } elseif ($answerType == GLOBAL_MULTIPLE_ANSWER) {
            $question_result = $exercise->manageAnswerWithoutHTML(
                $attempt['exe_id'],
                $questionId,
                $choice,
                'exercise_show',
                array(),
                false,
                true,
                $showResults,
                $exercise->selectPropagateNeg()
            );
            $questionScore = $question_result['score'];
            $totalScore += $question_result['score'];
        } elseif ($answerType == FREE_ANSWER) {
            $question_result = $exercise->manageAnswerWithoutHTML(
                $attempt['exe_id'],
                $questionId,
                $choice,
                'exercise_show',
                array(),
                false,
                true,
                $showResults,
                $exercise->selectPropagateNeg()
            );
            $questionScore = $question_result['score'];
            $totalScore += $question_result['score'];
        } elseif ($answerType == ORAL_EXPRESSION) {
            $question_result = $exercise->manageAnswerWithoutHTML(
                $attempt['exe_id'],
                $questionId,
                $choice,
                'exercise_show',
                array(),
                false,
                true,
                $showResults,
                $exercise->selectPropagateNeg()
            );
            $questionScore = $question_result['score'];
            $totalScore += $question_result['score'];
        } elseif ($answerType == MATCHING || $answerType == MATCHING_DRAG || $answerType == DRAGGABLE) {
            $question_result = $exercise->manageAnswerWithoutHTML(
                $attempt['exe_id'],
                $questionId,
                $choice,
                'exercise_show',
                array(),
                false,
                true,
                $showResults,
                $exercise->selectPropagateNeg()
            );
            $questionScore = $question_result['score'];
            $totalScore += $question_result['score'];
        } elseif ($answerType == HOT_SPOT) {
            $question_result = $exercise->manageAnswerWithoutHTML(
                $attempt['exe_id'],
                $questionId,
                $choice,
                'exercise_show',
                array(),
                false,
                true,
                $showResults,
                $exercise->selectPropagateNeg()
            );
            $questionScore = $question_result['score'];
            $totalScore += $question_result['score'];
        } elseif ($answerType == HOT_SPOT_DELINEATION) {
            $question_result = $exercise->manageAnswerWithoutHTML(
                $attempt['exe_id'],
                $questionId,
                $choice,
                'exercise_show',
                array(),
                false,
                true,
                $showResults,
                $exercise->selectPropagateNeg(),
                'database'
            );

            $questionScore = $question_result['score'];
            $totalScore += $question_result['score'];

            if ($showResults) {
                //showing the score
                $queryfree = "
                    SELECT marks FROM $tblTrackAttempt
                    WHERE exe_id = '".Database::escape_string($attempt['exe_id'])."'
                    and question_id= '".Database::escape_string($questionId)."'";
                $resfree = Database::query($queryfree);
                $questionScore = Database::result($resfree, 0, "marks");
                $totalScore += $questionScore;
            }
        }

        if ($showResults) {
            if (in_array($answerType, array(FREE_ANSWER, ORAL_EXPRESSION))) {
                if ($questionScore == -1) {
                    $questionScore = 0;
                }
            }
        }

        $myTotalScore = $questionScore;
        $myTotalWeight = $questionWeighting;

        if (isset($objQuestionTmp->category_list) && !empty($objQuestionTmp->category_list)) {
            foreach ($objQuestionTmp->category_list as $categoryId) {
                $categoryList[$categoryId]['score'] += $myTotalScore;
                $categoryList[$categoryId]['total'] += $myTotalWeight;
            }
        }
    }

    $results = [];

    foreach ($categoryNameList as $categoryNameId => $categoryName) {
        foreach ($categoryList as $categoryId => $categoryItem) {
            if ($categoryNameId == $categoryId) {
                $results[] = showScore($categoryItem['score'], $categoryItem['total'], false);
            }
        }
    }

    return $results;
}

function showScore($score, $weight, $show_percentage = true, $use_platform_settings = true, $show_only_percentage = false)
{
    if (is_null($score) && is_null($weight)) {
        return '-';
    }

    $max_note = api_get_setting('exercise_max_score');
    $min_note = api_get_setting('exercise_min_score');

    if ($use_platform_settings) {
        if ($max_note != '' && $min_note != '') {
            if (!empty($weight) && intval($weight) != 0) {
                $score = $min_note + ($max_note - $min_note) * $score / $weight;
            } else {
                $score = $min_note;
            }
            $weight = $max_note;
        }
    }
    $percentage = (100 * $score) / ($weight != 0 ? $weight : 1);

    //Formats values
    $percentage = float_format($percentage, 1);
    $score = float_format($score, 1);
    $weight = float_format($weight, 1);

    $html = null;
    if ($show_percentage) {
        $parent = '('.$score.' / '.$weight.')';
        $html = $percentage." %  $parent";
        if ($show_only_percentage) {
            $html = $percentage."% ";
        }
    } else {
        $html = $score.' / '.$weight;
    }

    return $html;
}

?>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
        * {
            font-family: sans-serif;
            font-size: 11px;
            padding: 0;
            margin: 0;
        }

        table {
            margin: 0 auto;
            width: 100%;
            padding: 10px;
            border-collapse: collapse;
        }

        td {
            border: 2px solid #CCC;
            padding: 2px;
        }
    </style>
</head>
<body>
<table>
    <?php foreach ($data as $row) { ?>
        <tr>
            <?php foreach ($row as $cell) { ?>
                <td><?php echo $cell ?></td>
            <?php } ?>
        </tr>
    <?php } ?>
</table>
</body>
</html>
