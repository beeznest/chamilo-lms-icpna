<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizDestinationResult;
use Chamilo\PluginBundle\ExerciseFocused\Entity\Log as FocusedLog;
use Chamilo\PluginBundle\ExerciseMonitoring\Entity\Log as MonitoringLog;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_course_script(true);

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$plugin = ExerciseFocusedPlugin::create();
$monitoringPlugin = ExerciseMonitoringPlugin::create();
$monitoringPluginIsEnabled = $monitoringPlugin->isEnabled(true);
$request = HttpRequest::createFromGlobals();
$em = Database::getManager();
$focusedLogRepository = $em->getRepository(FocusedLog::class);

$attempsRepository = $em->getRepository(TrackEAttempt::class);

if (!$plugin->isEnabled(true)) {
    api_not_allowed(true);
}

$params = $request->query->all();

$results = findResults($params, $em, $plugin);

$data = [];

/** @var array<string, mixed> $result */
foreach ($results as $result) {
    /** @var TrackEExercises $trackExe */
    $trackExe = $result['exe'];
    $user = api_get_user_entity($trackExe->getExeUserId());

    $outfocusedLimitCount = $focusedLogRepository->countByActionInExe($trackExe, FocusedLog::TYPE_OUTFOCUSED_LIMIT);
    $timeLimitCount = $focusedLogRepository->countByActionInExe($trackExe, FocusedLog::TYPE_TIME_LIMIT);

    $exercise = new Exercise($trackExe->getCId());
    $exercise->read($trackExe->getExeExoId());

    $quizType = (int) $exercise->selectType();

    $data[] = [
        get_lang('LoginName'),
        $user->getUsername(),
    ];
    $data[] = [
        get_lang('Student'),
        $user->getFirstname(),
        $user->getLastname(),
    ];

    if ($monitoringPluginIsEnabled
        && 'true' === $monitoringPlugin->get(ExerciseMonitoringPlugin::SETTING_INSTRUCTION_AGE_DISTINCTION_ENABLE)
    ) {
        $fieldVariable = $monitoringPlugin->get(ExerciseMonitoringPlugin::SETTING_EXTRAFIELD_BIRTHDATE);
        $birthdateValue = UserManager::get_extra_user_data_by_field($user->getId(), $fieldVariable);

        $data[] = [
            $monitoringPlugin->get_lang('Birthdate'),
            $birthdateValue ? $birthdateValue[$fieldVariable] : '----',
            $monitoringPlugin->isAdult($user->getId())
                ? $monitoringPlugin->get_lang('AdultStudent')
                : $monitoringPlugin->get_lang('MinorStudent'),
        ];
    }

    if ($trackExe->getSessionId()) {
        $data[] = [
            get_lang('SessionName'),
            api_get_session_entity($trackExe->getSessionId())->getName(),
        ];
    } else {
        $data[] = [
            get_lang('CourseTitle'),
            api_get_course_entity($trackExe->getCId())->getTitle(),
        ];
    }

    $destinationResult = getAchievedResult($em, (int) $trackExe->getExeId());

    $data[] = [
        $plugin->get_lang('ExerciseStartDateAndTime'),
        api_get_local_time($result['exe']->getStartDate(), null, null, true, true, true),
    ];
    $data[] = [
        $plugin->get_lang('ExerciseEndDateAndTime'),
        api_get_local_time($result['exe']->getExeDate(), null, null, true, true, true),
    ];
    $data[] = [
        get_lang('IP'),
        $result['exe']->getUserIp(),
    ];

    if ($markedMotive = ExerciseFocusedPlugin::getTrackExeMotive($result['exe']->getExeId())) {
        if (ExerciseFocusedPlugin::TRACK_EXE_MOTIVE_EXPIRED_TIME_CATEGORY === $markedMotive) {
            $data[] = [
                $plugin->get_lang('Motive'),
                $plugin->get_lang('MotiveExpiredTime'),
            ];
        }
    } else {
        $data[] = [
            $plugin->get_lang('Motive'),
            $plugin->calculateMotive($outfocusedLimitCount, $timeLimitCount),
        ];
    }

    if ($destinationResult) {
        $data[] = [
            $plugin->get_lang('LevelReached'),
            $destinationResult,
        ];
    }

    $data[] = [];

    $data[] = [
        $plugin->get_lang('LevelReached'),
        get_lang('DateTime'),
        get_lang('Score'),
        $plugin->get_lang('Outfocused'),
        $plugin->get_lang('Returns'),
        $monitoringPluginIsEnabled ? $monitoringPlugin->get_lang('Snapshots') : '',
    ];

    if (ONE_PER_PAGE === $quizType) {
        $questionList = explode(',', $trackExe->getDataTracking());

        foreach ($questionList as $idx => $questionId) {
            $attempt = $attempsRepository->findOneBy(
                ['exeId' => $trackExe->getExeId(), 'questionId' => $questionId],
                ['tms' => 'DESC']
            );

            if (!$attempt) {
                continue;
            }

            $result = $exercise->manage_answer(
                $trackExe->getExeId(),
                $questionId,
                null,
                'exercise_result',
                false,
                false,
                true,
                false,
                $exercise->selectPropagateNeg()
            );

            $row = [
                get_lang('QuestionNumber').' '.($idx + 1),
                api_get_local_time($attempt->getTms()),
                $result['score'].' / '.$result['weight'],
                $focusedLogRepository->countByActionAndLevel($trackExe, FocusedLog::TYPE_OUTFOCUSED, $questionId),
                $focusedLogRepository->countByActionAndLevel($trackExe, FocusedLog::TYPE_RETURN, $questionId),
                getSnapshotListForLevel($questionId, $trackExe),
            ];

            $data[] = $row;
        }
    } elseif (ALL_ON_ONE_PAGE === $quizType) {
    } elseif (ONE_CATEGORY_PER_PAGE === $quizType) {
        $rowsByMonitoring = false;

        if ($monitoringPluginIsEnabled
            && 'true' === $monitoringPlugin->get(ExerciseMonitoringPlugin::SETTING_INSTRUCTION_AGE_DISTINCTION_ENABLE)
            && $monitoringPlugin->isAdult($user->getId())
        ) {
                $rowsByMonitoring = true;
        }

        if ($rowsByMonitoring) {
            $rows = getOneCategoryPerPageByMonitoring($exercise, $trackExe, $user, $monitoringPlugin);
        } else {
            $rows = getOneCategoryPerPageByFocused($trackExe);
        }

        array_push($data, ...$rows);
    }

    $data[] = [];
    $data[] = [];
    $data[] = [];
}

