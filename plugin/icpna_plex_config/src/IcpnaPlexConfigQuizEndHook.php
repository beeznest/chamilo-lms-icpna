<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\CourseBundle\Entity\CQuizDestinationResult;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class IcpnaPlexConfigQuizEndHook.
 */
class IcpnaPlexConfigQuizEndHook extends HookObserver implements HookQuizEndObserverInterface
{
    private static $plugin;

    /**
     * IcpnaPlexConfigQuizEndHook constructor.
     */
    protected function __construct()
    {
        self::$plugin = IcpnaPlexConfigPlugin::create();

        parent::__construct(
            'plugin/icpna_plex_config/src/IcpnaPlexConfigQuizEndHook.php',
            'icpna_plex_config'
        );
    }

    /**
     * @inheritDoc
     */
    public function hookQuizEnd(HookQuizEndEventInterface $hookvent)
    {
        $hookData = $hookvent->getEventData();
        $exeId = !empty($hookData['exe_id']) ? (int) $hookData['exe_id'] : 0;
        $currentCourseCode = api_get_course_id();

        if (empty($exeId)) {
            return;
        }

        $courses = (string) self::$plugin->get(IcpnaPlexConfigPlugin::SETTING_COURSES);
        $courses = explode(',', $courses);

        if (!in_array($currentCourseCode, $courses)) {
            return;
        }

        $trackInfo = ExerciseLib::get_exercise_track_exercise_info($exeId);

        if (empty($trackInfo)) {
            return;
        }

        $userId = api_get_user_id();

        /** @var CQuizDestinationResult $destinationResult */
        $destinationResult = Database::getManager()
            ->getRepository('ChamiloCourseBundle:CQuizDestinationResult')
            ->findOneBy(['exe' => $exeId, 'user' => $userId]);

        if (!$destinationResult) {
            return;
        }

        if (0 === strpos($destinationResult->getAchievedLevel(), 'P - ')) {
            return;
        }

        $responseJson = [];

        do {
            try {
                $responseJson = $this->getWsResponse($destinationResult);

                $done = true;
            } catch (Exception $exception) {
                $done = false;
            }
        } while (false === $done);

        if (empty($responseJson)) {
            Display::addFlash(
                Display::return_message(self::$plugin->get_lang('WsResponseError'), 'warning', false)
            );
            return;
        }

        $this->saveEnrollment($responseJson, $destinationResult->getExe());

        Display::addFlash(
            Display::return_message('<strong>'.$responseJson['description'].'</strong>', 'success', false)
        );
    }

    /**
     * @param CQuizDestinationResult $destinationResult
     *
     * @return CQuizQuestionCategory
     */
    private function getQuestionCategoryByDestination(CQuizDestinationResult $destinationResult)
    {
        return Database::getManager()
            ->getRepository('ChamiloCourseBundle:CQuizQuestionCategory')
            ->findOneBy(
                [
                    'title' => $destinationResult->getAchievedLevel(),
                    'cId' => $destinationResult->getExe()->getCId(),
                ]
            );
    }

    /**
     * @param CQuizDestinationResult $destinationResult
     *
     * @return array
     *
     * @throws Exception
     */
    private function getWsResponse(CQuizDestinationResult $destinationResult)
    {
        $token = self::$plugin->get(IcpnaPlexConfigPlugin::SETTING_USERNAME);

        $user = $destinationResult->getUser();
        $questionCategory = $this->getQuestionCategoryByDestination(
            $destinationResult
        );

        $levelScore = TestCategory::getCatScoreForExeidForUserid(
            $questionCategory->getId(),
            $destinationResult->getExe()->getExeId(),
            $user->getId()
        );

        $efv = new ExtraFieldValue('session');
        $uididPrograma = $efv->get_values_by_handler_and_field_variable(
            $destinationResult->getExe()->getSessionId(),
            'uididprograma'
        );

        $httpClient = new Client();

        $requestData = [
            'codAl' => $user->getUsername(),
            'curso' => $destinationResult->getAchievedLevel(),
            'score' => $levelScore,
            'uiuidPrograma' => is_array($uididPrograma) ? $uididPrograma['value'] : '',
        ];

        try {
            $responseBody = $httpClient
                ->post(
                    self::$plugin->get(IcpnaPlexConfigPlugin::SETTING_WS_URL),
                    [
                        'headers' => [
                            'Authorization' => "$token",
                        ],
                        'json' => $requestData,
                    ]
                )
                ->getBody()
                ->getContents();

        } catch (RequestException $requestException) {
            $this->saveLog($requestData, [$requestException->getMessage()], false, $destinationResult->getExe());

            throw new Exception($requestException->getMessage());
        } catch (Exception $exception) {
            $this->saveLog($requestData, [$exception->getMessage()], false, $destinationResult->getExe());

            throw new Exception($exception->getMessage());
        }

        $json = json_decode($responseBody, true);

        if ('success' !== strtolower($json['response'])) {
            $successfulFailedResponse = [
                'response' => $json['response'],
                'description' => isset($json['ViewModelMessage']) ? $json['ViewModelMessage'] : 'Webservice internal error',
            ];

            $this->saveLog($requestData, $successfulFailedResponse, false, $destinationResult->getExe());

            throw new Exception($successfulFailedResponse['description']);
        }

        $this->saveLog($requestData, $json, true, $destinationResult->getExe());

        return $json;
    }

    /**
     * @param array           $request
     * @param array           $response
     * @param bool            $success
     * @param TrackEExercises $trackExercise
     */
    private function saveLog(array $request, array $response, $success, TrackEExercises $trackExercise)
    {
        Database::insert(
            'plugin_plex_log',
            [
                'exe_id' => $trackExercise->getExeId(),
                'request' => serialize($request),
                'response' => serialize($response),
                'success' => $success,
            ]
        );
    }

    /**
     * @param array $enrollment
     * @param TrackEExercises $trackExercise
     */
    private function saveEnrollment(array $enrollment, TrackEExercises $trackExercise)
    {
        Database::insert(
            'plugin_plex_enrollment',
            [
                'exe_id' => $trackExercise->getExeId(),
                'score' => $enrollment['score'],
                'exam_validity' => $enrollment['examvalidity'],
                'period_validity' => $enrollment['periodvalidity'],
                'level_reached' => $enrollment['levelreached'],
            ]
        );
    }
}
