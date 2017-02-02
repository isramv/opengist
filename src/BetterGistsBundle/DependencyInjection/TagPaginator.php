<?php

namespace BetterGistsBundle\DependencyInjection;

use Doctrine\ORM\Query;
use Doctrine\ORM\EntityRepository;

class TagPaginator extends BPaginator {

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
   * This abstraction makes easier the handle of logic that handles the orderBy.
   * @param $request_params
   */
  public function handleOrderByFromRequestParams($request_params)
  {

    foreach ($request_params as $key => $name) {
      if ($key === 'number_of_gists' && ($name === 'DESC' || $name === 'ASC')) {
        $this->setOrderBy(array($key => $name));
      } else if ($key === 'tag_name' && ($name === 'DESC' || $name === 'ASC')) {
        $this->setOrderBy(array($key => $name));
      }
    }
  }

  /**
   * @param int $offset
   * @param int $limit
   * @return array
   * @internal param array $array_of_ids
   */
  public function queryRepository($offset, $limit) {

    $dql = $this->getRepository()->createQueryBuilder('x');

    $dql->select('x.name AS tag_name, x.id AS tag_id')
      ->addSelect('COUNT(x.name) AS number_of_gists ')
      ->join('x.gists','gists')
      ->where($dql->expr()->in('gists.id', '?1'))
      ->setParameter(1, $this->getGistIds())
      ->groupBy('tag_name');
    $dql->setFirstResult($offset)->setMaxResults($limit);

    // todo implement order by.
    // order by number of gists.
    $dql->orderBy('number_of_gists', 'DESC');
    // order by name.
    // $dql->orderBy('x.name', 'ASC');


    if(!is_null($this->getOrderBy())) {
      dump('not null');
      $orderBy = $this->getOrderBy();
      $key = key($orderBy);
      $value = $orderBy[$key];
      dump($key);
      dump($value);
      $dql->orderBy($key, $value);
    }

    $results = $dql->getQuery()->getArrayResult();

    return $results;

  }

}