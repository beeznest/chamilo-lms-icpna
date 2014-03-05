<?php
$language_file = array('registration', 'messages', 'userInfo');
require_once '../inc/global.inc.php';
$plexcode = CourseManager::get_course_code_from_original_id(50,'cs_course_id');
$isAdult = !empty($plexcode);
if ($isAdult) {
    define('NUM_COURSES', 5);
    define('NUM_PHASES', 5);
    define('TOTAL_COURSES', NUM_COURSES * NUM_PHASES);
} else {
    define('NUM_COURSES', 6);
    define('NUM_PHASES', 4);
    define('TOTAL_COURSES', NUM_COURSES * NUM_PHASES);
}
$phase_title = array(
    1 => 'Elementary',
    2 => 'High - Elementary',
    3 => 'Basic',
    4 => 'High - Basic',
    5 => 'Advanced',
);
if ($isAdult) {
    define('NUM_PHASES', 5);
    $phase = array(
        1 => array(1,2,3,4,5),
        2 => array(6,7,8,9,10),
        3 => array(11,12,13,14,15),
        4 => array(16,17,18,19,20),
        5 => array(21,22,23,24,25),
    );
} else {
    $phase = array(
        1 => array(1,2,3,4,5,6),
        2 => array(7,8,9,10,11,12),
        3 => array(13,14,15,16,17,18),
        4 => array(19,20,21,22,23,24),
    );
}
$course_array = getAllCourses();
function createDiv($seq, $sid) {
    $text = '';
    global $phase, $phase_title, $course_array;
    $index = $seq % NUM_COURSES;
    $phase_id = ceil($seq / NUM_COURSES);
    if ($seq <= TOTAL_COURSES) {
        if ($index == 1) {
            $text .= '<div class="row nivel">
                                <div class="span9">
                                   <h3 class="icon-nivel">' . $phase_title[$phase_id] . '</h3>
                                </div>';
        }
        if (empty($sid)) {
            $score = '--';
        } else {
            $score = getCourseScore($course_array[$seq][0],$course_array[$seq][1], $sid);
        }
        $text .= '<div class="span9">
                                    <div class="span3"><div class="icon-complet">' . $course_array[$seq][2] . '</div></div>
                                    <div class="span3"><div class="nota-aprueba top-note">' . $score . '/100</div></div>
                                </div>';
        if ($index == 0) {
            $text .= '</div>';
        }
    } else {
        $text .= '</div>
                    </div>
                </div>
        </div>';
    }
    return $text;
}

function twoOrMoreDigitString($num) {
    if ($num < 0) {
        return false;
    }
    if ($num < 10) {
        return '0' . $num;
    } else {
        return strval($num);
    }
}

function getAllCourses() {
    for($i = 1; $i <= TOTAL_COURSES; $i++) {
        if (CourseManager::course_code_exists('COURSE'.$i)) {
            $course_code = 'COURSE'. $i;
        } else {
            $course_code = 'COURSE'. twoOrMoreDigitString($i);
        }
        $id = CourseManager::get_course_id_from_course_code($course_code);
        $title = CourseManager::get_course_title_from_course_id($id);
        $course_array[$i] = array(
            $id,
            $course_code,
            $title,
        );
    }
    return $course_array;
}

/*
 *
 */
