<?php
/* For licensing terms, see /license.txt */

die();

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

use Chamilo\CoreBundle\Entity\Session,
    Chamilo\CoreBundle\Entity\Course,
    Chamilo\CourseBundle\Entity\CSurvey,
    Chamilo\CoreBundle\Entity\SessionRelCourse;

require_once __DIR__.'/../../main/inc/global.inc.php';

$sessionIds = [
    2, 4, 5, 6
];

$em = Database::getManager();

foreach ($sessionIds as $sessionId) {
    /** @var Session $session */
    $session = $em->find('ChamiloCoreBundle:Session', $sessionId);

    ChamiloSession::write('id_session', $session->getId());

    $sessionCourses = $session->getCourses();

    /** @var SessionRelCourse $sessionCourse */
    foreach ($sessionCourses as $sessionCourse) {
        $course = $sessionCourse->getCourse();

        ChamiloSession::write('_real_cid', $course->getId());

        $users = getSessionUsersForInvitation($session, $course);
        $courseSurveys = SurveyUtil::getCourseSurveys($course->getId());

        /** @var CSurvey $survey */
        foreach ($courseSurveys as $survey) {
            $_GET['survey_id'] = $survey->getSurveyId();
            $_GET['course'] = $course->getCode();

            SurveyUtil::saveInvitations($users, '', '', 0, false, 0);
            SurveyUtil::update_count_invited($survey->getCode());
        }
    }
}

echo "Exiting".PHP_EOL;

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
