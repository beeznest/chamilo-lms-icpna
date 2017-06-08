<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20170608162311
 *
 * Update id to iid for c_quiz_question when they are MEDIA_QUESTION type
 *
 * @package Application\Migrations\Schema\V111
 */
class Version20170608162311 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("UPDATE c_quiz_question SET id = iid WHERE type = ".MEDIA_QUESTION);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // TODO: Implement down() method.
    }
}
