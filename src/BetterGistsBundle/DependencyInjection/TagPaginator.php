<?php

namespace BetterGistsBundle\DependencyInjection;

use Doctrine\ORM\Query;

class TagPaginator extends BPaginator {

  public $gist_ids;

  public function setGistIds($array_of_ids)
  {
    $this->gist_ids = $array_of_ids;
  }

  public function getGistIds()
  {
    return $this->gist_ids;
  }

  public function countAllItems()
  {

    $dql = $this->getRepository()->createQueryBuilder('x');

    $dql->select('x.name AS tag_name, x.id AS tag_id')
      ->addSelect('COUNT(x.name) AS number_of_gists ')
      ->join('x.gists','gists')
      ->where($dql->expr()->in('gists.id', '?1'))
      ->setParameter(1, $this->getGistIds())
      ->groupBy('tag_name');

    $result = $dql->getQuery()->getArrayResult();

    return count($result);
  }

  /**
   * @param int $offset
   * @param int $limit
   * @param array $array_of_ids
   * @return array
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

    // order by number of gists.
    $dql->orderBy('number_of_gists', 'DESC');
    // order by name.
    // $dql->orderBy('x.name', 'ASC');

    $dql->getQuery()->getArrayResult();

    // todo implement order by.
    if(!is_null($this->orderBy)) {
      $orderBy = 'x.'.$this->orderBy[0];
      $orderByDirection = $this->orderBy[1];
      $dql->addOrderBy($orderBy, $orderByDirection);
    } else if (is_null($this->orderBy)) {
      $dql->addOrderBy('x.name', 'DESC');
    }

    $results = $dql->getQuery()->getArrayResult();

    return $results;

  }

}