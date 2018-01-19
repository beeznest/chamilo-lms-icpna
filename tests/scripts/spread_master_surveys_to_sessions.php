<?php
/* For licensing terms, see /license.txt */
/**
 * This script allows you to replicate a survey from a base course to other
 * base courses or from base courses to session courses
 * @ref BT#13203
 */

die();

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

use Chamilo\CoreBundle\Entity\Session,
    Chamilo\CoreBundle\Entity\Course,
    Chamilo\CourseBundle\Entity\CSurvey;

require_once __DIR__.'/../../main/inc/global.inc.php';

// Set the origin course code
$originCourseCode = 'SVM';
$destCourseCodes = [];
//Set the uididprograma value (a session extra field) to filter sessions
$uididprogramaFilter = [
];
// Set the branch value to filter sessions
// (eg: select * from extra_field_options where field_id = 28)
$sedeFilter = [
    //'8F67B2B3-667E-4EBC-8605-766D2FF71B55', // Cent
    //'7379A7D3-6DC5-42CA-9ED4-97367519F1D9', // Mira
    //'30DE73B6-8203-4F81-96C8-3B27977BB924', // Smig
    //'8BA65461-60B5-4716-BEB3-22BC7B71BC09', // Moli
    //'257AD17D-91F7-4BC8-81D4-71EBD35A4E50', // Nort
    //'AC2CD7F4-A61D-45B3-9954-5A91FA2D8B95', // CPas
    //'3575A639-E933-4462-A173-6DFBFE45501B', // Abcy
    //'7BF57202-B174-4113-BAA3-C9A9C3753FD4', // Hraz
    //'6944EC08-1CA5-40D2-9576-850DE912DBEF', // Icaa
    //'9FB2971E-1424-4AC6-980F-AEF856EF249F', // Anda
    //'FE3AABDB-531D-4601-A3D4-E4F697335806', // Surc
    //'CE894D3F-E9E1-476C-9314-764DC0BCD003', // Chin
    //'EF7BF999-E359-40C1-A712-BCA0450888F4', // Iqui
    //'1CE8E5F9-56D2-4C35-B3C1-ED0D91C3D4B1', // Chim
    //'1BCE2204-76C3-4CC6-A0E3-8B7164FC76A4', // Puca
];
$surveyCodeFilter = '';
$period = ''; //e.g. '201712';
$surveyFixedDayToStart = ''; // e.g. '2017-09-16 05:00:00'; (include timezone shift)
$surveyFixedDayToEnd = ''; // e.g. '2017-09-24 05:00:00'; (include timezone shift)

// Main
$em = Database::getManager();

/** @var Course $course */
$course = $em->getRepository('ChamiloCoreBundle:Course')->findOneBy(['code' => $originCourseCode]);

if (empty($course)) {
    echo "Course of code $courseCode not found in DB".PHP_EOL;

    exit;
}

replicateInSessions(
    $course,
    $destCourseCodes,
    $uididprogramaFilter,
    $sedeFilter,
    $surveyFixedDayToStart,
    $surveyFixedDayToEnd,
    $surveyCodeFilter
);

echo "Exiting".PHP_EOL;

/**
 * @param \Chamilo\CoreBundle\Entity\Course $originCourse
 * @param array $destCourseCodes
 * @param array $uididprogramaFilter Optional. uididprograma extra field to filter
 * @param array $sedeFilter Optional. sede extra field to filter
 * @param $fixedDayToStart
 * @param $fixedDayToEnd
 * @param $surveyCodeFilter
 * @throws \Doctrine\ORM\ORMException
 * @throws \Doctrine\ORM\OptimisticLockException
 * @throws \Doctrine\ORM\TransactionRequiredException
 */
