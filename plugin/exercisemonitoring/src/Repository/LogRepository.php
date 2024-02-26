<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseMonitoring\Repository;

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Exercise;

class LogRepository extends EntityRepository
{
    public function findByLevelAndExe(int $level, TrackEExercises $exe): array
    {
        return $this->findBy(
            [
                'level' => $level,
                'exe' => $exe,
            ],
            ['createdAt' => 'ASC']
        );
    }

    public function findSnapshots(Exercise $objExercise, TrackEExercises $trackExe)
    {
        $qb = $this->createQueryBuilder('l');

        $qb->select(['l.level', 'l.imageFilename', 'l.createdAt', 'l.isError']);

        if (ONE_PER_PAGE == $objExercise->selectType()) {
            $qb
                ->addSelect(['qq.question AS log_level'])
                ->leftJoin(CQuizQuestion::class, 'qq', Join::WITH, 'l.level = qq.iid');
        } elseif (ONE_CATEGORY_PER_PAGE == $objExercise->selectType()) {
            $qb
                ->addSelect(['qqc.title AS log_level'])
                ->leftJoin(CQuizQuestionCategory::class, 'qqc', Join::WITH, 'l.level = qqc.iid');
        }

        $query = $qb
            ->andWhere(
                $qb->expr()->eq('l.exe', $trackExe->getExeId())
            )
            ->addOrderBy('l.createdAt', 'ASC')
            ->addOrderBy('l.level', 'DESC')
            ->getQuery();

        return $query->getResult();
    }
}
