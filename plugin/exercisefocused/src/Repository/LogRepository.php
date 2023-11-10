<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseFocused\Repository;

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Database;
use Doctrine\ORM\EntityRepository;

class LogRepository extends EntityRepository
{
    public function countByActionInExe(TrackEExercises $exe, string $action): int
    {
        $result = Database::select(
            'count(id) n',
            'plugin_exercisefocused_log',
            [
                'where' => [
                    'exe_id = ? AND action = ?' => [$exe->getExeId(), $action]
                ]
            ],
            'first'
        );

        return (int) $result['n'];
    }

    public function countByActionAndLevel(TrackEExercises $exe, string $action, int $level): int
    {
        $result = Database::select(
            'count(id) n',
            'plugin_exercisefocused_log',
            [
                'where' => [
                    'exe_id = ? AND action = ? AND level = ?' => [$exe->getExeId(), $action, $level]
                ]
            ],
            'first'
        );

        return (int) $result['n'];
    }
}
