<?php
require_once '../inc/global.inc.php';
$social_left_content = $social_left_content = SocialManager::show_social_menu('whereiam');
$social_right_content =
    '<div class="well_border">
            <div class="row">
                <h3>¿Dónde Estoy?</h3>';
$user_id = api_get_user_id();
$sessions = SessionManager::get_sessions_by_user($user_id);
foreach ($sessions as $session) {
    $course_code = $session['courses'][0]['code'];
    var_dump($course_code);
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
var_dump($course_sequences);
for ($i = 1 ; $i <= 26; $i++) {
    //Open and close divs
    switch ($i) {
        case 1:
            $social_right_content .=
                '<div class="span8">
                    <div class="span3">
                        <div class="title-nivel-01">Elementary</div>
                        <div class="location-course">';
            break;
        case 6:
            $social_right_content .=
                '    <div class="number-hours">N° de Horas: - </div>
                </div>
                </div>
                <div class="span3">
                    <div class="title-nivel-02">High - Elementary</div>
                    <div class="location-course">';
            break;
        case 11:
            $social_right_content .=
                '    <div class="number-hours">N° de Horas: - </div>
                </div>
                </div>
                <div class="span3">
                    <div class="title-nivel-03">Basic</div>
                    <div class="location-course">';
            break;
        case 16:
            $social_right_content .=
                '    <div class="number-hours">N° de Horas: - </div>
                </div>
                </div>
                <div class="span3">
                    <div class="title-nivel-04">High - Basic</div>
                    <div class="location-course">';
            break;
        case 21:
            $social_right_content .=
                '    <div class="number-hours">N° de Horas: - </div>
                </div>
                </div>
                <div class="span3">
                    <div class="title-nivel-04">Advanced</div>
                    <div class="location-course">';
            break;
        case 26:
            $social_right_content .=
                '    <div class="number-hours">N° de Horas: - </div>
                    </div>
                </div>
            </div>
        </div>';
            break 2;
        default:
            break;
    }

    if ($course_sequences[$i]) {
        //@TODO check which session is in progress
        $social_right_content .= '<span class="complet"><a href="#">'.$course_sequences[$i].'</a></span> - ';
    } elseif ($sequence_int < 10) {
        $social_right_content .= '0' . $i . ' - ';
    } elseif ($length > 99) {
        $social_right_content .= ($sequence_int % 100) . ' - ';
    }
}
$social_right_content .=
    '<style>
        /*+++++++++++++++++++++++++++++++++++++++
CSS DONDE ESTOY
+++++++++++++++++++++++++++++++++++++++++*/
.title-nivel-01{
  font-size: 20px;
  color: #000;
  border-top: 2px solid #deebf7;
  padding-top: 5px;
  padding-bottom: 5px;
  border-bottom: 6px solid #deebf7;
  text-align: center;
}
.title-nivel-02{
  font-size: 20px;
  color: #000;
  border-top: 2px solid #bdd7ee;
  padding-top: 5px;
  padding-bottom: 5px;
  border-bottom: 6px solid #bdd7ee;
  text-align: center;
}
.title-nivel-03{
  font-size: 20px;
  color: #000;
  border-top: 2px solid #9dc3e6;
  padding-top: 5px;
  padding-bottom: 5px;
  border-bottom: 6px solid #9dc3e6;
  text-align: center;
}
.title-nivel-04{
  font-size: 20px;
  color: #000;
  border-top: 2px solid #2e75b6;
  padding-top: 5px;
  padding-bottom: 5px;
  border-bottom: 6px solid #2e75b6;
  text-align: center;
}
.location-course{
  padding-bottom: 15px;
  padding-top: 15px;
  text-align: center;
  font-size: 20px;
  color: #ccc;
}
.location-course .complet a{
  color: #ffffff;
  background-color: #0189bc;
  padding-left: 5px;
  padding-right: 5px;
}
.location-course .complet a:hover{
  color: #ffffff;
  background-color: #1da40f;
  padding-left: 5px;
  padding-right: 5px;
}
.location-course .actual a{
  color: #ffffff;
  background-color: #ff0000;
  padding-left: 5px;
  padding-right: 5px;
}
.location-course .actual a:hover{
  color: #ffffff;
  background-color: #1da40f;
  padding-left: 5px;
  padding-right: 5px;
}
.number-hours{
  font-size: 16px;
  text-align: center;
  padding-top: 10px;
  padding-bottom: 10px;
  margin-bottom: 15px;
  background-color: #d7dde3;
}
/*+++++++++++++++++++++++++++++++++++++++
FIN CSS DONDE ESTOY
+++++++++++++++++++++++++++++++++++++++++*/
    </style>';
$tpl = new Template(null);
$tpl->assign('social_left_content', $social_left_content);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('layout/social_layout.tpl');
$tpl->display($social_layout);