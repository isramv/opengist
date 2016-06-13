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
   * @var $limit
   * The number of items to show per page.
   */
  private $limit;

  /**
   * @var EntityRepository $repository
   */
  private $repository;

  /**
   * BPaginator constructor.
   * @param EntityRepository $repository
   * @param integer $offset
   * @param integer $limit
   */
  public function __construct(EntityRepository $repository, $offset = 0, $limit = 10)
  {
    $this->repository = $repository;
    $this->offset = $offset;
    $this->limit = $limit;
  }

  /**
   * @param integer $offset
   */
  public function setOffset($offset) {
    $this->offset = $offset;
  }

  /**
   * @return integer
   */
  public function getOffset() {
    return $this->offset;
  }

  /**
   * @param integer $limit
   */
  public function setLimit($limit) {
    $this->limit = $limit;
  }

  /**
   * @return integer
   */
  public function getLimit()
  {
    return $this->limit;
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

  /**
   * Count the items on the repository.
   * @return integer;
   */
  public function countAllItems()
  {
    $repository = $this->getRepository();
    $queryBuilder = $repository->createQueryBuilder('x');
    $queryBuilder->select('COUNT(x.id) AS itemsCount');
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
   * Returns a PHP Array of values instead of Entity Objects.
   * @param integer $page_requested
   * @return array
   */
  public function getPageArray($page_requested)
  {
    $offset = $this->queryOffset($page_requested);
    $limit = $this->getLimit();

    return array(
      'number_of_pages' => $this->getNumberOfPages(),
      'page_requested' => $page_requested,
      'offset' => $offset,
      'limit' => $limit,
      'items' => $this->queryRepositoryArray($offset, $limit)
    );
  }

  /**
   * @param integer $page_requested
   * @return integer
   */
  private function queryOffset($page_requested)
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
  private function queryRepository($offset, $limit)
  {
    $results = $this->getRepository()
      ->createQueryBuilder('x')
      ->setFirstResult($offset)
      ->setMaxResults($limit)
      ->getQuery()->getResult();
    return $results;
  }

  /**
   * @param integer $offset
   * @param integer $limit
   * @return array
   */
  private function queryRepositoryArray($offset, $limit)
  {
    $results = $this->getRepository()
      ->createQueryBuilder('x')
      ->leftJoin('x.tags','tags')
      ->select('x, tags')
      ->groupBy('x.id')
      ->setFirstResult($offset)
      ->setMaxResults($limit)
      ->getQuery()->getArrayResult(Query::HYDRATE_ARRAY);
    return $results;
  }
}

