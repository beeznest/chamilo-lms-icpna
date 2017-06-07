<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20151221150011
 *
 * Convert c_quiz_question.type to question types for 1.10.x
 *
 * @package Application\Migrations\Schema\V110
 */
class Version20151221150011 extends AbstractMigrationChamilo
{

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("UPDATE c_quiz_question SET type = ".MATCHING_DRAGGABLE." WHERE type = 18");
        $this->addSql("UPDATE c_quiz_question SET type = ".DRAGGABLE." WHERE type = 17");
        $this->addSql("UPDATE c_quiz_question SET type = ".UNIQUE_ANSWER_IMAGE." WHERE type = 16");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("UPDATE c_quiz_question SET type = 16 WHERE type = ".UNIQUE_ANSWER_IMAGE);
        $this->addSql("UPDATE c_quiz_question SET type = 17 WHERE type = ".DRAGGABLE);
        $this->addSql("UPDATE c_quiz_question SET type = 18 WHERE type = ".MATCHING_DRAGGABLE);
    }
}
