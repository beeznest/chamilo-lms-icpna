<?php
/* For licensing terms, see /license.txt */

/**
 * Delete surveys from session
 * You need indicate the session ids from where surveys will be delete in $surveysBySession array
 */

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CourseBundle\Entity\CSurvey;

exit(); //remove this line to execute from the command line

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

/** @var array $surveysBySession The session ids */
$surveysBySession = [
];

$em = Database::getManager();
$surveyRepo = $em->getRepository('ChamiloCourseBundle:CSurvey');

foreach ($surveysBySession as $sessionId) {
    echo "Deleting surveys from session \"{$sessionId}\"".PHP_EOL;

    /** @var Session $session */
    $session = $em->find('ChamiloCoreBundle:Session', $sessionId);

    if (!$session) {
        echo "\tSession \"{$session->getId()}\" not found".PHP_EOL;

        continue;
    }

    $sessionCourses = $session->getCourses();

    if ($sessionCourses->count() === 0) {
        echo "\tSession \"{$session->getId()}\" has no courses".PHP_EOL;

        continue;
    }

    /** @var SessionRelCourse $sessionCourse */
    foreach ($sessionCourses as $sessionCourse) {
        $course = $sessionCourse->getCourse();

        echo "\tDeleting surveys from course \"{$course->getCode()}\"".PHP_EOL;

        $surveys = $surveyRepo->findBy([
            'sessionId' => $session->getId(),
            'cId' => $course->getId()
        ]);

        if (count($surveys) === 0) {
            echo "\t\tCourse \"{$course->getCode()}\" has no surveys".PHP_EOL;

            continue;
        }

        /** @var CSurvey $survey */
        foreach ($surveys as $survey) {
            echo "\t\tDeleteing from survey \"{$survey->getCode()}\"".PHP_EOL;

            $em
                ->createQuery("
                    DELETE FROM ChamiloCourseBundle:CSurveyInvitation si
                    WHERE si.sessionId = :SESSION AND si.cId = :course AND si.surveyCode = :survey 
                ")
                ->execute([
                    'session' => $session,
                    'course' => $course->getId(),
                    'survey' => $survey->getCode()
                ]);

            echo "\t\t\tSurvey invitations deleted".PHP_EOL;

            $em
                ->createQuery('
                    DELETE FROM ChamiloCourseBundle:CSurveyAnswer sa
                    WHERE sa.cId = :course AND sa.surveyId = :survey
                ')
                ->execute([
                    'course' => $course->getId(),
                    'survey' => $survey->getSurveyId()
                ]);

            echo "\t\t\tSurvey answers deleted".PHP_EOL;

            $em->remove($survey);
            $em->flush();

            echo "\t\t\tSurvey deleted".PHP_EOL;
        }
    }
}

echo "Exiting";
