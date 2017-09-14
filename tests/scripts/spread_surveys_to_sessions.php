<?php
/* For licensing terms, see /license.txt */
/**
 * This script allows you to replicate a survey from a base course to other
 * base courses or from base courses to session courses
 * @ref BT#13203
 */

//die();

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

use Chamilo\CoreBundle\Entity\Session,
    Chamilo\CoreBundle\Entity\Course,
    Chamilo\CourseBundle\Entity\CSurvey;

require_once __DIR__.'/../../main/inc/global.inc.php';

// Set the origin course code
$originCourseCodes = [
    'B01',
];
//Set course codes (not in sessions) to replicate the survey
$destinationCourseCodes = [
    'B02',
];
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
$surveyDayNumberToStart = 13;
$surveyDayNumberToEnd = 15;
// If fixed dates are defined, they will superseed the relative number of days above
$surveyFixedDayToStart = ''; // e.g. '2017-09-16 05:00:00'; (include timezone shift)
$surveyFixedDayToEnd = ''; // e.g. '2017-09-24 05:00:00'; (include timezone shift)

$em = Database::getManager();

foreach ($originCourseCodes as $courseCode) {
    /** @var Course $course */
    $course = $em->getRepository('ChamiloCoreBundle:Course')->findOneBy(['code' => $courseCode]);
    if (empty($course)) {
        echo "Course of code $courseCode not found in DB".PHP_EOL;
        continue;
    }

    ChamiloSession::write('_real_cid', $course->getId());

    echo "Replicate surveys from {$course->getCode()} to ".count($destinationCourseCodes)." courses".PHP_EOL;
    echo PHP_EOL;

    if (count($destinationCourseCodes) > 0) {
        echo count($destinationCourseCodes)." destination base courses selected".PHP_EOL;
        replicateInCourses($course, $destinationCourseCodes);
    } else {
        replicateInSessions(
            $course,
            $surveyDayNumberToStart,
            $surveyDayNumberToEnd,
            $uididprogramaFilter,
            $sedeFilter,
            $surveyFixedDayToStart,
            $surveyFixedDayToEnd
        );
    }
}

echo "Exiting".PHP_EOL;

/**
 * Replicate all surveys from given origin course to all destination courses
 * @param \Chamilo\CoreBundle\Entity\Course $originCourse
 * @param array $destinationCourseCodes
 */
function replicateInCourses(Course $originCourse, array $destinationCourseCodes) {
    $courseSurveys = SurveyUtil::getCourseSurveys($originCourse->getId());
    echo "Replicating surveys from course ".$originCourse->getCode().PHP_EOL;

    $em = Database::getManager();
    $courseRepo = $em->getRepository('ChamiloCoreBundle:Course');

    foreach ($destinationCourseCodes as $courseCode) {
        echo "Attempt to replicate surveys in course $courseCode.".PHP_EOL;
        /** @var Course $courseToReplicate */
        $courseToReplicate = $courseRepo->findOneBy(['code' => $courseCode]);

        if (!$courseToReplicate) {
            echo "\tCourse does not exists.".PHP_EOL;
            continue;
        }

        /** @var CSurvey $survey */
        foreach ($courseSurveys as $survey) {
            // Surveys are only considered if their code ends with 'survey'
            if (substr($survey->getCode(), -6, 6) != 'survey') {
                continue;
            }

            echo "\tReplicate survey {$survey->getCode()}".PHP_EOL;

            if (SurveyUtil::existsSurveyCodeInCourse($survey->getCode(), $courseToReplicate->getId())) {
                echo "\t\tSurvey {$survey->getCode()} already exists in course.".PHP_EOL;
                continue;
            }

            $newSurveyIds = SurveyUtil::copy($survey->getSurveyId(), $originCourse->getCode(), 0, $courseCode, 0);
            /** @var CSurvey $replicatedSurvey */
            $replicatedSurvey = $em->find('ChamiloCourseBundle:CSurvey', current($newSurveyIds));
            echo "\t\tNew survey created with code {$replicatedSurvey->getCode()}".PHP_EOL;
        }
    }
}

