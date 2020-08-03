<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuizDestinationResult;
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

        $destinationResult = Database::getManager()
            ->getRepository('ChamiloCourseBundle:CQuizDestinationResult')
            ->findOneBy(['exe' => $exeId, 'user' => $userId]);

        if (!$destinationResult) {
            return;
        }

        try {
            $responseJson = $this->getWsResponse($destinationResult);
        } catch (Exception $e) {
            Display::addFlash(
                Display::return_message($e->getMessage(), 'error')
            );

            $this->sendErrorEmail($e->getMessage(), $destinationResult);

            return;
        }

        Display::addFlash(
            Display::return_message($responseJson['description'], 'success')
        );
    }

    /**
     * @param CQuizDestinationResult $destinationResult
     *
     * @return \Chamilo\CourseBundle\Entity\CQuizQuestionCategory
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
     * @return void
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

        $httpClient = new Client();

        try {
            $responseBody = $httpClient
                ->post(
                    self::$plugin->get(IcpnaPlexConfigPlugin::SETTING_WS_URL),
                    [
                        'headers' => [
                            'Authorization' => "$token",
                        ],
                        'json' => [
                            'colAl' => $user->getUsername(),
                            'curso' => $destinationResult->getAchievedLevel(),
                            'score' => $levelScore,
                        ],
                    ]
                )
                ->getBody()
                ->getContents();

        } catch (RequestException $requestException) {
            throw new Exception($requestException->getMessage());
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

        $json = json_decode($responseBody, true);

        if ('success' !== strtolower($json['response'])) {
            throw new Exception($json['description']);
        }

        return $json;
    }

    /**
     * @param string                 $exceptionMessage
     * @param CQuizDestinationResult $destinationResult
     */
    private function sendErrorEmail($exceptionMessage, CQuizDestinationResult $destinationResult)
    {
        $student = $destinationResult->getUser();
        $exercise = $destinationResult->getExe();

        $mailMessage = ''.PHP_EOL;
        $mailMessage .= Display::div(
            $exceptionMessage,
            ['style' => 'border: 1px solid red; padding: 10px;']
        );
        $mailMessage .= '<hr>'.PHP_EOL
            .'<strong>Información del examen:</strong>'.PHP_EOL
            .'<ul>'
            .'<li>Fecha y hora del examen tomado: '.api_convert_and_format_date($exercise->getExeDate()).'</li>'
            .'<li>Estudiante (código): '.$student->getUsername().'</li>'
            .'<li>ID de examen tomado: '.$exercise->getExeId().'</li>'
            .'</ul>';

        api_mail_html(
            'Adaptive Plex Support',
            self::$plugin->get(IcpnaPlexConfigPlugin::SETTING_ERROR_EMAIL),
            'Adaptive PLEX: Error en proceso de matrícula.',
            $mailMessage
        );
    }
}
