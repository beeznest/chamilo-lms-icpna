<?php

/* For licensing terms, see /license.txt */
/**
 * This script displays statistics on the current learning path (scorm)
 * This script must be included by lp_controller.php to get basic initialisation
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 * @todo clean this file like the exercise files J.M
 */
/**
 * Code
 */
require_once 'learnpath.class.php';
require_once 'resourcelinker.inc.php';
require_once '../exercice/exercise.lib.php';

$course_code = api_get_course_id();

if (empty($user_id)) {
    $user_id = api_get_user_id();
}

// Declare variables to be used in lp_stats.php
//When checking the reporting myspace/lp_tracking.php
//isset($_GET['lp_id']) &&
if (isset($lp_id) && !empty($lp_id)) {
    $lp_id = intval($lp_id);
    if (!isset($list)) {
        $list = learnpath::get_flat_ordered_items_list($lp_id);
    }
} else {
    if (isset($_SESSION['oLP'])) {
        $lp_id = $_SESSION['oLP']->get_id();
        $list = learnpath::get_flat_ordered_items_list($lp_id);
    }
}

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

if (isset($_GET['course'])) {
    $course_code = Security::remove_XSS($_GET['course']);
}

$course_info = api_get_course_info($course_code);
$course_id = $course_info['real_id'];

if (isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);
}
$session_id = api_get_session_id();
$session_condition = api_get_session_condition($session_id);

//When origin is not set that means that the lp_stats are viewed from the "man running" icon
if (!isset($origin)) {
    $origin = 'learnpath';
}

//Origin = tracking means that teachers see that info in the Reporting tool
if ($origin != 'tracking') {
    Display::display_reduced_header();
}

$output = '';

//Extend all button
$extend_all_link = '';
$extend_all = 0;
if ($origin == 'tracking') {
    $url_suffix = '&session_id=' . $session_id . '&course=' . Security::remove_XSS($_GET['course']) . '&student_id=' . $student_id . '&lp_id=' . Security::remove_XSS($_GET['lp_id']) . '&origin=' . Security::remove_XSS($_GET['origin']) . $from_link;
} else {
    $url_suffix = '&lp_id=' . $lp_id;
}
if (!empty($_GET['extend_all'])) {
    $extend_all_link = '<a href="' . api_get_self() . '?action=stats' . $url_suffix . '"><img src="../img/view_less_stats.gif" alt="fold_view" border="0" title="' . get_lang('HideAllAttempts') . '"></a>';
    $extend_all = 1;
} else {
    $extend_all_link = '<a href="' . api_get_self() . '?action=stats&extend_all=1' . $url_suffix . '"><img src="../img/view_more_stats.gif" alt="extend_view" border="0" title="' . get_lang('ShowAllAttempts') . '"></a>';
}

if ($origin != 'tracking') {
    $output .= Display::page_header(get_lang('ScormMystatus'));
}

$output .= '<table class="data_table">
            <tr>
                <th width="16">' . $extend_all_link . '</th>
                <th colspan="4">
                    ' . get_lang('ScormLessonTitle') .'
                </th>
                <th colspan="2">
                    ' . get_lang('ScormStatus') . '
                </th>
                <th colspan="2">
                    ' . get_lang('ScormScore') . '
                </th>
                <th colspan="2">
                    ' . get_lang('ScormTime') . '
                </th>
                <th>
                    ' . get_lang('Actions') . '
                </th>
           </tr>';

// Going through the items using the $items[] array instead of the database order ensures
// we get them in the same order as in the imsmanifest file, which is rather random when using
// the database table.

$TBL_LP_ITEM = Database :: get_course_table(TABLE_LP_ITEM);
$TBL_LP_ITEM_VIEW = Database :: get_course_table(TABLE_LP_ITEM_VIEW);
$TBL_LP_VIEW = Database :: get_course_table(TABLE_LP_VIEW);
$tbl_quiz_questions = Database :: get_course_table(TABLE_QUIZ_QUESTION);
$TBL_QUIZ = Database :: get_course_table(TABLE_QUIZ_TEST);
$tbl_stats_exercices = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$tbl_stats_attempts = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

$sql = "SELECT max(view_count) FROM $TBL_LP_VIEW WHERE c_id = $course_id AND lp_id = $lp_id AND user_id = '" . $user_id . "' $session_condition";
$res = Database::query($sql);
$view = '';
$num = 0;
if (Database :: num_rows($res) > 0) {
    $myrow = Database :: fetch_array($res);
    $view = $myrow[0];
}

$counter = 0;
$total_score = 0;
$total_time = 0;
$h = get_lang('h');

if (!empty($export_csv)) {
    $csv_content[] = array(
        get_lang('ScormLessonTitle'),
        get_lang('ScormStatus'),
        get_lang('ScormScore'),
        get_lang('ScormTime')
    );
}

