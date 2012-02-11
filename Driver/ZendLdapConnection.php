<?php

namespace FR3D\LdapBundle\Driver;

use Zend\Ldap\Ldap;
use Zend\Ldap\Exception;
use FR3D\LdapBundle\Model\LdapUserInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * This class adapt ldap calls to Zend Framwork Ldap library functions.
 * Also prevent information disclosure catching Zend Ldap Exceptions and passing
 * them to the logger.
 *
 * @since v2.0.0
 */
class ZendLdapConnection implements LdapConnectionInterface
{
    /**
     * @var Ldap $connection
     */
    private $connection;

    /**
     * @var LoggerInterface $logger
     */
    private $logger;

    /**
     * @param Ldap            $connection Initializated Zend::Ldap Object
     * @param LoggerInterface $logger     Optional logger for write debug messages.
     */
    public function __construct(Ldap $connection, LoggerInterface $logger = null)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function search($baseDn, $filter, array $attributes = array())
    {
        $this->logDebug(sprintf('ldap_search(%s, %s, %s)', $baseDn, $filter, $attributes));

        try {
            $entries          = $this->connection->searchEntries($filter, $baseDn, Ldap::SEARCH_SCOPE_SUB, $attributes);
            // searchEntries don't return 'count' key as specified by php native
            // function ldap_get_entries()
            $entries['count'] = count($entries);
        } catch (\Zend\Ldap\Exception $exception) {
            $this->zendExceptionHandler($exception);

            throw new LdapConnectionException('An error occur with the search operation.');
        }

        return $entries;
    }

    /**
     * {@inheritDoc}
     */
    public function bind(UserInterface $user, $password = null)
    {
        if ($user instanceof LdapUserInterface && $user->getDn()) {
            $bind_rdn = $user->getDn();
        } else {
            $bind_rdn = $user->getUsername();
        }

        try {
            $this->logDebug(sprintf('ldap_bind(%s, ****)', $bind_rdn));
            $bind = $this->connection->bind($bind_rdn, $password);

            return ($bind instanceof Ldap);
        } catch (\Zend\Ldap\Exception $exception) {
            $this->zendExceptionHandler($exception);

            return false;
        }

        return false;
    }

    /**
     * Treat a Zend Ldap Exception
     * 
     * @param \Zend\Ldap\Exception $exception
     */
    protected function zendExceptionHandler(\Zend\Ldap\Exception $exception)
    {
        switch ($exception->getCode()) {
            // Error level codes
            case Exception::LDAP_SERVER_DOWN:
                if ($this->logger) {
                    $this->logger->err($exception->getMessage());
                }
                break;

            // Other level codes
            default:
                $this->logDebug($exception->getMessage());
                break;
        }
    }

    /**
     * Log debug messages if the logger is set.
     * 
     * @param string $message
     */
    private function logDebug($message)
    {
        if (!$this->logger) {
            return;
        }

        $this->logger->debug($message);
    }
}