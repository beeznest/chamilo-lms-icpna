<?php
/* See license terms in /license.txt */
/**
 * Class ExerciseShowFunctions
 * @package chamilo.library
 */
class ExerciseShowFunctions
{

	/**
	 * Shows the answer to a fill-in-the-blanks question, as HTML
	 * @param string    Answer text
	 * @param int       Exercise ID
	 * @param int       Question ID
	 * @return void
	 */
	static function display_fill_in_blanks_answer($answer,$id,$questionId)
    {
        global $feedback_type;
        $html = null;
        if (empty($id)) {
            $html .= '<tr><td>'. nl2br(Security::remove_XSS($answer,COURSEMANAGERLOWSECURITY)).'</td></tr>';
        } else {
            $html .= '
			<tr>
                <td>
                    '.nl2br(Security::remove_XSS($answer,COURSEMANAGERLOWSECURITY)).'
                </td>';

			if (!api_is_allowed_to_edit(null,true) && $feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
                $comm = get_comments($id,$questionId);
            }
            $html .= '<td>
				</td>
            </tr>';
        }
        return $html;
	}

	/**
	 * Shows the answer to a free-answer question, as HTML
	 * @param string    Answer text
	 * @param int       Exercise ID
	 * @param int       Question ID
	 * @return void
	 */
	static function display_free_answer($answer, $exe_id, $questionId, $questionScore = null)
    {
        global $feedback_type;
        $html = null;

        $comments = get_comments($exe_id, $questionId);

        if (!empty($answer)) {
            $html .= '<tr><td>';
            $html .= nl2br(Security::remove_XSS($answer, COURSEMANAGERLOWSECURITY));
            $html .= '</td></tr>';
        }

        if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
            if ($questionScore > 0 || !empty($comments)) {
            } else {
                $html .= '<tr>';
                $html .= Display::tag('td', Display::return_message(get_lang('notCorrectedYet')), array());
                $html .= '</tr>';
            }
        }
        return $html;
	}

	static function display_oral_expression_answer($answer, $id, $questionId, $nano = null)
    {
		global $feedback_type;
        $html = null;

		if (isset($nano)) {
            $html .= $nano->show_audio_file();
		}

		if (empty($id)) {
            $html .= '<tr>';
            $html .= Display::tag('td',nl2br(Security::remove_XSS($answer,COURSEMANAGERLOWSECURITY)), array('width'=>'55%'));
            $html .= '</tr>';
			if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
                $html .= '<tr>';
                $html .= Display::tag('td',get_lang('notCorrectedYet'), array('width'=>'45%'));
                $html .= '</tr>';
			} else {
                $html .= '<tr><td>&nbsp;</td></tr>';
			}
		} else {
            $html .= '<tr>';
            $html .= '<td>';
			if (!empty($answer)) {
                $html .= nl2br(Security::remove_XSS($answer,COURSEMANAGERLOWSECURITY));
			}
            $html .= '</td>';

			if (!api_is_allowed_to_edit(null,true) && $feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
                $html .= '<td>';
				$comm = get_comments($id,$questionId);
                $html .= '</td>';
			}
            $html .= '</tr>';
		}
        return $html;
	}

	/**
	 * Displays the answer to a hotspot question
	 *
	 * @param int $answerId
	 * @param string $answer
	 * @param string $studentChoice
	 * @param string $answerComment
	 */
	static function display_hotspot_answer($answerId, $answer, $studentChoice, $answerComment)
    {
		global $feedback_type;
        $html = null;
		$hotspot_colors = array(
            "", // $i starts from 1 on next loop (ugly fix)
            "#4271B5",
            "#FE8E16",
            "#45C7F0",
            "#BCD631",
            "#D63173",
            "#D7D7D7",
            "#90AFDD",
            "#AF8640",
            "#4F9242",
            "#F4EB24",
            "#ED2024",
            "#3B3B3B",
            "#F7BDE2"
        );

        $html .= '<table class="data_table">
		<tr>
			<td width="100px" valign="top" align="left">
				<div style="width:100%;">
				<div style="height:11px; width:11px; background-color:'.$hotspot_colors[$answerId].'; display:inline; float:left; margin-top:3px;"></div>
					<div style="float:left; padding-left:5px;">
					'.$answerId.'
					</div>
					<div>&nbsp;'.$answer .'</div>
				</div>
			</td>
			<td width="50px" style="padding-right:15px" valign="top" align="left">';
        $my_choice = ($studentChoice)?get_lang('Correct'):get_lang('Fault');

        $html .= $my_choice;

        $html .= '</td>';
        if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
            $html .= '<td valign="top" align="left" >';

            if ($studentChoice) {
                $html .= '<span style="font-weight: bold; color: #008000;">'.nl2br($answerComment).'</span>';
            } else {
                //$html .= '<span style="font-weight: bold; color: #FF0000;">'.nl2br(make_clickable($answerComment)).'</span>';
            }
            $html .= '</td>';
        } else {
            $html .= '<td>&nbsp;</td>';
        }
        $html .= '</tr>';

        return $html;
	}


	/**
	 * Display the answers to a multiple choice question
	 *
	 * @param integer Answer type
	 * @param integer Student choice
	 * @param string  Textual answer
	 * @param string  Comment on answer
	 * @param string  Correct answer comment
	 * @param integer Exercise ID
	 * @param integer Question ID
	 * @param boolean Whether to show the answer comment or not
	 * @return void
	 */
	static function display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect, $id, $questionId, $ans)
    {
		global $feedback_type;

		$html = '<tr><td width="5%">';
        $icon = (in_array($answerType, array(UNIQUE_ANSWER, UNIQUE_ANSWER_NO_OPTION))) ? 'radio':'checkbox';
        $icon .= $studentChoice?'_on':'_off';
        $icon .= '.gif';
        $html .= Display::return_icon($icon);
        $html .= '</td><td width="5%">';

        $icon = (in_array($answerType, array(UNIQUE_ANSWER, UNIQUE_ANSWER_NO_OPTION))) ? 'radio':'checkbox';
        $icon .= $answerCorrect?'_on':'_off';
        $icon .= '.gif';
        $html .= Display::return_icon($icon);
        $html .= '</td>
		<td width="40%">
			'.$answer.'
		</td>';

		if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
            $html .= '<td width="20%">';
            if ($studentChoice) {
				if ($answerCorrect) {
                    $color = 'green';
					//echo '<span style="font-weight: bold; color: #008000;">'.nl2br(make_clickable($answerComment)).'</span>';
				} else {
                    $color = 'black';
                    //echo '<span style="font-weight: bold; color: #FF0000;">'.nl2br(make_clickable($answerComment)).'</span>';
				}
                $html .= '<span style="font-weight: bold; color: '.$color.';">'.nl2br(($answerComment)).'</span>';
			} else {
				if ($answerCorrect) {
					//echo '<span style="font-weight: bold; color: #000;">'.nl2br(make_clickable($answerComment)).'</span>';
				} else {
                    //echo '<span style="font-weight: normal; color: #000;">'.nl2br(make_clickable($answerComment)).'</span>';
				}
			}
            $html .= '</td>';
            if ($ans==1) {
                $comm = get_comments($id,$questionId);
            }
        } else {
            $html .= '<td>&nbsp;</td>';
		}
        $html .= '</tr>';
        return $html;
	}

    /**
     * Display the answers to a multiple choice question
     *
     * @param integer Answer type
     * @param integer Student choice
     * @param string  Textual answer
     * @param string  Comment on answer
     * @param string  Correct answer comment
     * @param integer Exercise ID
     * @param integer Question ID
     * @param boolean Whether to show the answer comment or not
     * @return void
     */
    static function display_multiple_answer_true_false($answerType, $studentChoice, $answer, $answerComment, $answerCorrect, $id, $questionId, $ans)
    {
        global $feedback_type;
        $html = null;
        $html .= '        <tr>
        <td width="5%">';

        $question 	 = new MultipleAnswerTrueFalse();
        $course_id   = api_get_course_int_id();
        $new_options = Question::readQuestionOption($questionId, $course_id);

        //Your choice
        if (isset($new_options[$studentChoice])) {
            $html .= get_lang($new_options[$studentChoice]['name']);
        } else {
            $html .=  '-';
        }
        $html .= '
        </td>
        <td width="5%">';

		//Expected choice
        if (isset($new_options[$answerCorrect])) {
            $html .= get_lang($new_options[$answerCorrect]['name']);
        } else {
            $html .= '-';
        }
        $html .= '
        </td>
        <td width="40%">';
        $html .= $answer;
        $html .= '</td>';
        if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
            $html .= '<td width="20%">';
            $color = "black";
            if (isset($new_options[$studentChoice])) {
                if ($studentChoice == $answerCorrect) {
                    $color = "green";
                }
                $html .= '<span style="font-weight: bold; color: '.$color.';">'.nl2br($answerComment).'</span>';
            }
            $html .= '</td>';
            if ($ans==1) {
                $comm = get_comments($id, $questionId);
            }
        } else {
            $html .= ' <td>&nbsp;</td>';
        }
        $html .= '</tr>';
        return $html;
    }

     /**
     * Display the answers to a multiple choice question
     *
     * @param integer Answer type
     * @param integer Student choice
     * @param string  Textual answer
     * @param string  Comment on answer
     * @param string  Correct answer comment
     * @param integer Exercise ID
     * @param integer Question ID
     * @param boolean Whether to show the answer comment or not
     * @return void
     */
    static function display_multiple_answer_combination_true_false($answerType, $studentChoice, $answer, $answerComment, $answerCorrect, $id, $questionId, $ans)
    {
        global $feedback_type;
        $html = null;
        $html .= '<tr><td width="5%">';
		//Your choice
        $question = new MultipleAnswerCombinationTrueFalse();
        if (isset($question->options[$studentChoice])) {
            $html .= $question->options[$studentChoice];
        } else {
            $html .= $question->options[2];
        }

        $html .= '</td><td width="5%">';

		//Expected choice
        if (isset($question->options[$answerCorrect])) {
            $html .= $question->options[$answerCorrect];
        } else {
            $html .= $question->options[2];
        }
        $html .= '</td><td width="40%">';

        //my answer
        $html .= $answer;
        $html .= '</td>';
        if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
            $html .= '<td width="20%">';

            //@todo replace this harcoded value
            if ($studentChoice) {
                 $color = "black";
                if ($studentChoice == $answerCorrect) {
                    $color = "green";
                }
                $html .= '<span style="font-weight: bold; color: '.$color.';">'.nl2br($answerComment).'</span>';
            }
            if ($studentChoice == 2 || $studentChoice == '') {
            	//$html .= '<span style="font-weight: bold; color: #000;">'.nl2br(make_clickable($answerComment)).'</span>';
            } else {
				if ($studentChoice == $answerCorrect) {
	            	//$html .= '<span style="font-weight: bold; color: #008000;">'.nl2br(make_clickable($answerComment)).'</span>';
				} else {
                    //$html .= '<span style="font-weight: bold; color: #FF0000;">'.nl2br(make_clickable($answerComment)).'</span>';
				}
            }
            $html .= '</td>';
            if ($ans==1) {
                $comm = get_comments($id,$questionId);
            }
         } else {
            $html = '<td>&nbsp;</td>';
        }
        $html .= '</tr>';
        return $html;
    }
}