// Get attempts of a exercise.
if (isset($_GET['lp_id']) && isset($_GET['lp_item_id'])) {
    $clean_lp_item_id = Database::escape_string($_GET['lp_item_id']);
    $clean_lp_id = Database::escape_string($_GET['lp_id']);
    $clean_course_code = Database :: escape_string($course_code);
    $sql_path = "SELECT path FROM $TBL_LP_ITEM WHERE c_id = $course_id AND id = '$clean_lp_item_id' AND lp_id = '$clean_lp_id'";
    $res_path = Database::query($sql_path);
    $row_path = Database::fetch_array($res_path);

    if (Database::num_rows($res_path) > 0) {
        if ($origin != 'tracking') {
            $sql_attempts = 'SELECT * FROM ' . $tbl_stats_exercices . '
            				 WHERE  exe_exo_id="' . (int) $row_path['path'] . '" AND
                                    status <> "incomplete"  AND
                                    exe_user_id="' . api_get_user_id() . '" AND
                                    orig_lp_id = "' . (int) $clean_lp_id . '" AND
                                    orig_lp_item_id = "' . (int) $clean_lp_item_id . '" AND
                                    exe_cours_id="' . $clean_course_code . '"  AND
                                    session_id = ' . $session_id . '
                             ORDER BY exe_date';
        } else {
            $sql_attempts = 'SELECT * FROM ' . $tbl_stats_exercices . '
            				 WHERE  exe_exo_id="' . (int) $row_path['path'] . '" AND
                                    status <> "incomplete"  AND
                                    exe_user_id="' . $student_id . '" AND
                                    orig_lp_id = "' . (int) $clean_lp_id . '" AND
                                    orig_lp_item_id = "' . (int) $clean_lp_item_id . '" AND
                                    exe_cours_id="' . $clean_course_code . '"  AND
                                    session_id = ' . $session_id . '
                             ORDER BY exe_date';
        }
    }
    //var_dump($sql_attempts);
}

