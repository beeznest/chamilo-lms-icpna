<?php
$language_file = array('registration', 'messages', 'userInfo');
require_once '../inc/global.inc.php';
$isAdult = $_configuration['kids'] !== 1;
if ($isAdult) {
    define('NUM_COURSES', 5);
    define('NUM_PHASES', 5);
    define('TOTAL_COURSES', NUM_COURSES * NUM_PHASES);

    $basic_adulto = Display::return_icon('donde_basico_adulto.png',get_lang('Basic'));
    $basic_alto_adulto = Display::return_icon('donde_basico_alto_adulto.png',get_lang('High - Basic'));
    $Intermediate = Display::return_icon('donde_intermedio-adulto.png',get_lang('Intermediate'));
    $HighIntermediate = Display::return_icon('donde_intermedio-alto-adultos.png',get_lang('High - Intermediate'));
    $Advanced = Display::return_icon('donde_avanzado-adulto.png',get_lang('Advanced'));
    $phase_title = array(
        1 => $basic_adulto,
        2 => $basic_alto_adulto,
        3 => $Intermediate,
        4 => $HighIntermediate,
        5 => $Advanced,
    );
} else {
    define('NUM_COURSES', 6);
    define('NUM_PHASES', 4);
    define('TOTAL_COURSES', NUM_COURSES * NUM_PHASES);
    $Elementary = Display::return_icon('donde_elemental-kids.png',get_lang('Elementary'));
    $HighElementary = Display::return_icon('donde_elemental-alto-kids.png',get_lang('High - Elementary'));
    $BasicKids = Display::return_icon('donde_basico-kids.png',get_lang('High - Elementary'));
    $HighBasicKids = Display::return_icon('donde_basico-alto-kids.png',get_lang('High - Elementary'));
    $phase_title = array(
        1 => $Elementary,
        2 => $HighElementary,
        3 => $BasicKids,
        4 => $HighBasicKids,
    );
}
function createDiv($course_id) {
    $text = '';
    global $isAdult, $phase_title;
    $index = $course_id % NUM_COURSES;
    if ($index == 1 && $course_id <= TOTAL_COURSES) {
        $phase_class = array(
            1 => 'title-nivel-01',
            2 => 'title-nivel-02',
            3 => 'title-nivel-03',
            4 => 'title-nivel-04',
            5 => 'title-nivel-05',
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
                      </div></div>';
                $text .= '</div>';
            }
            $text .= '<div class="span9">';
        } else {
            $text .= '</div>
                          <div class="number-hours">N° de Horas: 125 </div>
                      </div></div>';
        }
        $text .= '<div class="span4"><div class="bloque-item">
                          <div class="' . $phase_class[$phase_id] . '">' . $phase_title[$phase_id] . '</div>
                          <div class="location-course">';
    } elseif ($course_id == (TOTAL_COURSES + 1)) {
        $text .= '</div>
                      <div class="number-hours">N° de Horas: 125 </div>
                  </div></div>
            </div>
        </div>
        ';
    }
    return $text;
}
$user_id = api_get_user_id();
$social_left_content = $social_left_content = SocialManager::show_social_menu('whereiam');
if (!empty($user_id)) {
    $social_right_content =  '<div class="row"><div class="span9 page-show"><h3 class="titulo">¿Dónde Estoy?</h3>';
    $social_right_content .= '<div class="row">';
    $session_list = SessionManager::get_course_session_list_by_user($user_id);
    $sequence_int = 0;
    $max_int = 0;

    foreach ($session_list as $session) {
        $course_code = $session['course_code'];
        $sequence_int = intval(preg_replace('/\D/','',$course_code));
        if ($sequence_int < 1) {
            continue;
        } elseif ($sequence_int < 10) {
            $course_sequence = '0' . $sequence_int;
        } elseif ($sequence_int > 99) {
            $course_sequence = $sequence_int % 100;
        } else {
            $course_sequence = $sequence_int;
        }
        $course_sequences[$sequence_int] = $course_sequence;
        if ($max_int < $sequence_int) {
            $max_int = $sequence_int;
        }
    }

    if (!empty($course_sequences)) {
        $course_sequences = array_unique($course_sequences);
        ksort($course_sequences);
    }
    for ($i = 1 ; $i <= (TOTAL_COURSES + 1) ; $i++) {
        $social_right_content .= createDiv($i);
        if ($i > TOTAL_COURSES) {
            break;
        }
        // show list of courses
        if (!empty($course_sequences[$i])) {
            // if user have completed a course or its in progress
            if ($i == $max_int) {
                $social_right_content .= '<div class="item-list"><div class="module-process">'.$course_sequences[$i].'</div></div>';
            } else {
                $social_right_content .= '<div class="item-list"><div class="module-completed">'.$course_sequences[$i].'</div></div>';
            }
        } elseif ($i< 10) {
            $social_right_content .= '<div class="item-list"><div class="module-closed">0' . $i . '</div></div>';
        } else {
            $social_right_content .= '<div class="item-list"><div class="module-closed">' . $i . '</div></div>';
        }
    }
}
$tpl = new Template(null);
$tpl->assign('social_left_content', $social_left_content);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('layout/social_layout.tpl');
$tpl->display($social_layout);