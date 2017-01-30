<?php

namespace BetterGistsBundle\DependencyInjection;

use Doctrine\ORM\Query;

class GistPaginator extends  BPaginator
{
  private $user_id;

  private $offset;

  private $page_number = 1;

  /**
   * @param $page_number
   */
  public function setPageNumber($page_number)
  {
    $this->page_number = $page_number;
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
  public function handleOrderByFromRequestParams($request_params)
  {
    if(count($request_params) > 0) {
      foreach ($request_params as $key => $name) {
        if ($key === 'created' && ($name === 'DESC' || $name === 'ASC')) {
          $this->setOrderBy(array('x.'.$key => $name));
        } else if ($key === 'updated' && ($name === 'DESC' || $name === 'ASC')) {
          $this->setOrderBy(array('x.'.$key => $name));
        } else if ($key === 'title' && ($name === 'DESC' || $name === 'ASC')) {
          $this->setOrderBy(array('x.'.$key => $name));
        }
      }
    }
  }

  /**
   * @param $request_params
   */
  public function handleRequestParams($request_params)
  {
    if(count($request_params) > 0) {
      foreach ($request_params as $key => $value) {
        if ($key === 'created' && ($value === 'DESC' || $value === 'ASC')) {
          $this->setOrderBy(array('x.'.$key => $value));
        } else if ($key === 'updated' && ($value === 'DESC' || $value === 'ASC')) {
          $this->setOrderBy(array('x.'.$key => $value));
        } else if ($key === 'title' && ($value === 'DESC' || $value === 'ASC')) {
          $this->setOrderBy(array('x.'.$key => $value));
        }
        // Pager.
        if($key === 'page' && is_numeric($value)) {
          $this->setPageNumber($value);
        }
      }
    }
  }

  /**
   * @return array
   */
  public function getResults()
  {
    {
      $page_requested = $this->getPageNumber();
      $offset = $this->queryOffset($page_requested);
      $limit = $this->getLimit();

      return array(
        'number_of_pages' => $this->getNumberOfPages(),
        'page_requested' => (int) $page_requested,
        'offset' => $offset,
        'limit' => $limit,
        'items' => $this->queryRepository($offset, $limit)
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

    if(!is_null($this->user_id)) {
      $dql->join('x.author', 'author', 'WITH', 'author.id = ?2')
        ->setParameter(2, $this->user_id);
    };

    $dql->setFirstResult($offset)->setMaxResults($limit);

    if(!is_null($this->getOrderBy())) {
      $orderBy = $this->getOrderBy();
      $key = key($orderBy);
      $value = $orderBy[$key];
      $dql->orderBy($key, $value);
    } else if (is_null($this->getOrderBy())) {
      $dql->orderBy('x.updated', 'DESC');
    }

    $results = $dql->getQuery()->getArrayResult(Query::HYDRATE_ARRAY);

    return $results;
  }

}