<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo,
    Doctrine\DBAL\Schema\Schema,
    Chamilo\CourseBundle\Entity\CQuizQuestion;

/**
 * Class Version20170608150811
 *
 * Update the parent_id fro media question children
 *
 * @package Application\Migrations\Schema\V111
 */
class Version20170608150811 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $em = $this->getEntityManager();
        $questionRepo = $em->getRepository('ChamiloCourseBundle:CQuizQuestion');

        $questions = $em
            ->createQuery('
                SELECT q FROM ChamiloCourseBundle:CQuizQuestion q
                WHERE q.parentId != 0
            ')
            ->getResult();

        /** @var CQuizQuestion $question */
        foreach ($questions as $question) {
            /** @var CQuizQuestion $questionParent */
            $questionParent = $questionRepo->findOneBy([
                'cId' => $question->getCId(),
                'id' => $question->getParentId()
            ]);

            $question->setParentId($questionParent->getIid());
            $em->persist($question);
        }

        $em->flush();
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // TODO: Implement down() method.
    }
}
