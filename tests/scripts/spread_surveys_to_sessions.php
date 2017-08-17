<?php
/* For licensing terms, see /license.txt */

die();

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

use Chamilo\CoreBundle\Entity\Session,
    Chamilo\CoreBundle\Entity\SessionRelCourse,
    Chamilo\CourseBundle\Entity\CSurvey;

require_once __DIR__.'/../../main/inc/global.inc.php';

$em = Database::getManager();
$monthStart = new DateTime('first day of this month 00:00:00', new DateTimeZone('UTC'));
$monthEnd = new DateTime('last day of this month 23:59:59', new DateTimeZone('UTC'));

// Todas las sesiones que empiezan este mes
$sessions = $em
    ->createQuery("
        SELECT s FROM ChamiloCoreBundle:Session s
        WHERE s.accessStartDate >= :month_start AND s.accessStartDate <= :month_end
    ")
    ->setParameters(['month_start' => $monthStart, 'month_end' => $monthEnd])
    ->getResult();

/** @var Session $session */
foreach ($sessions as $session) {
    /** @var SessionRelCourse $sessionCourse */
    $sessionCourse = $session->getCourses()->first();

    if (!$sessionCourse) {
        continue;
    }

    $course = $sessionCourse->getCourse();
    $surveys = SurveyUtil::getCourseSurveys($course->getId());
    $users = [];

    // Hack for get course code and session id
    ChamiloSession::write('_real_cid', $course->getId());
    ChamiloSession::write('id_session', $session->getId());

    $sessionCourseUsers = $session->getUserCourseSubscriptionsByStatus($course, Session::STUDENT);

    foreach ($sessionCourseUsers as $sessionCourseUser) {
        $users[] = "USER:{$sessionCourseUser->getUser()->getId()}";
    }

    /** @var CSurvey $survey */
    foreach ($surveys as $survey) {
        if (SurveyUtil::existsSurveyCodeInCourse($survey->getCode(), $course->getId(), $session->getId())) {
            echo 'Survey code '.$survey->getCode().' already exists in session '.$session->getId().'.'.PHP_EOL;
            continue;
        }

        echo "Creating survey with code '{$survey->getCode()}' in session {$session->getId()}".PHP_EOL;

        $newSurveyIds = SurveyUtil::copy(
            $survey->getSurveyId(),
            $course->getCode(),
            0,
            $course->getCode(),
            $session->getId()
        );
        /** @var CSurvey $newSurvey */
        $newSurvey = $em->find('ChamiloCourseBundle:CSurvey', current($newSurveyIds));
        echo "---- New survey created with code {$newSurvey->getCode()} in session {$session->getId()}".PHP_EOL;

        // Hack for get course code and session id
        $_GET['survey_id'] = $newSurvey->getSurveyId();
        $_GET['course'] = $course->getCode();

        SurveyUtil::saveInvitations($users, '', '', 0, false, 0);
        SurveyUtil::update_count_invited($newSurvey->getCode());
        echo "---- Users in session course were invited".PHP_EOL;
        echo PHP_EOL;
    }
}
