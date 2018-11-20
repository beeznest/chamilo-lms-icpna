<?php
/* For licensing terms, see /license.txt */

/**
 * Generate a report to the English contest
 *
 * Refs BT#13600
 */

//exit();

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;

if (PHP_SAPI !== 'cli') {
    //die('This script can only be executed from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

if (!api_is_platform_admin()) {
    die;
}

$courseId = api_get_course_int_id();
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
    '',
    '',
    'Etapa preliminar',
    '',
    '',
    '',
    '',
    'Etapa final',
    '',
    '',
    '',
    '',
    '',
    '',
];
$data[] = [
    'Apellidos',
    'Nombres',
    'DNI',
    'Colegio',
    'Nivel',
    'Grado',
    'Ciudad',
    'Provincia',
    'Grammar',
    //'Listening',
    'Reading',
    'Vocabulary',
    'Respuestas correctas',
    'Número de preguntas',
    'Grammar',
    'Listening',
    'Reading',
    'Vocabulary',
    'Respuestas correctas',
    'Número de preguntas',
    'Puntaje total',
];

$exercises = getExercise($courseId);
$realExercisesPRE = array_filter($exercises, function (array $exerciseInfo) {
    return substr($exerciseInfo['title'], 3, 3) === 'PRE';
});
$realExercisesFIN = array_filter($exercises, function (array $exerciseInfo) {
    return substr($exerciseInfo['title'], 3, 3) === 'FIN';
});

$studentSubscriptions = SessionManager::getUsersByCourseSession($sessionId, $courseInfo, Session::STUDENT);

foreach ($studentSubscriptions as $studentId) {
    $student = api_get_user_info($studentId, false, false, true);

    if (empty($student['username'])) {
        continue;
    }

    $efvDni = '';
    $efvColegio = '';
    $efvNivel = '';
    $efvGrado = '';
    $efvCiudad = '';
    $efvProvincia = '';

    foreach ($student['extra'] as $efvInfo) {
        /** @var ExtraFieldValues|null $efv */
        $efv = $efvInfo['value'];

        if (null === $efv) {
            continue;
        }

        /** @var ExtraField $ef */
        $ef = $efv->getField();

        //if ($ef->getVariable() === 'id_document_number') {
        //    $efvDni = $efv->getValue();
        //}

        if ($ef->getVariable() === 'colegio_-_centro_educativo') {
            $efvColegio = $efv->getValue();
        }

        if ($ef->getVariable() === 'grado') {
            $efvGrado = $efv->getValue();
        }

        if ($ef->getVariable() === 'nivel') {
            $efvNivel = $efv->getValue();
        }

        if ($ef->getVariable() === 'localidad_(ciudad)') {
            $efvCiudad = $efv->getValue();
        }

        if ($ef->getVariable() === 'localidad_(provincia)') {
            $efvProvincia = $efv->getValue();
        }
    }

    $userData = [];
    $userData[] = $student['lastname'];
    $userData[] = $student['firstname'];
    $userData[] = $student['username'];
    $userData[] = $efvColegio;
    $userData[] = $efvNivel;
    $userData[] = $efvGrado;
    $userData[] = $efvCiudad;
    $userData[] = $efvProvincia;

    $resultsPRE = [];
    $resultsFIN = [];

    foreach ($realExercisesPRE as $realExercise) {
        $results = getCategoryResults(
            $courseId,
            $realExercise['id'],
            $student['user_id'],
            $sessionId
        );

        if ($results) {
            $resultsPRE = $results;
            break;
        }
    }

    foreach ($realExercisesFIN as $realExercise) {
        $results = getCategoryResults(
            $courseId,
            $realExercise['id'],
            $student['user_id'],
            $sessionId
        );

        if ($results) {
            $resultsFIN = $results;
            break;
        }
    }

    if (!$resultsPRE && !$resultsFIN) {
        fillEmptyData(12, $userData);

        $data[] = $userData;

        continue;
    }

    $totalScore = 0;

    if ($resultsPRE) {
        $totalPreScore = 0;
        $totalPreStage = 0;

        foreach ($resultsPRE as $stageResult) {
            $parts = explode(' / ', $stageResult);
            $totalPreScore += $parts[0];
            $totalPreStage += $parts[1];

            $userData[] = $stageResult;
        }

        $totalScore += $totalPreScore;

        $userData[] = $totalPreScore;
        $userData[] = $totalPreStage;
    } else {
        fillEmptyData(5, $userData);
    }

    if ($resultsFIN) {
        $totalFinScore = 0;
        $totalFinStage = 0;

        foreach ($resultsFIN as $stageResult) {
            $parts = explode(' / ', $stageResult);
            $totalFinScore += $parts[0];
            $totalFinStage += $parts[1];

            $userData[] = $stageResult;
        }

        $totalScore += $totalFinScore;

        $userData[] = $totalFinScore;
        $userData[] = $totalFinStage;
    } else {
        fillEmptyData(6, $userData);
    }

    $userData[] = $totalScore;

    $data[] = $userData;
}

