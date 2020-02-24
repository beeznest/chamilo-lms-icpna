<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\Popups;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Popup.
 *
 * @package Chamilo\PluginBundle\Entity\Popups
 *
 * @ORM\Table(name="popups_popup")
 * @ORM\Entity()
 */
class Popup
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string")
     */
    private $title;
    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     */
    private $content;
    /**
     * @var array
     *
     * @ORM\Column(name="visible_for", type="json")
     */
    private $visibleFor;
    /**
     * @var string
     *
     * @ORM\Column(name="shown_in", type="string")
     */
    private $shownIn;
    /**
     * @var bool
     *
     * @ORM\Column(name="visible", type="boolean")
     */
    private $visible;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Popup
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return Popup
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return Popup
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getShownIn()
    {
        return $this->shownIn;
    }

    /**
     * @param string $shownIn
     *
     * @return Popup
     */
    public function setShownIn($shownIn)
    {
        $this->shownIn = $shownIn;

        return $this;
    }

    /**
     * @return array
     */
    public function getVisibleFor()
    {
        return $this->visibleFor;
    }

    /**
     * @return string
     */
    public function getStringVisibleFor()
    {
        $status = [];
        $status[COURSEMANAGER] = get_lang('Teacher');
        $status[STUDENT] = get_lang('Learner');
        $status[DRH] = get_lang('Drh');
        $status[SESSIONADMIN] = get_lang('SessionsAdmin');
        $status[STUDENT_BOSS] = get_lang('RoleStudentBoss');
        $status[INVITEE] = get_lang('Invitee');

        $str = [];

        foreach ($this->visibleFor as $value) {
            $str[] = $status[$value];
        }

        return implode('<br>', $str);
    }

    /**
     * @param array $visibleFor
     *
     * @return Popup
     */
    public function setVisibleFor($visibleFor)
    {
        $this->visibleFor = $visibleFor;

        return $this;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @param bool $visible
     *
     * @return Popup
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }
}
