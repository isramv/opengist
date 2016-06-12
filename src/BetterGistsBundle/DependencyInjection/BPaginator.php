<?php

namespace BetterGistsBundle\DependencyInjection;

use Doctrine\ORM\EntityRepository;

class BPaginator
{
  /**
   * @var $offset
   * The number of items to skip on the query.
   */
  private $offset;

  /**
   * @var $number_of_items
   * The number of items to show.
   */
  private $number_of_items_to_show;

  /**
   * @var EntityRepository $repository
   */
  private $repository;

  /**
   * Paginator constructor.
   * @param
   * @param integer $offset
   * @param integer $number_of_items
   */
  public function __construct(EntityRepository $repository, $offset = 0, $number_of_items_to_show = 10)
  {
    $this->repository = $repository;
    $this->offset = $offset;
    $this->number_of_items_to_show = $number_of_items_to_show;
  }

  /**
   * @param mixed $offset
   */
  public function setOffset($offset) {
    $this->offset = $offset;
  }

  /**
   * @return mixed
   */
  public function getOffset() {
    return $this->offset;
  }

  /**
   * @param mixed $number_of_items
   */
  public function setNumberOfItemsToShow($number_of_items) {
    $this->number_of_items_to_show = $number_of_items;
  }

  /**
   * @return mixed
   */
  public function getNumberOfItemsToShow()
  {
    return $this->number_of_items_to_show;
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


  public function getNumberOfPages()
  {
    return (integer) ceil($this->countAllItems()/$this->getNumberOfItemsToShow());
  }

  /**
   * @param integer $number_of_page
   * @return array
   */
  public function getPage($page_requested)
  {
    $offset = $this->queryOffset($page_requested);
    $limit = $this->getNumberOfItemsToShow();

    $paginator = array(
      'number_of_pages' => $this->getNumberOfPages(),
      'page_requested' => $page_requested,
      'offset' => $offset,
      'limit' => $limit,
      'items' => $this->queryRepository($offset, $limit)
    );
    return $paginator;
  }

  public function getPageArray($page_requested)
  {
    $offset = $this->queryOffset($page_requested);
    $limit = $this->getNumberOfItemsToShow();

    $paginator = array(
      'number_of_pages' => $this->getNumberOfPages(),
      'page_requested' => $page_requested,
      'offset' => $offset,
      'limit' => $limit,
      'items' => $this->queryRepositoryArray($offset, $limit)
    );
    return $paginator;
  }

  /**
   * @param $page_requested
   * @return mixed
   */
  private function queryOffset($page_requested)
  {
    $number_of_pages = $this->getNumberOfItemsToShow();
    $query_offset = ($page_requested - 1) * $number_of_pages;
    return $query_offset;
  }

  /**
   * @param $offset
   * @param $items
   * @return array
   */
  private function queryRepository($offset, $items)
  {
    $results = $this->getRepository()
      ->createQueryBuilder('x')
      ->setFirstResult($offset)
      ->setMaxResults($items)
      ->getQuery()->getResult();
    return $results;
  }
  private function queryRepositoryArray($offset, $items)
  {
    $results = $this->getRepository()
      ->createQueryBuilder('x')
      ->setFirstResult($offset)
      ->setMaxResults($items)
      ->getQuery()->getArrayResult();
    return $results;
  }
}
