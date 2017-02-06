<?php

namespace BetterGistsBundle\DependencyInjection;

use Doctrine\ORM\Query;
use Doctrine\ORM\EntityRepository;

class GistDatatablesPaginator extends  BPaginator
{
  private $user_id;

  private $offset;

  private $page_number = 1;

  public function __construct(EntityRepository $repository, $user_id, $offset = 0, $limit = 15) {
    parent::__construct($repository, $user_id, $offset, $limit);
    $this->user_id = $user_id;
  }

  /**
   * @param $page_number
   */
  public function setPageNumber($page_number)
  {
    $this->page_number = $page_number;
  }

  /**
   *
   */
  public function getUserId()
  {
    return $this->user_id;
  }

  /**
   * @return int
   */
  public function getPageNumber()
  {
    return $this->page_number;
  }

  /**
   * @param $request_params
   */
  public function handleRequestParams($request_params)
  {
    // set the Page Number.
    if(isset($request_params['draw']) && is_numeric($request_params['draw'])) {
      $this->setPageNumber((int) $request_params['draw']);
    }

    // set Order By.
    $order_params = array();
    if(isset($request_params['order'])) {
      $order = $request_params['order'][0];
      $column_key = (int) $order['column'];
      $column_name = $request_params['columns'][$column_key]['name'];
      $order_params = array($column_name => $order['dir']);
    }
    if(count($order_params) > 0) {
      foreach ($order_params as $key => $value) {
        if ($key === 'created' && ($value === 'desc' || $value === 'asc')) {
          $this->setOrderBy(array('x.'.$key => $value));
        } else if ($key === 'updated' && ($value === 'desc' || $value === 'asc')) {
          $this->setOrderBy(array('x.'.$key => $value));
        } else if ($key === 'title' && ($value === 'desc' || $value === 'asc')) {
          $this->setOrderBy(array('x.'.$key => $value));
        }
      }
    }

    // Offset
    $this->setOffset($request_params['start']);

    // Max Results
    $this->setLimit($request_params['length']);

  }

  /**
   * @return array
   */
  public function getResults()
  {
    {
      return array(
        'draw' => $this->getPageNumber(),
        'recordsTotal' => $this->countAllItems(),
        'recordsFiltered' => $this->countAllItems(),
        'items' => $this->queryRepository($this->getOffset(), $this->getLimit())
      );
    }
  }

  /**
   * @param int $offset
   * @param int $limit
   * @return array
   */
  public function queryRepository($offset, $limit)
  {
    $dql = $this->getRepository()
      ->createQueryBuilder('x');

    if(!is_null($this->getUserId())) {
      $dql->join('x.author', 'author', 'WITH', 'author.id = ?2')
        ->setParameter(2, $this->getUserId());
    };

    $dql->setFirstResult($offset)->setMaxResults($limit);

    if(!is_null($this->getOrderBy())) {
      $orderBy = $this->getOrderBy();
      $key = key($orderBy);
      $value = $orderBy[$key];
      $dql->orderBy($key, $value);
    }

    // limit
    $dql->setMaxResults($this->getLimit());

    $results = $dql->getQuery()->getArrayResult(Query::HYDRATE_ARRAY);

    return $results;
  }

}