Export::arrayToXls($data);

function getSessionIdFromFormValues(array $formValues, array $fieldVariableList): array
{
    $fieldItemIdList = [];
    $objFieldValue = new ExtraFieldValue('session');

    foreach ($fieldVariableList as $fieldVariable) {
        if (!isset($formValues["extra_$fieldVariable"])) {
            continue;
        }

        $itemValues = $objFieldValue->get_item_id_from_field_variable_and_field_value(
            $fieldVariable,
            $formValues["extra_$fieldVariable"],
            false,
            false,
            true
        );

        foreach ($itemValues as $itemValue) {
            $fieldItemIdList[] = (int) $itemValue['item_id'];
        }
    }

    return array_unique($fieldItemIdList);
}

function findResults(array $formValues, EntityManagerInterface $em, ExerciseFocusedPlugin $plugin)
{
    $cId = api_get_course_int_id();
    $sId = api_get_session_id();

    $qb = $em->createQueryBuilder();
    $qb
        ->select('te AS exe, q.title, te.startDate , u.firstname, u.lastname, u.username')
        ->from(TrackEExercises::class, 'te')
        ->innerJoin(CQuiz::class, 'q', Join::WITH, 'te.exeExoId = q.iid')
        ->innerJoin(User::class, 'u', Join::WITH, 'te.exeUserId = u.id');

    $params = [];

    if ($cId) {
        $qb->andWhere($qb->expr()->eq('te.cId', ':cId'));

        $params['cId'] = $cId;

        $sessionItemIdList = $sId ? [$sId] : [];
    } else {
        $sessionItemIdList = getSessionIdFromFormValues(
            $formValues,
            $plugin->getSessionFieldList()
        );
    }

    if ($sessionItemIdList) {
        $qb->andWhere($qb->expr()->in('te.sessionId', ':sessionItemIdList'));

        $params['sessionItemIdList'] = $sessionItemIdList;
    }

    if (!empty($formValues['username'])) {
        $qb->andWhere($qb->expr()->eq('u.username', ':username'));

        $params['username'] = $formValues['username'];
    }

    if (!empty($formValues['firstname'])) {
        $qb->andWhere($qb->expr()->like ('u.firstname', ':firstname'));

        $params['firstname'] = $formValues['firstname'].'%';
    }

    if (!empty($formValues['lastname'])) {
        $qb->andWhere($qb->expr()->like('u.lastname', ':lastname'));

        $params['lastname'] = $formValues['lastname'].'%';
    }

    if (!empty($formValues['start_date'])) {
        $qb->andWhere(
            $qb->expr()->andX(
                $qb->expr()->gte('te.startDate', ':start_date'),
                $qb->expr()->lte('te.exeDate', ':end_date')
            )
        );

        $params['start_date'] = api_get_utc_datetime($formValues['start_date'].' 00:00:00', false, true);
        $params['end_date'] = api_get_utc_datetime($formValues['start_date'].' 23:59:59', false, true);
    }

    if (empty($params)) {
        return [];
    }

    if ($cId && !empty($formValues['id'])) {
        $qb->andWhere($qb->expr()->eq('q.iid', ':q_id'));

        $params['q_id'] = $formValues['id'];
    }

    $qb->setParameters($params);

    $query = $qb->getQuery();

    return $query->getResult();
}