//Show lp items
if (is_array($list) && count($list) > 0) {
    foreach ($list as $my_item_id) {
        $extend_this = 0;
        $qry_order = 'DESC';
        if ((!empty($_GET['extend_id']) && $_GET['extend_id'] == $my_item_id) || $extend_all) {
            $extend_this = 1;
            $qry_order = 'ASC';
        }

        // Prepare statement to go through each attempt.
        if (!empty($view)) {
            $sql = "SELECT  iv.status as mystatus,
                            v.view_count as mycount,
                            iv.score as myscore,
                            iv.total_time as mytime,
                            i.id as myid,
                            i.lp_id as mylpid,
                            iv.lp_view_id as mylpviewid,
                            i.title as mytitle,
                            i.max_score as mymaxscore,
                            iv.max_score as myviewmaxscore,
                            i.item_type as item_type,
                            iv.view_count as iv_view_count,
                            iv.id as iv_id,
                            path
                    FROM $TBL_LP_ITEM as i
                    INNER JOIN $TBL_LP_ITEM_VIEW as iv ON (i.id = iv.lp_item_id  AND i.c_id = $course_id AND iv.c_id = $course_id)
                    INNER JOIN $TBL_LP_VIEW as v ON (iv.lp_view_id = v.id AND v.c_id = $course_id)
            WHERE
                i.id = $my_item_id AND
                i.lp_id = $lp_id  AND
                v.user_id = $user_id AND
                v.view_count = $view AND
                v.session_id = $session_id
            ORDER BY iv.view_count $qry_order ";
            //var_dump($sql);
        } else {
            $sql = "SELECT  iv.status as mystatus,
                            v.view_count as mycount,
                            iv.score as myscore,
                            iv.total_time as mytime,
                            i.id as myid,
                            i.lp_id as mylpid,
                            iv.lp_view_id as mylpviewid,
                            i.title as mytitle,
                            i.max_score as mymaxscore,
                            iv.max_score as myviewmaxscore,
                            i.item_type as item_type,
                            iv.view_count as iv_view_count,
                            iv.id as iv_id,
                            path
                    FROM $TBL_LP_ITEM as i
                    INNER JOIN $TBL_LP_ITEM_VIEW as iv ON (i.id = iv.lp_item_id  AND i.c_id = $course_id AND iv.c_id = $course_id)
                    INNER JOIN $TBL_LP_VIEW as v ON (iv.lp_view_id = v.id AND v.c_id = $course_id)
                    WHERE
                        i.id = $my_item_id AND
                        i.lp_id = $lp_id AND
                        v.user_id = $user_id AND
                        v.session_id = $session_id
                   ORDER BY iv.view_count $qry_order ";
        }
        $result = Database::query($sql);
        $num = Database :: num_rows($result);
        $time_for_total = 'NaN';

        //Extend all + extend scorm?
        if (($extend_this || $extend_all) && $num > 0) {
            $row = Database :: fetch_array($result);
            $result_disabled_ext_all = false;
            if ($row['item_type'] == 'quiz') {
                // Check results_disabled in quiz table.
                $my_path = Database::escape_string($row['path']);

                $sql = "SELECT results_disabled FROM $TBL_QUIZ WHERE c_id = $course_id AND id ='" . $my_path . "'";
                $res_result_disabled = Database::query($sql);
                $row_result_disabled = Database::fetch_row($res_result_disabled);

                if (Database::num_rows($res_result_disabled) > 0 && (int) $row_result_disabled[0] === 1) {
                    $result_disabled_ext_all = true;
                }
            }

            // If there are several attempts, and the link to extend has been clicked, show each attempt...
            if (($counter % 2) == 0) {
                $oddclass = 'row_odd';
            } else {
                $oddclass = 'row_even';
            }
            $extend_link = '';
            if (!empty($inter_num)) {
                $extend_link = '<a href="' . api_get_self() . '?action=stats&fold_id=' . $my_item_id . $url_suffix . '">
                                <img src="../img/visible.gif" alt="' . get_lang('HideAttemptView') . '" title="' . get_lang('HideAttemptView') . '"  border="0"></a>';
            }
            $title = $row['mytitle'];

            if (empty($title)) {
                $title = rl_get_resource_name(api_get_course_id(), $lp_id, $row['myid']);
            }

            if ($row['item_type'] != 'dokeos_chapter') {
                $correct_test_link = '-';
                $title = Security::remove_XSS($title);
                $output .= '<tr class="'.$oddclass.'">
                                <td>'.$extend_link.'</td>
                                <td colspan="4">
                                    '.$title.'
                                </td>
                                <td colspan="2"></td>
                                <td colspan="2"></td>
                                <td colspan="2"></td>
                                <td></td>
                            </tr>';
            }
            $counter++;

            do {
                // Check if there are interactions below.
                $extend_attempt_link = '';
                $extend_this_attempt = 0;

                if ((learnpath :: get_interactions_count_from_db($row['iv_id'], $course_id) > 0 || learnpath :: get_objectives_count_from_db($row['iv_id'], $course_id) > 0) && !$extend_all) {
                    if (!empty($_GET['extend_attempt_id']) && $_GET['extend_attempt_id'] == $row['iv_id']) {
                        // The extend button for this attempt has been clicked.
                        $extend_this_attempt = 1;
                        $extend_attempt_link = '<a href="' . api_get_self() . '?action=stats&extend_id=' . $my_item_id . '&fold_attempt_id=' . $row['iv_id'] . $url_suffix . '"><img src="../img/visible.gif" alt="' . get_lang('HideAttemptView') . '" title="' . get_lang('HideAttemptView') . '" border="0"></a>';
                    } else { // Same case if fold_attempt_id is set, so not implemented explicitly.
                        // The extend button for this attempt has not been clicked.
                        $extend_attempt_link = '<a href="' . api_get_self() . '?action=stats&extend_id=' . $my_item_id . '&extend_attempt_id=' . $row['iv_id'] . $url_suffix . '"><img src="../img/invisible.gif" alt="' . get_lang('ExtendAttemptView') . '" title="' . get_lang('ExtendAttemptView') . '"  border="0"></a>';
                    }
                }

                if (($counter % 2) == 0) {
                    $oddclass = 'row_odd';
                } else {
                    $oddclass = 'row_even';
                }

                $lesson_status = $row['mystatus'];
                $score = $row['myscore'];

                $time_for_total = $row['mytime'];

                $time = learnpathItem :: get_scorm_time('js', $row['mytime']);
                $scoIdentifier = $row['myid'];

                if ($score == 0) {
                    $maxscore = $row['mymaxscore'];
                } else {
                    if ($row['item_type'] == 'sco') {
                        if (!empty($row['myviewmaxscore']) && $row['myviewmaxscore'] > 0) {
                            $maxscore = $row['myviewmaxscore'];
                        } elseif ($row['myviewmaxscore'] === '') {
                            $maxscore = 0;
                        } else {
                            $maxscore = $row['mymaxscore'];
                        }
                    } else {
                        $maxscore = $row['mymaxscore'];
                    }
                }

                // Remove "NaN" if any (@todo: locate the source of these NaN)
                $time = str_replace('NaN', '00' . $h . '00\'00"', $time);

                if ($row['item_type'] != 'dokeos_chapter') {
                    if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                        $view_score = Display::return_icon('invisible.gif', get_lang('ResultsHiddenByExerciseSetting'));
                    } else {
                        switch ($row['item_type']) {
                            case 'sco':
                                if ($maxscore == 0) {
                                    $view_score = $score;
                                } else {
                                    $view_score = show_score($score, $maxscore, false);
                                }
                                break;
                            case 'document':
                                $view_score = ($score == 0 ? '/' : show_score($score, $maxscore, false));
                                break;
                            default:
                                $view_score = show_score($score, $maxscore, false);
                                break;
                        }
                    }
                    $output .= '<tr class="' . $oddclass . '">
                                    <td></td>
                                    <td>' . $extend_attempt_link . '</td>
                                    <td colspan="3">' . get_lang('Attempt') . ' ' . $row['iv_view_count'] . '</td>
                                    <td colspan="2">' . learnpathItem::humanize_status($lesson_status) . '</td>
                                    <td colspan="2">' . $view_score . '</td>
                                    <td colspan="2">' . $time . '</td>
                                    <td></td>
                                </tr>';

                    if (!empty($export_csv)) {
                        $temp = array();
                        $temp[] = $title = Security::remove_XSS($title);
                        $temp[] = Security::remove_XSS(learnpathItem::humanize_status($lesson_status, false));

                        if ($row['item_type'] == 'quiz') {
                            if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                $temp[] = '/';
                            } else {
                                $temp[] = ($score == 0 ? '0/' . $maxscore : ($maxscore == 0 ? $score : $score . '/' . float_format($maxscore, 1)));
                            }
                        } else {
                            $temp[] = ($score == 0 ? '/' : ($maxscore == 0 ? $score : $score . '/' . float_format($maxscore, 1)));
                        }
                        $temp[] = $time;
                        $csv_content[] = $temp;
                    }
                }

                $counter++;

                if ($extend_this_attempt OR $extend_all) {
                    $list1 = learnpath :: get_iv_interactions_array($row['iv_id']);

                    foreach ($list1 as $id => $interaction) {
                        if (($counter % 2) == 0) {
                            $oddclass = 'row_odd';
                        } else {
                            $oddclass = 'row_even';
                        }
                        $student_response = urldecode($interaction['student_response']); // Code added by Isaac Flores.
                        $content_student_response = array();
                        $content_student_response = explode('__|', $student_response);

                        if (count($content_student_response) > 0) {
                            if (count($content_student_response) >= 3) {
                                $new_content_student_response = array_pop($content_student_response); // Pop the element off the end of array.
                            }
                            $student_response = implode(',', $content_student_response);
                        }
                        $output .= '<tr class="'.$oddclass.'">
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>'.$interaction['order_id'] . '</td>
                                        <td>'.$interaction['id'] . '</td>
                                        <td colspan="2">' . $interaction['type'].'</td>
                                        <td>'.$student_response . '</td>
                                        <td>'.$interaction['result'] . '</td>
                                        <td>'.$interaction['latency'] . '</td>
                                        <td>'.$interaction['time'] . '</td>
                                        <td></td>
                                    </tr>';
                        $counter++;
                    }
                    $list2 = learnpath :: get_iv_objectives_array($row['iv_id']);

                    foreach ($list2 as $id => $interaction) {
                        if (($counter % 2) == 0) {
                            $oddclass = 'row_odd';
                        } else {
                            $oddclass = 'row_even';
                        }
                        $output .= '<tr class="'.$oddclass.'">
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>' . $interaction['order_id'] . '</td>
                                        <td colspan="2">' . $interaction['objective_id'] . '</td>
                                        <td colspan="2">' . $interaction['status'] .'</td>
                                        <td>' . $interaction['score_raw'] . '</td>
                                        <td>' . $interaction['score_max'] . '</td>
                                        <td>' . $interaction['score_min'] . '</td>
                                        <td></td>
                                     </tr>';
                        $counter++;
                    }
                }
            } while ($row = Database :: fetch_array($result));
        } elseif ($num > 0) {
            //Not extended

            $row = Database :: fetch_array($result, 'ASSOC');
            $my_id = $row['myid'];
            $my_lp_id = $row['mylpid'];
            $my_lp_view_id = $row['mylpviewid'];
            $my_path = $row['path'];

            $result_disabled_ext_all = false;

            if ($row['item_type'] == 'quiz') {
                // Check results_disabled in quiz table.
                $my_path = Database::escape_string($my_path);

                $sql = "SELECT results_disabled FROM $TBL_QUIZ WHERE c_id = $course_id AND id ='" . (int) $my_path . "'";
                $res_result_disabled = Database::query($sql);
                $row_result_disabled = Database::fetch_row($res_result_disabled);

                if (Database::num_rows($res_result_disabled) > 0 && (int) $row_result_disabled[0] === 1) {
                    $result_disabled_ext_all = true;
                }
            }

            // Check if there are interactions below
            $extend_this_attempt = 0;

            $inter_num = learnpath::get_interactions_count_from_db($row['iv_id'], $course_id);
            $objec_num = learnpath::get_objectives_count_from_db($row['iv_id'], $course_id);

            $extend_attempt_link = '';
            if (($inter_num > 0 || $objec_num > 0)) {
                if (!empty($_GET['extend_attempt_id']) && $_GET['extend_attempt_id'] == $row['iv_id']) {
                    // The extend button for this attempt has been clicked.
                    $extend_this_attempt = 1;
                    $extend_attempt_link = '<a href="' . api_get_self() . '?action=stats&extend_id=' . $my_item_id . '&fold_attempt_id=' . $row['iv_id'] . $url_suffix . '"><img src="../img/visible.gif" alt="' . get_lang('HideAttemptView') . '" title="' . get_lang('HideAttemptView') . '" border="0"></a>' . "\n";
                } else { // Same case if fold_attempt_id is set, so not implemented explicitly.
                    // The extend button for this attempt has not been clicked.
                    $extend_attempt_link = '<a href="' . api_get_self() . '?action=stats&extend_id=' . $my_item_id . '&extend_attempt_id=' . $row['iv_id'] . $url_suffix . '"><img src="../img/invisible.gif" alt="' . get_lang('ExtendAttemptView') . '" title="' . get_lang('ExtendAttemptView') . '" border="0"></a>' . "\n";
                }
            }

            if (($counter % 2) == 0) {
                $oddclass = 'row_odd';
            } else {
                $oddclass = 'row_even';
            }

            $extend_link = '';
            if ($inter_num > 1) {
                $extend_link = '<a href="' . api_get_self() . '?action=stats&extend_id=' . $my_item_id . '&extend_attempt_id=' . $row['iv_id'] . $url_suffix . '"><img src="../img/invisible.gif" alt="' . get_lang('ExtendAttemptView') . '" title="' . get_lang('ExtendAttemptView') . '"  border="0"></a>';
            }

            $lesson_status = $row['mystatus'];
            $score = $row['myscore'];
            $subtotal_time = $row['mytime'];

            while ($tmp_row = Database :: fetch_array($result)) {
                $subtotal_time += $tmp_row['mytime'];
            }
            $scoIdentifier = $row['myid'];
            $title = $row['mytitle'];

            // Selecting the exe_id from stats attempts tables in order to look the max score value.
            if ($origin != 'tracking') {
                $sql_last_attempt = 'SELECT * FROM ' . $tbl_stats_exercices . '
                                     WHERE  exe_exo_id="' . $row['path'] . '" AND
                                            exe_user_id="' . api_get_user_id() . '" AND
                                            orig_lp_id = "' . $lp_id . '" AND
                                            orig_lp_item_id = "' . $row['myid'] . '" AND
                                            exe_cours_id="' . $course_code . '" AND
                                            status <> "incomplete" AND
                                            session_id = ' . $session_id . '
                                     ORDER BY exe_date DESC limit 1';
            } else {
                $sql_last_attempt = 'SELECT * FROM ' . $tbl_stats_exercices . '
                                     WHERE  exe_exo_id="' . $row['path'] . '" AND
                                            exe_user_id="' . $student_id . '" AND
                                            orig_lp_id = "' . $lp_id . '" AND
                                            orig_lp_item_id = "' . $row['myid'] . '" AND
                                            exe_cours_id="' . $course_code . '" AND
                                            status <> "incomplete" AND
                                            session_id = ' . $session_id . '
                                     ORDER BY exe_date DESC limit 1';
            }

            $resultLastAttempt = Database::query($sql_last_attempt);
            $num = Database :: num_rows($resultLastAttempt);
            if ($num > 0) {
                while ($rowLA = Database :: fetch_array($resultLastAttempt)) {
                    $id_last_attempt = $rowLA['exe_id'];
                }
            }

            //var_dump($row['path'] .' '.$score);
            if ($score == 0) {
                $maxscore = $row['mymaxscore'];
            } else {
                if ($row['item_type'] == 'sco') {
                    if (!empty($row['myviewmaxscore']) and $row['myviewmaxscore'] > 0) {
                        $maxscore = $row['myviewmaxscore'];
                    } elseif ($row['myviewmaxscore'] === '') {
                        $maxscore = 0;
                    } else {
                        $maxscore = $row['mymaxscore'];
                    }
                } else {
                    if ($row['item_type'] == 'quiz') {
                        // Get score and total time from last attempt of a exercise en lp.
                        $sql = "SELECT score FROM $TBL_LP_ITEM_VIEW
                                WHERE c_id = $course_id AND lp_item_id = '" . (int) $my_id . "' AND lp_view_id = '" . (int) $my_lp_view_id . "'
                                ORDER BY view_count DESC limit 1";
                        $res_score = Database::query($sql);
                        $row_score = Database::fetch_array($res_score);

                        $sql = "SELECT SUM(total_time) as total_time FROM $TBL_LP_ITEM_VIEW
                                WHERE c_id = $course_id AND lp_item_id = '" . (int) $my_id . "' AND lp_view_id = '" . (int) $my_lp_view_id . "'";
                        $res_time = Database::query($sql);
                        $row_time = Database::fetch_array($res_time);

                        if (Database::num_rows($res_score) > 0 && Database::num_rows($res_time) > 0) {
                            $score = (float) $row_score['score'];
                            $subtotal_time = (int) $row_time['total_time'];
                        } else {
                            $score = 0;
                            $subtotal_time = 0;
                        }
                        //echo $subtotal_time ;
                        //$time = learnpathItem :: get_scorm_time('js', $subtotal_time);
                        // Selecting the max score from an attempt.
                        $sql = "SELECT SUM(t.ponderation) as maxscore
                                FROM (
                        			SELECT distinct question_id, marks, ponderation
                                    FROM $tbl_stats_attempts as at INNER JOIN  $tbl_quiz_questions as q
                        			ON (q.id = at.question_id AND q.c_id = $course_id
                                    )
                                WHERE exe_id ='$id_last_attempt' ) as t";

                        $result = Database::query($sql);
                        $row_max_score = Database :: fetch_array($result);
                        $maxscore = $row_max_score['maxscore'];
                    } else {
                        $maxscore = $row['mymaxscore'];
                    }
                }
            }

            $time_for_total = $subtotal_time;
            $time = learnpathItem :: get_scorm_time('js', $subtotal_time);
            if (empty($title)) {
                $title = rl_get_resource_name(api_get_course_id(), $lp_id, $row['myid']);
            }
            // Remove "NaN" if any (@todo: locate the source of these NaN)
            //$time = str_replace('NaN', '00'.$h.'00\'00"', $time);

            if ($row['item_type'] != 'dokeos_chapter') {
                if ($row['item_type'] == 'quiz') {
                    $correct_test_link = '';
                    $my_url_suffix = '';

                    if ($origin != 'tracking' && $origin != 'tracking_course') {
                        $my_url_suffix = '&course=' . api_get_course_id() . '&student_id=' . api_get_user_id() . '&lp_id=' . Security::remove_XSS($row['mylpid']);
                        $sql_last_attempt = 'SELECT * FROM ' . $tbl_stats_exercices . '
                                             WHERE  exe_exo_id="' . $row['path'] . '" AND
                                                    exe_user_id="' . api_get_user_id() . '" AND
                                                    orig_lp_id = "' . $lp_id . '" AND
                                                    orig_lp_item_id = "' . $row['myid'] . '" AND
                                                    exe_cours_id="' . $course_code . '" AND
                                                    status <> "incomplete" AND
                                                    session_id = ' . $session_id . '
                                            ORDER BY exe_date DESC ';
                    } else {
                        $my_url_suffix = '&course=' . Security::remove_XSS($_GET['course']) . '&student_id=' . $student_id . '&lp_id=' . Security::remove_XSS($row['mylpid']) . '&origin=' . Security::remove_XSS($_GET['origin'] . $from_link);
                        $sql_last_attempt = 'SELECT * FROM ' . $tbl_stats_exercices . '
                                             WHERE   exe_exo_id="' . $row['path'] . '" AND
                                                    exe_user_id="' . $student_id . '" AND
                                                    orig_lp_id = "' . $lp_id . '" AND
                                                    orig_lp_item_id = "' . $row['myid'] . '" AND
                                                    exe_cours_id="' . Database :: escape_string($_GET['course']) . '" AND
                                                    status <> "incomplete" AND
                                                    session_id = ' . $session_id . '
                                             ORDER BY exe_date DESC ';
                    }

                    $resultLastAttempt = Database::query($sql_last_attempt);
                    $num = Database :: num_rows($resultLastAttempt);
                    if ($num > 0) {
                        if (isset($_GET['extend_attempt']) && $_GET['extend_attempt'] == 1 && (isset($_GET['lp_id']) && $_GET['lp_id'] == $my_lp_id) && (isset($_GET['lp_item_id']) && $_GET['lp_item_id'] == $my_id)) {
                            $correct_test_link = '<a href="' . api_get_self() . '?action=stats' . $my_url_suffix . '&session_id=' . api_get_session_id() . '&lp_item_id=' . $my_id . '"><img src="../img/view_less_stats.gif" alt="fold_view" border="0" title="' . get_lang('HideAllAttempts') . '"></a>';
                        } else {
                            $correct_test_link = '<a href="' . api_get_self() . '?action=stats&extend_attempt=1' . $my_url_suffix . '&session_id=' . api_get_session_id() . '&lp_item_id=' . $my_id . '"><img src="../img/view_more_stats.gif" alt="extend_view" border="0" title="' . get_lang('ShowAllAttemptsByExercise') . '"></a>';
                        }
                    } else {
                        $correct_test_link = '-';
                    }
                } else {
                    $correct_test_link = '-';
                }

                $title = Security::remove_XSS($title);

                if ((isset($_GET['lp_id']) && $_GET['lp_id'] == $my_lp_id && false)) {

                    $output .= '<tr class =' . $oddclass . '>
                                    <td>' . $extend_link . '</td>
                                    <td colspan="4">' . $title . '</td>
                                    <td colspan="2">&nbsp;</td>
                                    <td colspan="2">&nbsp;</td>
                                    <td colspan="2">&nbsp;</td>
                                    <td>' . $correct_test_link . '</td>
                                </tr>';
                    $output .= '</tr>';
                } else {
                    if ((isset($_GET['lp_id']) && $_GET['lp_id'] == $my_lp_id ) && (isset($_GET['lp_item_id']) && $_GET['lp_item_id'] == $my_id)) {
                        $output .= "<tr class='$oddclass'>";
                    } else {
                        $output .= "<tr class='$oddclass'>";
                    }

                    if (($is_allowed_to_edit || api_is_drh()) && isset($_GET['lp_id']) && isset($course_code)) {
                        $lp = new learnpath($course_code, $_GET['lp_id'], api_get_user_id());
                        $lp->set_course_int_id($course_id);
                        $item_path_url = $lp->get_link('http', $my_id, false);
                        $item_path_url .= "&width=600";
                        $title = Display::url($title, $item_path_url, array('class' => 'ajax'));
                    }

                    if ($num > 0) {
                        $row_attempts = Database :: fetch_array($resultLastAttempt);
                        $my_score = $row_attempts['exe_result'];
                        $my_maxscore = $row_attempts['exe_weighting'];
                        $mktime_start_date = api_strtotime($row_attempts['start_date'], 'UTC');
                        $mktime_exe_date = api_strtotime($row_attempts['exe_date'], 'UTC');
                        if ($mktime_start_date && $mktime_exe_date) {
                            $mytime = ((int) $mktime_exe_date - (int) $mktime_start_date);
                            $time_attemp = learnpathItem :: get_scorm_time('js', $mytime);
                            $time_attemp = str_replace('NaN', '00' . $h . '00\'00"', $time_attemp);
                        } else {
                            $time_attemp = ' - ';
                        }
                        if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                            $view_score = Display::return_icon('invisible.gif', get_lang('ResultsHiddenByExerciseSetting'));
                        } else {
                            // Show only float when need it
                            if ($my_score == 0) {
                                $view_score = show_score(0, $my_maxscore, false);
                            } else {
                                if ($my_maxscore == 0) {
                                    $view_score = $my_score;
                                } else {
                                    $view_score = show_score($my_score, $my_maxscore, false);
                                }
                            }
                        }
                        $my_lesson_status = $row_attempts['status'];

                        if ($my_lesson_status == '') {
                            $my_lesson_status = learnpathitem::humanize_status('completed');
                        } elseif ($my_lesson_status == 'incomplete') {
                            $my_lesson_status = learnpathitem::humanize_status('incomplete');
                        }


                        $output .= '<td>'.$extend_link.'</td>
                                <td colspan="4">' . $title . '</td>
                                <td colspan="2">' . $my_lesson_status . '</td>
                                <td colspan="2">' . $view_score . '</td>
                                <td colspan="2">' . $time_attemp . '</td>
                                <td>' . $correct_test_link . '</td>';
                        $output .= '</tr>';
                    } else {
                        $output .= '<td>'.$extend_link.'</td>
                                <td colspan="4">' . $title . '</td>
                                <td colspan="2">' . learnpathitem::humanize_status($lesson_status) .'</td>
                                <td colspan="2">';
                        if ($row['item_type'] == 'quiz') {
                            if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                $output .= Display::return_icon('invisible.gif', get_lang('ResultsHiddenByExerciseSetting'));
                            } else {
                                $output .= show_score($score, $maxscore, false);
                            }
                        } else {
                            $output .= ($score == 0 ? '/' : ($maxscore == 0 ? $score : $score . '/' . $maxscore));
                        }
                        $output .= '</td>
                                <td colspan="2">'.$time.'</td>
                                 <td>'.$correct_test_link.'</td>';
                        $output .= '</tr>';
                    }
                }

                if (!empty($export_csv)) {
                    $temp = array();
                    $temp[] = api_html_entity_decode($title, ENT_QUOTES);
                    $temp[] = api_html_entity_decode($my_lesson_status, ENT_QUOTES);

                    if ($row['item_type'] == 'quiz') {
                        if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                            $temp[] = '/';
                        } else {
                            $temp[] = ($score == 0 ? '0/' . $maxscore : ($maxscore == 0 ? $score : $score . '/' . float_format($maxscore, 1)));
                        }
                    } else {
                        $temp[] = ($score == 0 ? '/' : ($maxscore == 0 ? $score : $score . '/' . float_format($maxscore, 1)));
                    }
                    $temp[] = $time;
                    $csv_content[] = $temp;
                }
            }

            $counter++;
            //var_dump($extend_this_attempt, $extend_all);
            if ($extend_this_attempt OR $extend_all) {
                $list1 = learnpath :: get_iv_interactions_array($row['iv_id']);

                foreach ($list1 as $id => $interaction) {
                    if (($counter % 2) == 0) {
                        $oddclass = 'row_odd';
                    } else {
                        $oddclass = 'row_even';
                    }
                    $output .= '<tr class="'.$oddclass.'">
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>'.$interaction['order_id'].'</td>
                                    <td>'.$interaction['id'].'</td>
                                    <td colspan="2">' . $interaction['type'].'</td>
                                    <td>'.urldecode($interaction['student_response']).'</td>
                                    <td>'.$interaction['result'].'</td>
                                    <td>'.$interaction['latency'].'</td>
                                    <td>'.$interaction['time'].'</td>
                                    <td></td>
                               </tr>';
                    $counter++;
                }
                $list2 = learnpath :: get_iv_objectives_array($row['iv_id']);
                foreach ($list2 as $id => $interaction) {
                    if (($counter % 2) == 0) {
                        $oddclass = 'row_odd';
                    } else {
                        $oddclass = 'row_even';
                    }
                    $output .= '<tr class="'.$oddclass.'">
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>' . $interaction['order_id'] . '</td>
                                    <td colspan="2">'.$interaction['objective_id'] . '</td>
                                    <td colspan="2">' . $interaction['status'] . '</td>
                                    <td>' . $interaction['score_raw'].'</td>
                                    <td>' . $interaction['score_max'] .'</td>
                                    <td>' . $interaction['score_min'].'</td>
                                    <td></td>
                               </tr>';
                    $counter++;
                }
            }

            // Attempts listing by exercise.
            if ((isset($_GET['lp_id']) && $_GET['lp_id'] == $my_lp_id) && (isset($_GET['lp_item_id']) && $_GET['lp_item_id'] == $my_id) && isset($_GET['extend_attempt'])) {

                $res_attempts = Database::query($sql_attempts);
                $num_attempts = Database :: num_rows($res_attempts);
                if ($row['item_type'] === 'quiz') {
                    if ($num_attempts > 0) {
                        $n = 1;
                        while ($row_attempts = Database :: fetch_array($res_attempts)) {
                            $my_score = $row_attempts['exe_result'];
                            $my_maxscore = $row_attempts['exe_weighting'];
                            $my_exe_id = $row_attempts['exe_id'];
                            $my_orig_lp = $row_attempts['orig_lp_id'];
                            $my_orig_lp_item = $row_attempts['orig_lp_item_id'];
                            $my_exo_exe_id = $row_attempts['exe_exo_id'];
                            $mktime_start_date = api_strtotime($row_attempts['start_date'], 'UTC');
                            $mktime_exe_date = api_strtotime($row_attempts['exe_date'], 'UTC');
                            if ($mktime_start_date && $mktime_exe_date) {
                                $mytime = ((int) $mktime_exe_date - (int) $mktime_start_date);
                                $time_attemp = learnpathItem :: get_scorm_time('js', $mytime);
                                $time_attemp = str_replace('NaN', '00' . $h . '00\'00"', $time_attemp);
                            } else {
                                $time_attemp = ' - ';
                            }
                            if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                $view_score = Display::return_icon('invisible.gif', get_lang('ResultsHiddenByExerciseSetting'));
                            } else {
                                // Show only float when need it
                                if ($my_score == 0) {
                                    $view_score = show_score(0, $my_maxscore, false);
                                } else {
                                    if ($my_maxscore == 0) {
                                        $view_score = $my_score;
                                    } else {
                                        $view_score = show_score($my_score, $my_maxscore, false);
                                    }
                                }
                            }
                            $my_lesson_status = $row_attempts['status'];

                            if ($my_lesson_status == '') {
                                $my_lesson_status = learnpathitem::humanize_status('completed');
                            } elseif ($my_lesson_status == 'incomplete') {
                                $my_lesson_status = learnpathitem::humanize_status('incomplete');
                            }

                            $output .= '<tr class="' . $oddclass . '" >
                                            <td></td>
                                            <td>' . $extend_attempt_link . '</td>
                                            <td colspan="3">' . get_lang('Attempt').' '. $n.'</td>
                                            <td colspan="2">' . $my_lesson_status . '</td>
                                            <td colspan="2">'.$view_score . '</td>
                                            <td colspan="2">'.$time_attemp . '</td>';
                            if ($origin != 'tracking') {
                                if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                    $output .= '<td><img src="' . api_get_path(WEB_IMG_PATH) . 'quiz_na.gif" alt="' . get_lang('ShowAttempt') . '" title="' . get_lang('ShowAttempt') . '"></td>';
                                } else {
                                    $output .= '<td><a href="../exercice/exercise_show.php?origin=' . $origin . '&id=' . $my_exe_id . '&cidReq=' . $course_code . $from_link . '" target="_parent">
                                                    <img src="' . api_get_path(WEB_IMG_PATH) . 'quiz.gif" alt="' . get_lang('ShowAttempt') . '" title="' . get_lang('ShowAttempt') . '"></a></td>';
                                }
                            } else {
                                if (!$is_allowed_to_edit && $result_disabled_ext_all) {
                                    $output .= '<td><img src="' . api_get_path(WEB_IMG_PATH) . 'quiz_na.gif" alt="' . get_lang('ShowAndQualifyAttempt') . '" title="' . get_lang('ShowAndQualifyAttempt') . '"></td>';
                                } else {
                                    $output .= '<td><a href="../exercice/exercise_show.php?cidReq=' . $course_code . '&origin=correct_exercise_in_lp&id=' . $my_exe_id . '" target="_parent">
                                                     <img src="' . api_get_path(WEB_IMG_PATH) . 'quiz.gif" alt="' . get_lang('ShowAndQualifyAttempt') . '" title="' . get_lang('ShowAndQualifyAttempt') . '"></a></td>';
                                }
                            }
                            $output .= '</tr>';
                            $n++;
                        }
                    }
                    $output .= '<tr><td colspan="12">&nbsp;</td></tr>';
                }
            }
        }

        $total_time += $time_for_total;
        // QUIZZ IN LP
        $a_my_id = array();
        if (!empty($my_lp_id)) {
            $a_my_id[] = $my_lp_id;
        }
    }
}
//NOT Extend all "left green cross"
if (!empty($a_my_id)) {
    $my_studen_id = 0;
    $my_course_id = '';
    if ($origin == 'tracking') {
        $my_studen_id = $student_id;
        $my_course_id = Database::escape_string($_GET['course']);
    } else {
        $my_studen_id = intval(api_get_user_id());
        $my_course_id = Database::escape_string(api_get_course_id());
    }
    //var_dump($my_studen_id, $my_course_id,$a_my_id);
    if (isset($_GET['extend_attempt'])) {
        //"Right green cross" extended
        $total_score = Tracking::get_avg_student_score($my_studen_id, $my_course_id, $a_my_id, api_get_session_id(), false, false);
    } else {
        //"Left green cross" extended
        $total_score = Tracking::get_avg_student_score($my_studen_id, $my_course_id, $a_my_id, api_get_session_id(), false, true);
    }
} else {
    // Extend all "left green cross"
    if ($origin == 'tracking') {
        $my_course_id = Database::escape_string($_GET['course']);
        //    var_dump($student_id, $my_course_id );
        if (!empty($student_id) && !empty($my_course_id)) {
            $total_score = Tracking::get_avg_student_score($student_id, $my_course_id, array(intval($_GET['lp_id'])), api_get_session_id(), false, false);
        } else {
            $total_score = 0;
        }
    } else {
        $total_score = Tracking::get_avg_student_score(api_get_user_id(), api_get_course_id(), array(intval($_GET['lp_id'])), api_get_session_id(), false, false);
    }
}

