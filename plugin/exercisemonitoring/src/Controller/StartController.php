<?php

/* For license terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\PluginBundle\ExerciseMonitoring\Entity\Log;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class StartController
{
    private $plugin;
    private $request;
    private $em;

    public function __construct(ExerciseMonitoringPlugin $plugin, HttpRequest $request, EntityManager $em)
    {
        $this->plugin = $plugin;
        $this->request = $request;
        $this->em = $em;
    }

    public function __invoke(): HttpResponse
    {
        $userDirName = $this->createDirectory();

        /** @var UploadedFile $imgIddoc */
        $imgIddoc = $this->request->files->get('iddoc');
        /** @var UploadedFile $imgLearner */
        $imgLearner = $this->request->files->get('learner');

        $isSkippedIddoc = $this->request->request->getBoolean('is_skipped_iddoc');
        $isSkippedLearner = $this->request->request->getBoolean('is_skipped_learner');

        $exercise = $this->em->find(CQuiz::class, $this->request->request->getInt('exercise_id'));

        $fileNamesToUpdate = [];

        $logImgIdDoc = new Log();
        $logImgIdDoc
            ->setExercise($exercise)
            ->setLevel(-2)
        ;

        if ($imgIddoc) {
            $newFilename = uniqid().'_iddoc.jpg';

            $imgIddoc->move($userDirName, $newFilename);

            $logImgIdDoc->setImageFilename($newFilename);
        } else {
            $logImgIdDoc->setIsError(!$isSkippedIddoc);
        }

        $this->em->persist($logImgIdDoc);

        $logImgLearner = new Log();
        $logImgLearner
            ->setExercise($exercise)
            ->setLevel(-1)
        ;

        if ($imgLearner) {
            $newFilename = uniqid().'_learner.jpg';

            $imgLearner->move($userDirName, $newFilename);

            $logImgLearner->setImageFilename($newFilename);
        } else {
            $logImgLearner->setIsError(!$isSkippedLearner);
        }

        $this->em->persist($logImgLearner);

        $this->em->flush();

        $fileNamesToUpdate[] = $logImgIdDoc->getId();
        $fileNamesToUpdate[] = $logImgLearner->getId();

        ChamiloSession::write($this->plugin->get_name().'_orphan_snapshots', $fileNamesToUpdate);

        return HttpResponse::create();
    }

    private function createDirectory(): string
    {
        $user = api_get_user_entity(api_get_user_id());

        $pluginDirName = api_get_path(SYS_UPLOAD_PATH).'plugins/exercisemonitoring';
        $userDirName = $pluginDirName.'/'.$user->getId();

        $fs = new Filesystem();
        $fs->mkdir(
            [$pluginDirName, $userDirName],
            api_get_permissions_for_new_directories()
        );

        return $userDirName;
    }
}
