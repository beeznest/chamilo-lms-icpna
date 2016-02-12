<?php
/* For license terms, see /license.txt */
class WamiRecorder
{
    public $courseInfo;
    public $userId;
    public $exeId;
    public $exerciseId;
    public $questionId;
    public $courseId;
    public $sessionId;
    public $storePath;
    public $fileName;
    public $filePath;
    public $canEdit;

    public function __construct($courseId = 0, $sessionId = 0, $userId = 0, $exerciseId = 0, $questionId = 0, $exeId = 0)
    {
        if (!empty($courseId)) {
            $this->courseId = intval($courseId);
        } else {
            $this->courseId = api_get_course_int_id();
        }

        $this->courseInfo = api_get_course_info_by_id($this->courseId);

        if (!empty($sessionId)) {
            $this->sessionId = intval($sessionId);
        } else {
            $this->sessionId = api_get_session_id();
        }

        if (!empty($userId)) {
            $this->userId = intval($userId);
        } else {
            $this->userId = api_get_user_id();
        }

        $this->exerciseId = 0;

        if (!empty($exerciseId)) {
            $this->exerciseId = intval($exerciseId);
        }

        $this->questionId = 0;

        if (!empty($questionId)) {
            $this->questionId = intval($questionId);
        }

        $this->canEdit = false;

        if (api_is_allowed_to_edit()) {
            $this->canEdit = true;
        } else {
            if ($this->userId == api_get_user_id()) {
                $this->canEdit = true;
            }
        }

        $this->exeId = intval($exeId);

        $this->storePath = $this->generateDirectory();
        $this->fileName = $this->generateFileName();
        $this->filePath = $this->storePath . $this->fileName;
    }

    private function generateFileName()
    {
        return implode(
            '-',
            array(
                $this->courseId,
                $this->sessionId,
                $this->userId,
                $this->exerciseId,
                $this->questionId,
                $this->exeId
            )
        );
    }

    private function generateDirectory()
    {
        $this->storePath = api_get_path(SYS_COURSE_PATH) . $this->courseInfo['path'] . '/exercises/';

        if (!is_dir($this->storePath)) {
            mkdir($this->storePath);
        }

        if (!is_dir($this->storePath . $this->sessionId)) {
            mkdir($this->storePath . $this->sessionId);
        }

        if (!empty($this->exerciseId) && !is_dir($this->storePath . $this->sessionId . '/' . $this->exerciseId)) {
            mkdir($this->storePath . $this->sessionId . '/' . $this->exerciseId);
        }

        if (!empty($this->questionId) && !is_dir($this->storePath . $this->sessionId . '/' . $this->exerciseId . '/' . $this->questionId)) {
            mkdir($this->storePath . $this->sessionId . '/' . $this->exerciseId . '/' . $this->questionId);
        }

        if (!empty($this->userId) && !is_dir($this->storePath . $this->sessionId . '/' . $this->exerciseId . '/' . $this->questionId . '/' . $this->userId)) {
            mkdir($this->storePath . $this->sessionId . '/' . $this->exerciseId . '/' . $this->questionId . '/' . $this->userId);
        }

        return $this->storePath .= implode(
            '/',
            array(
                $this->sessionId,
                $this->exerciseId,
                $this->questionId,
                $this->userId
            )
        ) . '/';
    }

    private function generateRelativeDirectory()
    {
        return implode(
            '/',
            array(
                $this->sessionId,
                $this->exerciseId,
                $this->questionId,
                $this->userId
            )
        );
    }

    private function getParams()
    {
        return http_build_query(array(
            'course_id' => $this->courseId,
            'session_id' => $this->sessionId,
            'exercise_id' => $this->exerciseId,
            'exe_id' => $this->exeId,
            'question_id' => $this->questionId,
            'user_id' => $this->userId
        ));
    }