function getCourseScore($cid, $ccode, $sid, $uid = null) {
    $tbl_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
    // Limited list of terms that will be considered as exams that classify the user to move to next course
    $exam_names = "'final exam', 'examen final', 'placement test', 'final test', 'examen de clasificación'";

    $sql = "SELECT id, max_attempt FROM $tbl_quiz WHERE c_id = $cid AND LOWER(title) IN ($exam_names) ORDER BY id DESC LIMIT 1";
    $res = Database::query($sql);
    if (Database::num_rows($res) < 1) {
        return '--';
    }
    $row = Database::fetch_row($res);
    $qid = intval($row[0]);
    $maxAttempt = intval($row[1]);

    // From the results table, we have to check the latest attempt.
    // There is a special case for exams where only one attempt is allowed: if
    // the first attempt failed but was not finished, the user gets a second
    // attempt. As such, in the case where only one attempt is allowed
    // (c_quiz.max_attempt = 1) and we have more than one attempt, of which the
    // first was not finished (track_e_exercices.status != ''), we have to
    // take the results from the second attempt (but not more)
    $tbl_res = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
    $score = 0;
    if ($maxAttempt == 1) {
        // Adults case, only one attempt but if first unfinished, we take the
        // second one
        $sql = "SELECT exe_result, exe_weighting, status
            FROM $tbl_res
            WHERE exe_exo_id = $qid
                AND exe_cours_id = '$ccode'
                AND session_id = $sid
            ORDER BY start_date ASC LIMIT 2";
        $res = Database::query($sql);
        if (Database::num_rows($res) < 1) {
            return '--';
        } elseif (Database::num_rows($res) == 1) {
            $row = Database::fetch_row($res);
            $tempScore = round(($row[0]/$row[1])*100,0);
            if ($tempScore < 70 && $row[2]!= '') {
                // return empty array so this score is not taken into account
                return '--';
            }
            $score = $tempScore;
        } else {
            $lastScore = 0;
            // only scan 2 rows, thanks to the LIMIT 2 above
            while ($row = Database::fetch_row($res)) {
                $tempScore = round(($row[0]/$row[1])*100,0);
                if ($tempScore > $lastScore) {
                    $lastScore = $tempScore;
                }
            }
            // return the best score
            $score = $lastScore;
        }
    } else {
        // There are 3 attempts to these tests. As soon as one is > 70, send result
        $sql = "SELECT exe_result, exe_weighting, status
            FROM $tbl_res
            WHERE exe_exo_id = $qid
                AND exe_cours_id = '$ccode'
                AND session_id = $sid
            ORDER BY start_date ASC LIMIT 3";
        $res = Database::query($sql);
        if (Database::num_rows($res) < 1) {
            return '--';
        }
        $lastScore = 0;
        $count = 0;
        // only scan max 3 rows, thanks to the LIMIT 2 above
        while ($row = Database::fetch_row($res)) {
            $tempScore = round(($row[0]/$row[1])*100,0);
            if ($tempScore > $lastScore) {
                $lastScore = $tempScore;
            }
            $count++;
        }
        if ($lastScore >= 70) {
            // return the success score
            $score = $lastScore;
        } else {
            if ($count == 3) {
                // reached maximum attempts, return bad result
                $score = $lastScore;
            } else {
                return '--';
            }
        }
    }
    return $score;
}

$user_id = api_get_user_id();
$social_left_content = $social_left_content = SocialManager::show_social_menu('myperformance');
if (!empty($user_id)) {
    $social_right_content =
        '<div class="well_border">
                <div class="span9">
                    <div class="row">
                        <h3>Mi Desempeño</h3>
                        <div class="span8">
                            <div class="row seccion-info-notas">
                                <div class="span4"><div class="letra">Courses</div></div>
                                <div class="span3"><div class="letra">Notes</div></div>
                            </div>';
    $session_list = SessionManager::get_course_session_list_by_user($user_id);
    $sequence_int = 0;
    foreach ($session_list as $session) {
        $course_code = $session['course_code'];
        $sequence_int = intval(preg_replace('/\D/','',$course_code));
        $sid = $session['id_session'];
        $course_sequences[$sequence_int] = $sid;
    }
    sort($course_sequences = array_unique($course_sequences));
    for ($i = 1 ; $i <= (TOTAL_COURSES + 1) ; $i++) {
        $social_right_content .= createDiv($i,$course_sequences[$i]);
        if ($i > TOTAL_COURSES) {
            break;
        }
    }
}
$tpl = new Template(null);
$tpl->assign('social_left_content', $social_left_content);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('layout/social_layout.tpl');
$tpl->display($social_layout);