$total_time = learnpathItem :: get_scorm_time('js', $total_time);
//$total_time = str_replace('NaN', '00:00:00' ,$total_time);
$total_time = str_replace('NaN', '00' . $h . '00\'00"', $total_time);
//$lp_type = learnpath :: get_type_static($lp_id);
//$total_percent = 0;

if (!$is_allowed_to_edit && $result_disabled_ext_all) {
    $final_score = Display::return_icon('invisible.gif', get_lang('ResultsHiddenByExerciseSetting'));
} else {
    if (is_numeric($total_score))
        $final_score = $total_score . '%';
    else
        $final_score = $total_score;
}

if (($counter % 2) == 0) {
    $oddclass = 'row_odd';
} else {
    $oddclass = 'row_even';
}

//if (empty($extend_all)) {
$output .= '<tr class="'.$oddclass.'">
                <td></td>
                <td colspan="4">
                    <i>' . get_lang('AccomplishedStepsTotal') .'</i>
                </td>
                <td colspan="2"></td>
                <td colspan="2">
                    ' . $final_score.'
                </td>
                <td colspan="2">' . $total_time . '</div><td></td>
           </tr>';
//}

$output .= "</table>";

if (!empty($export_csv)) {
    $temp = array(
        '',
        '',
        '',
        ''
    );
    $csv_content[] = $temp;
    $temp = array(
        get_lang('AccomplishedStepsTotal'),
        '',
        $final_score,
        $total_time
    );
    $csv_content[] = $temp;
    ob_end_clean();
    Export :: export_table_csv($csv_content, 'reporting_learning_path_details');
    exit;
}

if ($origin != 'tracking') {
    $output .= "</body></html>";
}

if (empty($export_csv)) {
    echo $output;
}