function getSnapshotListForLevel(int $level, TrackEExercises $trackExe): string
{
    $monitoringPluginIsEnabled = ExerciseMonitoringPlugin::create()->isEnabled(true);

    if (!$monitoringPluginIsEnabled) {
        return '';
    }

    $user = api_get_user_entity($trackExe->getExeUserId());
    $monitoringLogRepository = Database::getManager()->getRepository(MonitoringLog::class);

    $monitoringLogsByQuestion = $monitoringLogRepository->findByLevelAndExe($level, $trackExe);
    $snapshotList = [];

    /** @var MonitoringLog $logByQuestion */
    foreach ($monitoringLogsByQuestion as $logByQuestion) {
        $snapshotUrl = ExerciseMonitoringPlugin::generateSnapshotUrl(
            $user->getId(),
            $logByQuestion->getImageFilename()
        );
        $snapshotList[] = api_get_local_time($logByQuestion->getCreatedAt()).' '.$snapshotUrl;
    }

    return implode(PHP_EOL, $snapshotList);
}

function getAchievedResult(EntityManagerInterface $em, int $exeId): string
{
    $enrollmentInfo = IcpnaPlexConfigPlugin::getEnrollmentByExeId($exeId);

    if ($enrollmentInfo) {
        return $enrollmentInfo['level_reached'];
    }

    $repo = $em->getRepository(CQuizDestinationResult::class);

    $destinationResult = $repo->findOneBy(['exe' => $exeId]);

    if ($destinationResult) {
        return $destinationResult->getAchievedLevel();
    }

    return '';
}

function getOneCategoryPerPageByFocused(TrackEExercises $trackExe): array
{
    $focusedLogRepository = Database::getManager()->getRepository(FocusedLog::class);

    $categorySequence = TestCategory::getCategorySequence($trackExe->getExeId());

    $rows = [];

    foreach ($categorySequence as $details) {
        $score = strip_tags(
            ExerciseLib::show_score($details['score'], $details['weight'])
        );
        $outfocused = $focusedLogRepository->countByActionAndLevel(
            $trackExe,
            FocusedLog::TYPE_OUTFOCUSED,
            $details['category_id']
        );
        $returns = $focusedLogRepository->countByActionAndLevel(
            $trackExe,
            FocusedLog::TYPE_RETURN,
            $details['category_id']
        );

        $rows[] = [
            $details['category_title'],
            api_get_local_time($details['tms']),
            $score,
            $outfocused,
            $returns,
            getSnapshotListForLevel($details['category_id'], $trackExe),
        ];
    }

    return $rows;
}

function getOneCategoryPerPageByMonitoring(Exercise $objExercise, TrackEExercises $trackExe, User $user, ExerciseMonitoringPlugin $monitoringPlugin): array
{
    $em = Database::getManager();
    $monitoringLogRepo = $em->getRepository(MonitoringLog::class);
    $focusedLogRepository = $em->getRepository(FocusedLog::class);

    $monitoringLogs = $monitoringLogRepo->findSnapshots($objExercise, $trackExe);

    $rows = [];

    foreach ($monitoringLogs as $monitoringLog) {
        $score = TestCategory::getCatScoreForExeidForUserid(
            $monitoringLog['level'],
            $trackExe->getExeId(),
            $user->getId()
        );
        $categoryInQuiz = TestCategory::findCategoryInQuiz(
            $trackExe->getExeExoId(),
            $monitoringLog['level'],
            $trackExe->getCId()
        );
        $scoreWeight = $categoryInQuiz
            ? strip_tags(ExerciseLib::show_score($score, $categoryInQuiz->getCountQuestions()))
            : $score;

        $outfocused = $focusedLogRepository->countByActionAndLevel(
            $trackExe,
            FocusedLog::TYPE_OUTFOCUSED,
            $monitoringLog['level']
        );
        $returns = $focusedLogRepository->countByActionAndLevel(
            $trackExe,
            FocusedLog::TYPE_RETURN,
            $monitoringLog['level']
        );

        $snapshotUrl = get_plugin_lang('CameraError', 'ExerciseMonitoringPlugin');

        if (!$monitoringLog['isError'] && !empty($monitoringLog['imageFilename'])) {
            $snapshotUrl = ExerciseMonitoringPlugin::generateSnapshotUrl(
                $user->getId(),
                $monitoringLog['imageFilename']
            );
        }

        $hasLevel = $monitoringLog['level'] > 0;

        $rows[] = [
            $hasLevel ? $monitoringLog['log_level'] : $monitoringPlugin->parseLevel($monitoringLog['level']),
            api_get_local_time($monitoringLog['createdAt']),
            $hasLevel ? $scoreWeight : null,
            $hasLevel ? $outfocused : null,
            $hasLevel ? $returns : null,
            $snapshotUrl,
        ];
    }

    return $rows;
}
