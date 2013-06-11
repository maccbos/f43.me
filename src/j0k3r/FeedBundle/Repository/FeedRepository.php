<?php

namespace j0k3r\FeedBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * FeedRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class FeedRepository extends DocumentRepository
{
    /**
     * Find feeds ordered by updated date
     *
     * @param  integer   $limit Items to retrieve
     *
     * @return Doctrine\ODM\MongoDB\EagerCursor
     */
    public function findAllOrderedByDate($limit = null)
    {
        $q = $this->createQueryBuilder()
            ->eagerCursor(true)
            ->sort('updated_at', 'DESC');

        if (null !== $limit) {
            $q->limit($limit);
        }

        return $q->getQuery()->execute();
    }

    /**
     * Find feeds that doesn't have items
     * BUT, this doesn't seems to work ...
     *
     * @return Doctrine\ODM\MongoDB\EagerCursor
     */
    public function findAllNew()
    {
        return $this->createQueryBuilder()
            ->field('feeditems')->size(0)
            ->eagerCursor(true)
            ->getQuery()
            ->execute();
    }

    /**
     * Find feed by ids.
     * Used in FetchItemCommand to retrieve feed that have / or not items
     *
     * @param  Array    $ids   An array of MongoID
     * @param  string   $type  in or notIn
     *
     * @return Doctrine\ODM\MongoDB\EagerCursor
     */
    public function findByIds($ids, $type = 'in')
    {
        $q = $this->createQueryBuilder()
            ->field('id');

        switch ($type)
        {
            case 'in':
                $q->in($ids);
                break;

            case 'notIn':
                $q->notIn($ids);
                break;

            default:
                return false;
        }

        return $q->eagerCursor(true)
            ->getQuery()
            ->execute();
    }
}