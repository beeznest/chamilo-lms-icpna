<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo,
    Doctrine\DBAL\Schema\Schema,
    Doctrine\DBAL\Types\Type;

/**
 * Class Version20170607142611
 *
 * Add parent_id column to c_quiz_question
 *
 * @package Application\Migrations\Schema\V111
 */
class Version20170607142611 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $cQuizQuestion = $schema->getTable('c_quiz_question');

        if ($cQuizQuestion->hasColumn('parent_id')) {
            return;
        }

        $cQuizQuestion
            ->addColumn('parent_id', Type::INTEGER)
            ->setDefault(0);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
