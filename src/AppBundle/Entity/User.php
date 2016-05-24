<?php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\JoinColumn;
use AppBundle\Entity\SocialGroup as SocialGroup;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 *
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ManyToMany(targetEntity="SocialGroup", inversedBy="users")
     * @JoinTable(name="users_socialgroups")
     */
    protected $groups;

    public function __construct()
    {
        parent::__construct();
        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function addSocialGroup(SocialGroup $sg)
    {
        $this->groups[] = $sg;
    }
}