function replicateInSessions(
    Course $originCourse,
    array $destCourseCodes,
    array $uididprogramaFilter = [],
    array $sedeFilter = [],
    $fixedDayToStart,
    $fixedDayToEnd,
    $surveyCodeFilter
)
{
    $em = Database::getManager();
    echo "Replicating surveys from course ".$originCourse->getCode()." to all its sessions".PHP_EOL;

    $courseSurveys = SurveyUtil::getCourseSurveys($originCourse->getId());

    // Filter surveys
    if (!empty($surveyCodeFilter)) {
        $courseSurveys = array_filter($courseSurveys, function (CSurvey $survey) use ($surveyCodeFilter) {
            return $survey->getCode() === $surveyCodeFilter;
        });
    }

    //Get sessions from de origin course for this month
    if (!empty($destCourseCodes)) {
        $sessions = $em
            ->createQuery("
                SELECT s FROM ChamiloCoreBundle:Session s
                INNER JOIN ChamiloCoreBundle:SessionRelCourse sc WITH s = sc.session
                INNER JOIN ChamiloCoreBundle:Course c WITH sc.course = c
                WHERE c.code IN ('".implode("', '", $destCourseCodes)."')
                    AND s.name LIKE :period
            ")
            ->setParameters([
                'period' => "% - {$GLOBALS['period']} - %"
            ])
            ->getResult();
    } else {
        $sessions = $em
            ->createQuery("
                SELECT s FROM ChamiloCoreBundle:Session s
                INNER JOIN ChamiloCoreBundle:SessionRelCourse sc WITH s = sc.session
                WHERE s.name LIKE :period
                  AND sc.course = :course
            ")
            ->setParameters(['course' => $originCourse, 'period' => "% - {$GLOBALS['period']} - %"])
            ->getResult();
    }

    if ($uididprogramaFilter) {
        $sessions = filterSessionsByUididprograma($sessions, $uididprogramaFilter);
    }

    if ($sedeFilter) {
        $sessions = filterSessionsBySede($sessions, $sedeFilter);
    }

    $countSessions = 0;

    /** @var Session $session */
    foreach ($sessions as $session) {
        echo 'Session No. '.++$countSessions.PHP_EOL;
        echo "Attempt to replicate surveys in session {$session->getId()}".PHP_EOL;

        /** @var Course $sessionCourse */
        $sessionCourse = $session->getCourses()->first()->getCourse();

        ChamiloSession::write('id_session', $session->getId());
        ChamiloSession::write('_real_cid', $sessionCourse->getId());

        $users = getSessionUsersForInvitation($session, $sessionCourse);

        /** @var CSurvey $survey */
        foreach ($courseSurveys as $survey) {
            echo "\tReplicate survey {$survey->getCode()}".PHP_EOL;

            if (SurveyUtil::existsSurveyCodeInCourse($survey->getCode(), $sessionCourse->getId(), $session->getId())) {
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

            /** @var CSurvey $newSurvey */
            $newSurvey = $em->find('ChamiloCourseBundle:CSurvey', current($newSurveyIds));
            echo "\t\tNew survey created with code {$newSurvey->getCode()} in session {$session->getId()}".PHP_EOL;

            //Calculate new survey date
            $newSurveyAvailFrom = new DateTime($fixedDayToStart);
            $newSurveyAvailTill = new DateTime($fixedDayToEnd);

            $newSurvey
                ->setAvailFrom($newSurveyAvailFrom)
                ->setAvailTill($newSurveyAvailTill);
            $em->persist($newSurvey);
            $em->flush();

            $_GET['survey_id'] = $newSurvey->getSurveyId();
            $_GET['course'] = $sessionCourse->getCode();

            SurveyUtil::saveInvitations($users, '', '', 0, false, 0);
            SurveyUtil::update_count_invited($newSurvey->getCode(), $sessionCourse->getId(), $session->getId());

            echo "\t\tUsers in session course were invited".PHP_EOL;
        }
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

/**
 * @param array $sessions
 * @param array $uididprogramas
 * @return array
 */
function filterSessionsByUididprograma(array $sessions, array $uididprogramas)
{
    $em = Database::getManager();

    return array_filter($sessions, function (Session $session) use ($em, $uididprogramas) {
        $sessionIsFound = $em
            ->createQuery("
                SELECT COUNT(efv) FROM ChamiloCoreBundle:ExtraFieldValues efv
                INNER JOIN ChamiloCoreBundle:ExtraField ef WITH efv.field = ef
                WHERE ef.variable = :variable
                  AND efv.value IN ('".implode("', '", $uididprogramas)."')
                  AND efv.itemId = :session
            ")
            ->setParameters(['variable' => 'uididprograma', 'session' => $session->getId()])
            ->getSingleScalarResult();

        return $sessionIsFound > 0;
    });
}

/**
 * @param array $sessions
 * @param array $sedes
 * @return array
 */
function filterSessionsBySede(array $sessions, array $sedes)
{
    $em = Database::getManager();

    return array_filter($sessions, function (Session $session) use ($em, $sedes) {
        $sessionIsFound = $em
            ->createQuery("
                SELECT COUNT(efv) FROM ChamiloCoreBundle:ExtraFieldValues efv
                INNER JOIN ChamiloCoreBundle:ExtraField ef WITH efv.field = ef
                WHERE ef.variable = :variable
                  AND efv.value IN ('".implode("', '", $sedes)."')
                  AND efv.itemId = :session
            ")
            ->setParameters(['variable' => 'sede', 'session' => $session->getId()])
            ->getSingleScalarResult();

        return $sessionIsFound > 0;
    });
}