    public function showJS()
    {
        $wamidir = '/../exercises/' . $this->generateRelativeDirectory() . '/';
        $wamiurlplay = api_get_path(WEB_COURSE_PATH) . api_get_course_path($this->courseInfo['code']) . '/exercises/' . $this->generateRelativeDirectory();
        $wamiuserid = $this->userId;

        echo '
            <!-- swfobject is a commonly used library to embed Flash content https://ajax.googleapis.com/ajax/libs/swfobject/2.2/ -->
            <script type="text/javascript" src="' . api_get_path(WEB_LIBRARY_PATH) . 'swfobject/swfobject.js"></script>

            <!-- Setup the recorder interface -->
            <script type="text/javascript" src="' . api_get_path(WEB_LIBRARY_PATH) . 'wami-recorder/recorder.js"></script>

            <!-- GUI code... take it or leave it -->
            <script type="text/javascript" src="' . api_get_path(WEB_LIBRARY_PATH) . 'wami-recorder/gui.js"></script>

            <script type="text/javascript">

                function newNameRecord() {
                    location.reload(true)
                }

                function setupRecorder() {
                    document.getElementById(\'audio_button\').style.display = \'none\';

                    Wami.setup({
                        id : \'wami\',
                        onReady : setupGUI
                    });
                }

                function setupGUI() {
                    var waminame = \'' . $this->fileName . '.wav\';
                    var waminame_play=waminame;

                    var gui = new Wami.GUI({
                        id : \'wami\',
                        singleButton : true,
                        recordUrl: \'' . api_get_path(WEB_LIBRARY_PATH) . 'wami-recorder/record_document.php?waminame=\' + waminame + \'&wamidir=' . $wamidir . '&wamiuserid=' . $wamiuserid . '\',
                        playUrl: \'' . $wamiurlplay . '\' + waminame_play,
                        buttonUrl: \'' . api_get_path(WEB_LIBRARY_PATH) . 'wami-recorder/buttons.png\',
                        swfUrl: \'' . api_get_path(WEB_LIBRARY_PATH) . 'wami-recorder/Wami.swsdf\'
                    });

                    gui.setPlayEnabled(false);
                }

            </script>
        ';
    }

    public function showForm()
    {
        echo '
            <div id="wami" style="margin-top:10px;"></div>
            <div align="center" style="margin-top:150px;">
                <form name="form_wami_recorder">
                    <button type="button" value="" onclick="setupRecorder()" id="audio_button" />' . get_lang('Activate') . '</button>
                    <button type="button" value="" onclick="newNameRecord()" id="new_name" />' . get_lang('Reload') . '</button>
                </form>
            </div>
        ';
    }

    public function getButton()
    {
        $params_strings = $this->getParams();

        $html = '<br />'
            . Display::url(
                get_lang('RecordAnswer'), api_get_path(WEB_AJAX_PATH) . 'wamirecorder.ajax.php?a=show_form&'. $params_strings . '&TB_iframe=true&height=400&width=500',
                array('class'=>'btn thickbox')
            )
            . '<br /><br />' . Display::return_message(get_lang('UseTheMessageBelowToAddSomeComments'));
        return $html;
    }

    public function showAudioFile()
    {
        $filePath = $this->loadFileIfExists();

        if (empty($filePath)) {
            return null;
        }

        $url = $this->getPublicURL();

        $params = array(
            'url' => $url,
            'extension' => 'wav',
            'count' => 1
        );

        $jquery = DocumentManager::generate_jplayer_jquery($params);

        $js_path = api_get_path(WEB_LIBRARY_PATH) . 'javascript/';
        $actions = Display::url(Display::return_icon('save.png', get_lang('Download'), array(), ICON_SIZE_SMALL), $url, array('target' => '_blank'));

        $html = '<link rel="stylesheet" href="' . $js_path . 'jquery-jplayer/skins/blue/jplayer.blue.monday.css" type="text/css">';
        //$html .= '<link rel="stylesheet" href="' . $js_path . 'jquery-jplayer/skins/chamilo/jplayer.blue.monday.css" type="text/css">';
        $html .= '<script type="text/javascript" src="' . $js_path . 'jquery-jplayer/jquery.jplayer.min.js"></script>';

        $html .= '<div class="nanogong_player"></div>';
        $html .= '<br /><div class="action_player">' . $actions . '</div><br /><br /><br />';
        $html .= '<script>
            $(document).ready( function() {        
                //Experimental changes to preview mp3, ogg files        
                 ' . $jquery . '                 
            });
            </script>';
        $html .= DocumentManager::generate_media_preview(1, 'advanced');

        return $html;
    }

    public function loadFileIfExists($loadFromDatabase = false)
    {
        $fileName = $this->fileName . '.wav';

		//temp_exe
		if ($loadFromDatabase) {
			//Load the real filename just if exists
			if (isset($this->exeId) && isset($this->userId) && isset($this->questionId) && isset($this->sessionId) && isset($this->courseId)) {
				$attempt_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

                $sql = "SELECT filename FROM $attempt_table
                        WHERE exe_id = " . $this->exeId . " AND
                                user_id = " . $this->userId . " AND
                                question_id = " . $this->questionId . " AND
                                session_id = " . $this->sessionId . " AND
                                course_code = '" . $this->courseInfo['code'] . "' LIMIT 1";
                $result = Database::query($sql);
				$result = Database::fetch_row($result,'ASSOC');

				if (isset($result) && isset($result[0]) && !empty($result[0])) {
					$fileName = $result[0];
				}
			}
		}

		if (is_file($this->storePath . $fileName)) {
			return $this->storePath . $fileName;
		}

		return null;
    }

    public function getPublicURL()
    {
        $url = api_get_path(WEB_COURSE_PATH) . $this->courseInfo['path'] . '/exercises/';
        $url .= $this->generateRelativeDirectory() . '/';
        $url .= $this->fileName . '.wav';

        return $url;
    }
}
