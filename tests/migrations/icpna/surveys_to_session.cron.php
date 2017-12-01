<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CSurvey;

if (PHP_SAPI != 'cli') {
    exit;
}

require_once __DIR__.'/../../../main/inc/global.inc.php';

$em = Database::getManager();

$masterCourseCode = 'MSV';
/** @var \DateTime $today */
$today = api_get_utc_datetime(null, false, true);
$todayDate = $today->format('Y-m-d');

$result = Database::query("SELECT * FROM survey_calendar WHERE DATE(reply_date) = '$todayDate'");
$surveysCount = Database::num_rows($result);

echo "Searching surveys to reply".PHP_EOL;
echo "Date: ".$todayDate.PHP_EOL;
echo "Surveys found: $surveysCount".PHP_EOL;

if (!$surveysCount) {
    echo "Exiting".PHP_EOL;
    exit;
}

/** @var Course $masterCourse */
$masterCourse = $em->getRepository('ChamiloCoreBundle:Course')->findOneBy(['code' => $masterCourseCode]);

if (!$masterCourse) {
    echo "Course Master Survey not found".PHP_EOL;
    exit;
}

ChamiloSession::write('_real_cid', $masterCourse->getId());

while ($row = Database::fetch_assoc($result)) {
    $dql = "
        SELECT s FROM ChamiloCoreBundle:Session s
        INNER JOIN ChamiloCoreBundle:ExtraFieldValues branchV WITH s.id = branchV.itemId
        INNER JOIN ChamiloCoreBundle:ExtraField branchF WITH branchV.field = branchF.id
        INNER JOIN ChamiloCoreBundle:ExtraFieldValues periodV WITH s.id = periodV.itemId
        INNER JOIN ChamiloCoreBundle:ExtraField periodF WITH periodV.field = periodF.id
        WHERE branchF.variable = 'sede' AND branchV.value = :branch
            AND periodF.variable = 'periodo' AND periodV.value = :period
            AND (
                SELECT COUNT(sc) FROM ChamiloCoreBundle:SessionRelCourse sc
                INNER JOIN ChamiloCoreBundle:Course c WITH sc.course = c
                INNER JOIN ChamiloCoreBundle:ExtraFieldValues frequencyV WITH c.id = frequencyV.itemId
                INNER JOIN ChamiloCoreBundle:ExtraField frequencyF WITH frequencyV.field = frequencyF.id
                INNER JOIN ChamiloCoreBundle:ExtraFieldValues intensityV WITH c.id = intensityV.itemId
                INNER JOIN ChamiloCoreBundle:ExtraField intensityF WITH intensityV.field = intensityF.id
                WHERE sc.session = s
                    AND frequencyF.variable = 'frecuencia' AND frequencyV.value = :frequency
                    AND intensityF.variable = 'intensidad' AND intensityV.value = :intensity
            ) > 0
    ";
    $sessions = $em
        ->createQuery($dql)
        ->setParameters([
            'branch' => $row['branch'],
            'period' => $row['period'],
            'frequency' => $row['frequency'],
            'intensity' => $row['intensity'],
        ])
        ->getResult();

    /** @var Session $session */
    foreach ($sessions as $session) {
        if (!$session) {
            continue;
        }

        echo "Session found ("
            ."id: {$session->getId()} "
            ."branch: {$row['branch']} period: {$row['period']} "
            ."frequency: {$row['frequency']} intensity: {$row['intensity']})".PHP_EOL;

        ChamiloSession::write('id_session', $session->getId());

        /** @var Course $sessionCourse */
        $sessionCourse = $session->getCourses()->first()->getCourse();

        $efv = new ExtraFieldValue('course');
        $efSurvey = $efv->get_values_by_handler_and_field_variable($sessionCourse->getId(), 'survey');

        replicateInSessions(
            $masterCourse,
            $session,
            $sessionCourse,
            $efSurvey['value'],
            new DateTime($row['start_date'], new DateTimeZone('UTC')),
            new DateTime($row['end_date'], new DateTimeZone('UTC'))
        );
    }
}

echo 'Finish'.PHP_EOL;

/**
 * @param \Chamilo\CoreBundle\Entity\Course $originCourse
 * @param \Chamilo\CoreBundle\Entity\Session $session
 * @param \Chamilo\CoreBundle\Entity\Course $sessionCourse
 * @param string $surveyCode
 * @param \DateTime $fixedDayToStart
 * @param \DateTime $fixedDayToEnd
 * @throws \Doctrine\ORM\ORMException
 * @throws \Doctrine\ORM\OptimisticLockException
 * @throws \Doctrine\ORM\TransactionRequiredException
 */
function replicateInSessions(
    Course $originCourse,
    Session $session,
    Course $sessionCourse,
    $surveyCode,
    DateTime $fixedDayToStart,
    DateTime $fixedDayToEnd
) {
    $em = Database::getManager();

    $courseSurveys = SurveyUtil::getCourseSurveys($originCourse->getId());

    echo "Replicating survey in session {$session->getId()}".PHP_EOL;

    $users = getSessionUsersForInvitation($session, $sessionCourse);

    /** @var CSurvey $survey */
    foreach ($courseSurveys as $survey) {
        if (strpos($survey->getCode(), $surveyCode) === false) {
            continue;
        }

        echo "\tReplicating survey {$survey->getCode()}:".PHP_EOL;

        if (SurveyUtil::existsSurveyCodeInCourse($surveyCode, $sessionCourse->getId(), $session->getId())) {
            echo "\t\tSurvey code {$survey->getCode()} already exists in session {$session->getId()}".PHP_EOL;
            continue;
        }

        $newSurveyIds = SurveyUtil::copy(
            $survey->getSurveyId(),
            $originCourse->getCode(),
            0,
            $sessionCourse->getCode(),
            $session->getId()
        );
        $newSurveyId = current($newSurveyIds);

        /** @var CSurvey $newSurvey */
        $newSurvey = $em->find('ChamiloCourseBundle:CSurvey', $newSurveyId);
        echo "\t\tNew survey created with code {$newSurvey->getCode()} in session {$session->getId()}".PHP_EOL;

        $newSurvey
            ->setAvailFrom($fixedDayToStart)
            ->setAvailTill($fixedDayToEnd)
            ->setCreationDate(
                api_get_utc_datetime(null, false, true)
            );
        $em->persist($newSurvey);
        $em->flush();

        $_GET['survey_id'] = $newSurvey->getSurveyId();
        $_GET['course'] = $sessionCourse->getCode();

        SurveyUtil::saveInvitations($users, '', '', 0, false, 0);
        SurveyUtil::update_count_invited($newSurvey->getCode());

        echo "\t\tUsers in session course were invited".PHP_EOL;
    }
}

/**
 * @param \Chamilo\CoreBundle\Entity\Session $session
 * @param \Chamilo\CoreBundle\Entity\Course $course
 * @return array
 */
function getSessionUsersForInvitation(Session $session, Course $course)
{
    $users = [];
    $sessionCourseUsers = $session->getUserCourseSubscriptionsByStatus($course, Session::STUDENT);

    foreach ($sessionCourseUsers as $sessionCourseUser) {
        $users[] = "USER:{$sessionCourseUser->getUser()->getId()}";
    }

    return $users;
}