/**
 * @param \Chamilo\CoreBundle\Entity\Course $originCourse
 * @param int $dayNumberToStart number of day to start survey
 * @param int $dayNumberToEnd number of day to end survey
 * @param array $uididprogramaFilter Optional. uididprograma extra field to filter
 * @param array $sedeFilter Optional. sede extra field to filter
 */
function replicateInSessions(
    Course $originCourse,
    $dayNumberToStart,
    $dayNumberToEnd,
    array $uididprogramaFilter = [],
    array $sedeFilter = [],
    $fixedDayToStart,
    $fixedDayToEnd
)
{
    $em = Database::getManager();
    echo "Replicating surveys from course ".$originCourse->getCode()." to all its sessions".PHP_EOL;

    $monthStart = new DateTime('first day of this month 00:00:00', new DateTimeZone('UTC'));
    // Account for some sessions starting a few days before the start of this month
    $monthStart->modify('- 5 days');
    $monthEnd = new DateTime('last day of this month 23:59:59', new DateTimeZone('UTC'));
    $monthEnd->modify('+ 5 days');

    $courseSurveys = SurveyUtil::getCourseSurveys($originCourse->getId());

    //Get sessions from de origin course for this month
    $sessions = $em
        ->createQuery("
            SELECT s FROM ChamiloCoreBundle:Session s
            INNER JOIN ChamiloCoreBundle:SessionRelCourse sc WITH s = sc.session
            WHERE s.accessStartDate >= :month_start AND s.accessEndDate <= :month_end
              AND sc.course = :course
        ")
        ->setParameters(['month_start' => $monthStart, 'month_end' => $monthEnd, 'course' => $originCourse])
        ->getResult();

    if ($uididprogramaFilter) {
        $sessions = filterSessionsByUididprograma($sessions, $uididprogramaFilter);
    }

    if ($sedeFilter) {
        $sessions = filterSessionsBySede($sessions, $sedeFilter);
    }

    /** @var Session $session */
    foreach ($sessions as $session) {
        ChamiloSession::write('id_session', $session->getId());

        echo "Attempt to replicate surveys in session {$session->getId()}".PHP_EOL;

        $users = getSessionUsersForInvitation($session, $originCourse);

        /** @var CSurvey $survey */
        foreach ($courseSurveys as $survey) {
            echo "\tReplicate survey {$survey->getCode()}".PHP_EOL;

            if (SurveyUtil::existsSurveyCodeInCourse($survey->getCode(), $originCourse->getId(), $session->getId())) {
                echo "\t\tSurvey code {$survey->getCode()} already exists in session {$session->getId()}".PHP_EOL;
                continue;
            }

            $newSurveyIds = SurveyUtil::copy(
                $survey->getSurveyId(),
                $originCourse->getCode(),
                0,
                $originCourse->getCode(),
                $session->getId()
            );

            /** @var CSurvey $newSurvey */
            $newSurvey = $em->find('ChamiloCourseBundle:CSurvey', current($newSurveyIds));
            echo "\t\tNew survey created with code {$newSurvey->getCode()} in session {$session->getId()}".PHP_EOL;

            //Calculate new survey date
            if (!empty($fixedDayToStart) && !empty($fixedDayToEnd)) {
                $newSurveyAvailFrom = new DateTime($fixedDayToStart);
                $newSurveyAvailTill = new DateTime($fixedDayToEnd);
            } else {
                $newSurveyAvailFrom = clone $session->getAccessStartDate();
                $newSurveyAvailFrom->modify('+'.($dayNumberToStart - 1).'days');
                $newSurveyAvailTill = clone $session->getAccessStartDate();
                $newSurveyAvailTill->modify('+'.($dayNumberToEnd - 1).'days');
            }

            $newSurvey
                ->setAvailFrom($newSurveyAvailFrom)
                ->setAvailTill($newSurveyAvailTill);
            $em->persist($newSurvey);
            $em->flush();

            $_GET['survey_id'] = $newSurvey->getSurveyId();
            $_GET['course'] = $originCourse->getCode();

            SurveyUtil::saveInvitations($users, '', '', 0, false, 0);
            SurveyUtil::update_count_invited($newSurvey->getCode());

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
