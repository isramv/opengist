<?php

namespace BetterGistsBundle\Repository;

use BetterGistsBundle\Entity\Gist;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

/**
 * GistRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class GistRepository extends EntityRepository
{
  public function countAllGists()
  {
    $dql = 'SELECT COUNT (g) AS theCount
            FROM BetterGistsBundle:Gist g';
    $em = $this->getEntityManager();
    $result = $em->createQuery($dql)->getResult();
    $number_of_gists = intval($result[0]['theCount']);
    return $number_of_gists;
  }

  /**
   * @param int $uid
   * @return array
   */
  public function getGistsByUserId($uid)
  {
    $dql = 'SELECT g AS gist, t, u.username
            FROM BetterGistsBundle:Gist g
            LEFT JOIN g.tags t
            JOIN g.author u
            WHERE u.id = ?1
            ORDER BY g.updated DESC';
    $em = $this->getEntityManager();
    $result = $em->createQuery($dql)
      ->setParameter(1, $uid)
      ->getResult(Query::HYDRATE_ARRAY);
    return $result;
  }

  public function getGistsOrderedByName($firstValue = NULL, $numberOfValues = NULL)
  {
    $dql = 'SELECT g
            FROM BetterGistsBundle:Gist g
            GROUP BY g.id
            ORDER BY g.title';
    $em = $this->getEntityManager();
    if(!is_null($firstValue) && !is_null($numberOfValues)) {
      $results = $em->createQuery($dql)
        ->setMaxResults($numberOfValues)
        ->setFirstResult($firstValue)
        ->getResult();
      return $results;
    } else {
      return $em->createQuery($dql)->getResult();
    }
  }
  public function getGistsOrderedByUpdated($firstValue = NULL, $numberOfValues = NULL)
  {
    $dql = 'SELECT g
            FROM BetterGistsBundle:Gist g
            GROUP BY g.id
            ORDER BY g.updated DESC';
    $em = $this->getEntityManager();
    if(!is_null($firstValue) && !is_null($numberOfValues)) {
      $results = $em->createQuery($dql)
        ->setMaxResults($numberOfValues)
        ->setFirstResult($firstValue)
        ->getResult();
      return $results;
    } else {
      return $em->createQuery($dql)->getResult();
    }
  }

  /**
   * @param int $gist_id
   * @param int $author_id
   * @param bool $as_array
   * @return Gist $gist
   */
  public function getGistById($gist_id, $author_id, $as_array = FALSE)
  {
    $em = $this->getEntityManager();
    $gist_repository = $em->getRepository('BetterGistsBundle:Gist');
    $query = $gist_repository->createQueryBuilder('g');
    $query->select('g, tags')
      ->join('g.author','a')
      ->leftJoin('g.tags', 'tags')
      ->where($query->expr()->andX(
        $query->expr()->eq('a.id', '?1'),
        $query->expr()->eq('g.id', '?2')
      ))
      ->setParameter('2', $gist_id)
      ->setParameter('1', $author_id)
      ->getQuery();

    if($as_array) {
      $gist = $query->getQuery()->getResult(Query::HYDRATE_ARRAY);
    } else if(!$as_array) {
      $gist = $query->getQuery()->getResult(Query::HYDRATE_OBJECT);
    }
    if(isset($gist[0])) {
      return $gist[0];
    }
    // todo fix the no result behaviour.
    // todo return error response.
  }
}