/**
 * @param int   $cels
 * @param array $array
 */
function fillEmptyData($cels, array &$array) {
    for ($i = 1; $i <= $cels; $i++) {
        $array[] = '';
    }
}

//Export::export_table_csv($data, $courseInfo['title']);

function getExercise($courseId, $sessionId = 0)
{
    $tblExercise = Database::get_course_table(TABLE_QUIZ_TEST);
    $conditionSession = api_get_session_condition($sessionId, true, true);

    $sql = "
        SELECT id, title
        FROM $tblExercise
        WHERE c_id = $courseId AND active != -1 $conditionSession";
    $result = Database::query($sql);

    return Database::store_result($result);
}

function getBestAttemtpByUserWithLP($studentId, $execiseId, $courseId, $sessionId = 0)
{
    $user_results = getAllExerciseResultsWithLP($execiseId, $courseId, $sessionId, false, $studentId);
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
    $courseId,
    $session_id = 0,
    $load_question_list = true,
    $user_id = null
) {
    $TABLETRACK_EXERCICES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
    $TBL_TRACK_ATTEMPT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $courseId = Database::escape_string($courseId);
    $exercise_id = intval($exercise_id);
    $session_id = intval($session_id);

    $user_condition = null;
    if (!empty($user_id)) {
        $user_id = intval($user_id);
        $user_condition = "AND exe_user_id = $user_id ";
    }
    $sql = "SELECT * FROM $TABLETRACK_EXERCICES
            WHERE   status = ''  AND
                    c_id = '$courseId' AND
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
    $TBL_TRACK_EXERCICES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
    $TBL_TRACK_ATTEMPT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
    $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);

    $user_restriction = "AND user_id=".intval($student_id);
    $query = "SELECT attempts.question_id, attempts.answer FROM ".$TBL_TRACK_ATTEMPT." as attempts
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

function getCategoryResults($courseId, $exerciseId, $studentId, $sessionId)
{
    $tblTrackAttempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $totalScore = $totalWeighting = 0;
    $showResults = false;

    $exercise = new Exercise($courseId);
    $exercise->read($exerciseId);
    $questionList = $exercise->selectQuestionList();

    $categoryNameList = Testcategory::getListOfCategoriesNameForTest($exercise->id);
    asort($categoryNameList);

    $categoryList = [];

    $attempt = getBestAttemtpByUserWithLP($studentId, $exercise->id, $courseId, $sessionId);

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

        if (!empty($objQuestionTmp->category)) {
            if (empty($categoryList[$objQuestionTmp->category])) {
                $categoryList[$objQuestionTmp->category] = ['score' => 0, 'total' => 0];
            }

            $categoryList[$objQuestionTmp->category]['score'] += $myTotalScore;
            $categoryList[$objQuestionTmp->category]['total'] += $myTotalWeight;
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

Export::arrayToXls($data);
