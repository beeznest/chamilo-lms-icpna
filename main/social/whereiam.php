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
function createDiv($course_id) {
    $text = '';
    global $isAdult;
    $index = $course_id % NUM_COURSES;
    if ($index == 1 && $course_id <= TOTAL_COURSES) {
        $phase_class = array(
            1 => 'title-nivel-01',
            2 => 'title-nivel-02',
            3 => 'title-nivel-03',
            4 => 'title-nivel-04',
            5 => 'title-nivel-04',
        );
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
        $phase_id = ceil($course_id / NUM_COURSES);
        if ($phase_id % 2 == 1) {
            if ($phase_id != 1) {
                $text .= '</div>
                          <div class="number-hours">N° de Horas: 125 </div>
                      </div>';
                $text .= '</div>';
            }
            $text .= '<div class="span8">';
        } else {
            $text .= '</div>
                          <div class="number-hours">N° de Horas: 125 </div>
                      </div>';
        }
        $text .= '    <div class="span3">
                          <div class="' . $phase_class[$phase_id] . '">' . $phase_title[$phase_id] . '</div>
                          <div class="location-course">';
    } elseif ($course_id == (TOTAL_COURSES + 1)) {
        $text .= '</div>
                      <div class="number-hours">N° de Horas: 125 </div>
                  </div>
            </div>
        </div>
        ';
    }
    return $text;
}
$user_id = api_get_user_id();
$social_left_content = $social_left_content = SocialManager::show_social_menu('whereiam');
if (!empty($user_id)) {
    $social_right_content =
        '<div class="well_border">
                <div class="row">
                    <h3>¿Dónde Estoy?</h3>';
    $session_list = SessionManager::get_course_session_list_by_user($user_id);
    $sequence_int = 0;
    foreach ($session_list as $session) {
        $course_code = $session['course_code'];
        $sequence_int = intval(preg_replace('/\D/','',$course_code));
        if ($sequence_int < 1) {
            continue;
        } elseif ($sequence_int < 10) {
            $course_sequence = '0' . $sequence_int;
        } elseif ($length > 99) {
            $course_sequence = $sequence_int % 100;
        }
        $course_sequences[$sequence_int] = $course_sequence;
    }
    sort($course_sequences = array_unique($course_sequences));
    for ($i = 1 ; $i <= (TOTAL_COURSES + 1) ; $i++) {
        $social_right_content .= createDiv($i);
        if ($i > TOTAL_COURSES) {
            break;
        }
        // show list of courses
        if ($course_sequences[$i]) {
            // if user have completed a course or its in progress
            if ($i == $sequence_int) {
                $social_right_content .= '<span class="actual"><a href="#">'.$course_sequences[$i].'</a></span>';
            } else {
                $social_right_content .= '<span class="complet"><a href="#">'.$course_sequences[$i].'</a></span>';
            }
        } elseif ($i< 10) {
            $social_right_content .= '0' . $i;
        } else {
            $social_right_content .= $i;
        }

        if ($i % NUM_COURSES != 0) {
            $social_right_content .= ' - ';
        }
    }
}
$tpl = new Template(null);
$tpl->assign('social_left_content', $social_left_content);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('layout/social_layout.tpl');
$tpl->display($social_layout);