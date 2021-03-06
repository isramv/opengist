<?php

namespace BetterGistsBundle\DependencyInjection;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class BPaginator
{
  /**
   * @var $offset
   * The number of items to skip on the query.
   */
  private $offset;

  /**
   *
   */
  private $user_id;

  /**
   * @var $limit
   * The number of items to show per page.
   */
  private $limit;

  /**
   * @var EntityRepository $repository
   */
  private $repository;

  /**
   * @var array
   */
  public $orderBy;


  private $page_number;



  /**
   * BPaginator constructor.
   * @param EntityRepository $repository
   * @param integer $user_id
   * @param integer $offset
   * @param integer $limit
   */
  public function __construct(EntityRepository $repository, $user_id, $offset = 0, $limit = 10)
  {
    $this->repository = $repository;
    $this->offset = $offset;
    $this->limit = $limit;
    $this->user_id = $user_id;
  }

  /**
   * @param integer $offset
   */
  public function setOffset($offset) {
    $this->offset = $offset;
  }

  /**
   * @param int $page_number
   */
  public function setPageNumber($page_number) {
    $this->$page_number = $page_number;
  }


  /**
   * @param $uid
   */
  public function setUserId($uid) {
    $this->user_id = $uid;
  }

  /**
   * @param integer $limit
   */
  public function setLimit($limit) {
    $this->limit = $limit;
  }

  /**
   * @param array $orderBy
   */
  public function setOrderBy(array $orderBy) {
    $this->orderBy = $orderBy;
  }

  /**
   * @return integer
   */
  public function getOffset() {
    return $this->offset;
  }

  /**
   * @return integer
   */
  public function getLimit()
  {
    return $this->limit;
  }

  /**
   * @return array
   */
  public function getOrderBy()
  {
    return $this->orderBy;
  }

  /**
   * @return \Doctrine\ORM\EntityRepository
   */
  public function getRepository() {
    return $this->repository;
  }

  /**
   * @param \Doctrine\ORM\EntityRepository $repository
   */
  public function setRepository($repository) {
    $this->repository = $repository;
  }

  public function getPageNumber() {
    return $this->page_number;
  }

  /**
   * Count the items on the repository.
   * requires an author.id to work as it is.
   * @return integer;
   */
  public function countAllItems()
  {

    $repository = $this->getRepository();
    $queryBuilder = $repository->createQueryBuilder('x');
    $queryBuilder->select('COUNT(x.id) AS itemsCount');
    if(!is_null($this->user_id)) {
      $queryBuilder->join('x.author', 'author', 'WITH', 'author.id = ?2')
      ->setParameter(2, $this->user_id);
    }
    $dql = $queryBuilder->getQuery()->getResult();
    $items_count = $dql[0]["itemsCount"];
    return intval($items_count);
  }

  /**
   * Round the number of pages depending on how many items are in the database.
   * @return integer
   */
  public function getNumberOfPages()
  {
    return (integer) ceil($this->countAllItems()/$this->getLimit());
  }

  /**
   * this method binds everything together.
   * @param integer $page_requested
   * @return array
   */
  public function getPage($page_requested)
  {

    $offset = $this->queryOffset($page_requested);
    $limit = $this->getLimit();

    return array(
      'number_of_pages' => $this->getNumberOfPages(),
      'page_requested' => $page_requested,
      'offset' => $offset,
      'limit' => $limit,
      'items' => $this->queryRepository($offset, $limit)
    );
  }

  /**
   * @param integer $page_requested
   * @return integer
   */
  public function queryOffset($page_requested)
  {
    $number_of_pages = $this->getLimit();
    $query_offset = ($page_requested - 1) * $number_of_pages;
    return $query_offset;
  }

  /**
   * @param integer $offset
   * @param integer $limit
   * @return array
   */
  public function queryRepository($offset, $limit)
  {
    $dql = $this->getRepository()
      ->createQueryBuilder('x');

    if(!is_null($this->user_id)) {
      $dql->join('x.author', 'author', 'WITH', 'author.id = ?2')
      ->setParameter(2, $this->user_id);
    };

    $dql->setFirstResult($offset)->setMaxResults($limit);

    if(!is_null($this->orderBy)) {
      $orderBy = 'x.'.$this->orderBy[0];
      $orderByDirection = $this->orderBy[1];
      $dql->addOrderBy($orderBy, $orderByDirection);
    } else if (is_null($this->orderBy)) {
      $dql->addOrderBy('x.updated', 'DESC');
    }

    $results = $dql->getQuery()->getArrayResult(Query::HYDRATE_ARRAY);

    return $results;
  }
}

