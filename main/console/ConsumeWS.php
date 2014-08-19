<?php
/**
 * Created by PhpStorm.
 * User: fgonzales
 * Date: 09/06/14
 * Time: 02:58 PM
 */

require_once 'main/inc/global.inc.php';
require_once api_get_path(CONFIG_SYS_ROOT_PATH) . 'vendor/autoload.php';

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeWSCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('chamilo:sendTeacherAttendance')
            ->setDescription('This cron sends teachers attedance to ICPNAs WS')
            ->addOption(
                'list',
                null,
                InputOption::VALUE_NONE,
                'If set, you will see the attendance list that will be sent'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $progress = $this->getHelperSet()->get('progress');
        $tableTrackTeacherAttendance = Database::get_main_table(TABLE_TRACK_E_TEACHER_IN_OUT);
        $tableUser = Database::get_main_table(TABLE_MAIN_USER);
        $tableBranchRoom = Database::get_main_table(TABLE_BRANCH_ROOM);
        $tableBranch = Database::get_main_table(TABLE_BRANCH);

        $sql = "SELECT
            u.username,
            br.title as room,
            tck.log_in_course_date,
            tck.log_out_course_date,
            COALESCE(tck.sync, 0) as sync,
            b.id as branch_id,
            tck.id as track_id
            FROM
            $tableTrackTeacherAttendance tck
            INNER JOIN $tableUser u ON u.user_id = tck.user_id
            INNER JOIN $tableBranchRoom br ON br.id = tck.room_id
            INNER JOIN $tableBranch b ON b.id = br.branch_id
            WHERE COALESCE(tck.sync, 0) < 2
            ";

        $result = Database::query($sql);
        $dataTrackInOut = array();
        while ($row = Database::fetch_array($result)) {
            $dataTrackInOut[] = $row;
        }

        /**
         * 0 or NULL => Not sync
         * 1 => IN SYNC
         * 2 => OUT SYNC
         */

        $list = $input->getOption('list');
        if ($list) {
            /* @todo check table helper because it is not defined
            $table = $this->getHelperSet()->get('table');
            $table
                ->setHeaders('User ID', 'Room', 'IN', 'OUT', 'SYNC', 'Branch ID')
                ->setRows($dataTrackInOut);
            $table->render($output);
            */
        }

        $wsUrl = "https://www2.icpna.edu.pe/wsprueba/service.asmx?wsdl";
        $soapClient = new SoapClient($wsUrl);
        $trackInOut = array();
        foreach ($dataTrackInOut as $trackTeacher) {
            $dateTime = $this->explodeDateTime($trackTeacher['log_in_course_date']);
            $trackInOut[] = array(
                'room' => $trackTeacher['room'],
                'user_id' => $trackTeacher['username'],
                'mark' => 'IN',
                'date' => $dateTime['date'],
                'time' => $dateTime['time'],
                'branch' => $trackTeacher['branch_id'],
                'track_id' => $trackTeacher['track_id']
            );

            if (!empty($trackTeacher['log_out_course_date'])) {
                $dateTime = $this->explodeDateTime($trackTeacher['log_out_course_date']);
                $trackInOut[] = array(
                    'room' => $trackTeacher['room'],
                    'user_id' => $trackTeacher['username'],
                    'mark' => 'OUT',
                    'date' => $dateTime['date'],
                    'time' => $dateTime['time'],
                    'branch' => $trackTeacher['branch_id'],
                    'track_id' => $trackTeacher['track_id']
                );
            }
        }

        if (!empty($trackInOut)) {
            $progress->start($output, count($trackInOut));
            foreach ($trackInOut as $track) {
                $parameters = array(
                    'chraula' => $track['room'],
                    'vchcodigorrhh' => $track['user_id'],
                    'chrtipomarcacion' => $track['mark'],
                    'chrfechamarcacion' => $track['date'],
                    'chrhoramarcacion' => $track['time'],
                    'intidsede' => $track['branch']
                );

                $wsResponse = $soapClient->insAsistenciaDocente($parameters);
                if ($wsResponse->insAsistenciaDocenteResult) {
                    $attributes = array(
                        'sync' => ($track['mark'] == 'IN' ? 1 : 2)
                    );
                    $whereCondition = array(
                        'id = ?' => $track['track_id']
                    );
                    $updateResponse = Database::update($tableTrackTeacherAttendance, $attributes, $whereCondition);

                    if ($updateResponse) {
                        $progress->advance();
                    }
                }
            }
            $progress->finish();
        }
    }

    /**
     * This is to split the date and time
     * @param $dateTimeParam
     * @return array
     */
    public function explodeDateTime($dateTimeParam) {
        $dateTime = explode(' ', $dateTimeParam);

        return array(
            'date' => $dateTime[0],
            'time' => $dateTime[1]
        );
    }
}