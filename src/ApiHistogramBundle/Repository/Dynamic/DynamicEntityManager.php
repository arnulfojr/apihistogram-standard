<?php

namespace ApiHistogramBundle\Repository\Dynamic;

use ApiHistogramBundle\Container\Configuration\ConfigurationInterface;
use ApiHistogramBundle\Container\Configuration\SiteCapsuleInterface;
use ApiHistogramBundle\Exception\ExceptionParameters;
use ApiHistogramBundle\Exception\Repository\DoctrineException;
use ApiHistogramBundle\Exception\Repository\RepositoryException;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use \InvalidArgumentException;

/**
 * Class DynamicEntityManager
 * @package ApiHistogramBundle\Repository\Dynamic
 */
class DynamicEntityManager
{
    /** @var EntityManagerInterface $_em */
    private $_em;
    /** @var Registry $doctrine */
    protected $doctrine;

    /**
     * DynamicEntityManager constructor.
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param EntityManagerInterface $em
     * @return DynamicEntityManager $this
     */
    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->_em = $em;
        return $this;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->_em;
    }

    /**
     * @return Connection
     * @throws RepositoryException
     */
    public function getConnection()
    {
        if (is_null($this->_em))
        {
            throw new RepositoryException(
                ExceptionParameters::getEntityManagerNotSetMessage("The Entity Manager is not set."),
                ExceptionParameters::ENTITY_MANAGER_NOT_SET_CODE,
                NULL
            );
        }
        $con = $this->_em->getConnection();
        $this->pingConnection($con);
        return $con;
    }

    /**
     * @param Connection $connection
     * @return Connection
     */
    protected function pingConnection(Connection $connection)
    {
        if ($connection->ping() === false)
        {
            $connection->close();
            $connection->connect();
        }
        return $connection;
    }

    /**
     * @param string $connectionName
     * @throws DoctrineException
     */
    public function setUp($connectionName = 'default')
    {
        try
        {
            $this->_em = $this->doctrine->getManager($connectionName);
        }
        catch (InvalidArgumentException $e)
        {
            throw new DoctrineException(
                ExceptionParameters::DOCTRINE_ENTITY_MANAGER_INVALID_MESSAGE,
                ExceptionParameters::DOCTRINE_ENTITY_MANAGER_INVALID_CODE,
                $e
            );
        }
    }

    /**
     * @param SiteCapsuleInterface $capsule
     * @param ConfigurationInterface $configuration
     * @return string
     */
    protected function getTableExpression(SiteCapsuleInterface $capsule, ConfigurationInterface $configuration)
    {
        return "{$configuration->getSchemaName()}.{$capsule->getTableName()}";
    }

}