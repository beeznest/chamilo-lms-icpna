<?php
/* For licensing terms, see /license.txt */

die();

use Chamilo\CourseBundle\Entity\CSurvey;

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

$em = Database::getManager();

$r0 = $em
    ->createQuery('
        SELECT s FROM ChamiloCourseBundle:CSurvey s
        ORDER BY s.invited DESC
    ')
    ->getResult();

/** @var CSurvey $survey */
foreach ($r0 as $survey) {
    $invited = $em
        ->createQuery("
            SELECT COUNT(si) FROM ChamiloCourseBundle:CSurveyInvitation si
            WHERE si.cId = :course AND si.surveyCode = :code AND si.sessionId = :session
        ")
        ->setParameters([
            'course' => $survey->getCId(),
            'code' => $survey->getCode(),
            'session' => $survey->getSessionId()
        ])
        ->getSingleScalarResult();

    echo "Survey: {$survey->getCode()} - Course: {$survey->getCId()} - Session: {$survey->getSessionId()}".PHP_EOL;

    $survey->setInvited($invited);
    $em->persist($survey);
    $em->flush();

    echo "  -- Invited: {$invited}".PHP_EOL;
}

echo 'Ending'.PHP_EOL;
