<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;


/**
 * EntityCBlog
 *
 * @Table(name="c_lp_category")
 * @Entity
 */
class EntityCLpCategory
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="name")
     */
    private $name;

    /**
     * @var integer
     *
     * @Column(name="user_creator", type="integer")
     */
    private $userCreator;

    /**
     * @var integer
     *
     * @Column(name="session_creator", type="integer")
     */
    private $sessionCreator;

    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCBlog
     */
    public function setCId($cId)
    {
        $this->cId = $cId;
        return $this;
    }

    /**
     * Get cId
     *
     * @return integer
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return EntityCBlog
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get blogId
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set blogName
     *
     * @param string $blogName
     * @return EntityCBlog
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get blogName
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set userCreator
     *
     * @param integer idUser
     * @return EntityCLpCategory
     */
    public function setUserCreator($idUser)
    {
        $this->userCreator = $idUser;
        return $this;
    }

    /**
     * Get userCreator
     *
     * @return integer
     */
    public function getUserCreator()
    {
        return $this->userCreator;
    }

    /**
     * Set sessionCreator
     *
     * @param integer idUser
     * @return EntityCLpCategory
     */
    public function setSessionCreator($idSession)
    {
        $this->sessionCreator = $idSession;
        return $this;
    }

    /**
     * Get sessionCreator
     *
     * @return integer
     */
    public function getSessionCreator()
    {
        return $this->sessionCreator;
    }
}
