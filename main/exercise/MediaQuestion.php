<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuizQuestion;

class MediaQuestion extends Question
{
    static $typePicture = 'looknfeel.png';
    static $explanationLangVar = 'MediaQuestion';

    public function __construct()
    {
        parent::__construct();
        $this->type = MEDIA_QUESTION;
    }


    /**
     * function which process the creation of answers
     *
     * @param FormValidator $form
     * @param Exercise      $exercise
     */
    public function processAnswersCreation($form, $exercise)
    {
        $params = $form->getSubmitValues();
        $this->saveMedia($params);
    }

    private function saveMedia($params)
    {
        $courseId = api_get_course_int_id();
        $em = Database::getManager();

        /** @var CQuizQuestion $question */
        $question = isset($params['id'])
            ? $em->find('ChamiloCourseBundle:CQuizQuestion', $params['id'])
            : new CQuizQuestion();

        $question
            ->setCId($courseId)
            ->setQuestion($params['questionName'])
            ->setDescription($params['questionDescription'])
            ->setParentId(0)
            ->setType(MEDIA_QUESTION)
            ->setPosition(0)
            ->setLevel(0);

        $em->persist($question);
        $em->flush();

        $question->setId(
            $question->getIid()
        );

        $em->persist($question);
        $em->flush();
    }

    function createAnswersForm($form)
    {
        $form->addButtonSave(get_lang('Save'), 'submitQuestion');
    }
}
