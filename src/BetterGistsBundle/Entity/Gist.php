<?php

namespace BetterGistsBundle\Entity;

use AppBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use AppBundle\Entity\User as FosUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * Gist
 *
 * @ORM\Table(name="gist")
 * @ORM\Entity(repositoryClass="BetterGistsBundle\Repository\GistRepository")
 */
class Gist
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="text", nullable=true)
     */
    private $body;

    /**
     * @ManyToMany(targetEntity="Tags", inversedBy="gists")
     * @JoinTable(name="tags_gists")
     */
    private $tags;

    /**
     * @ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="gists")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $author;

    /**
     * Gist constructor.
     */
    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function getTags()
    {
        return $this->tags ?: $this->tags = new ArrayCollection();
    }

    public function setTags(Tags $tag)
    {
        $this->tags []= $tag;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Gist
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set body
     *
     * @param string $body
     * @return Gist
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string 
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get Author
     *
     * @return User
     *
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set Author
     *
     * @param User
     * @return User
     */
    public function setAuthor(User $author)
    {
        $this->author = $author;

        return $this;
    }
}
