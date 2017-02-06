<?php

namespace BetterGistsBundle\DependencyInjection;

use Doctrine\ORM\EntityRepository;

class TagsDatatablesPaginator extends BPaginator {

  public $gist_ids;

  public function __construct(EntityRepository $repository, $user_id, $limit, $offset = 0) {
    parent::__construct($repository, $user_id, $offset, $limit);
  }

  public function setGistIds($array_of_ids) {
    $this->gist_ids = $array_of_ids;
  }

  public function getGistIds() {
    return $this->gist_ids;
  }

  public function countAllItems() {

    $dql = $this->getRepository()->createQueryBuilder('x');

    $dql->select('x.name AS tag_name, x.id AS tag_id')
      ->addSelect('COUNT(x.name) AS number_of_gists ')
      ->join('x.gists', 'gists')
      ->where($dql->expr()->in('gists.id', '?1'))
      ->setParameter(1, $this->getGistIds())
      ->groupBy('tag_name');

    $result = $dql->getQuery()->getArrayResult();

    return count($result);
  }

  /**
   * @param $request_params
   */
  public function handleRequestParams($request_params)
  {

    // set the Page Number.
    if(isset($request_params['draw']) && is_numeric($request_params['draw'])) {
      $this->setPageNumber($request_params['draw']);
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
        if ($key === 'number_of_gists' && ($value === 'desc' || $value === 'asc')) {
          $this->setOrderBy(array($key => $value));
        } else if ($key === 'tag_name' && ($value === 'desc' || $value === 'asc')) {
          $this->setOrderBy(array($key => $value));
        }
      }
    }
    // Offset
    $this->setOffset((int) $request_params['start']);

    // Max Results
    $this->setLimit((int) $request_params['length']);

  }

  /**
   * @return array
   */
  public function getResults() {

    return array(
      'draw' => $this->getPageNumber(),
      'recordsTotal' => $this->countAllItems(),
      'recordsFiltered' => $this->countAllItems(),
      'items' => $this->queryRepository($this->getOffset(), $this->getLimit())
    );
  }

  /**
   * @param int $offset
   * @param int $limit
   * @return array
   * @internal param array $array_of_ids
   */
  public function queryRepository($offset, $limit) {

    $repo = $this->getRepository();

    $dql = $repo->createQueryBuilder('x');
    $dql->select('x.name AS tag_name, x.id AS tag_id')
      ->addSelect('COUNT(x.name) AS number_of_gists ')
      ->join('x.gists','gists')
      ->where($dql->expr()->in('gists.id', '?1'))
      ->setParameter(1, $this->getGistIds())
      ->groupBy('tag_name');

    $dql->setFirstResult($offset)->setMaxResults($limit);

    if(!is_null($this->getOrderBy())) {
      $orderBy = $this->getOrderBy();
      $key = key($orderBy);
      $value = $orderBy[$key];
      $dql->orderBy($key, $value);
    }

    $results = $dql->getQuery()->getArrayResult();

    return $results;

  }
}
