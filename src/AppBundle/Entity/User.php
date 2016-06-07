<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\Collection;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\JoinColumn;
use AppBundle\Entity\SocialGroup as SocialGroup;
use \Doctrine\Common\Collections\ArrayCollection;

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
     * @OneToMany(targetEntity="BetterGistsBundle\Entity\Gist", mappedBy="author")
     */
    protected $gists;


    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->gists = new ArrayCollection();

    }

    /**
     * Get the gists that the User has created.
     * @return Collection
     */
    public function getGists()
    {
        return $this->gists;
    }
}

