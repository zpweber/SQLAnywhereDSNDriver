<?php
namespace ZPweber\DBAL\Drivers\SQLAnywhereDSN;

use Doctrine\DBAL\Driver\AbstractSQLAnywhereDriver;
use Doctrine\DBAL\Driver\SQLAnywhere\SQLAnywhereConnection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\SQLAnywhere\SQLAnywhereException;

/**
 * Custom Doctrine driver to allow connection to SQLAnywhere server through DSN(odbc).
 * This class is an exact duplicate of the SQLAnywhere Driver with the exception of some
 * very minor changes to the Driver::buildDsn method.
 */
class Driver extends AbstractSQLAnywhereDriver{
    /**
     * {@inheritdoc}
     *
     * @throws DBALException If there was a problem establishing the connection.
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        try {
            return new SQLAnywhereConnection(
                $this->buildDsn(
                    $params['host'] ?? null,
                    $params['port'] ?? null,
                    $params['server'] ?? null,
                    $params['dsn'] ?? null,
                    $params['dbname'] ?? null,
                    $username,
                    $password,
                    $driverOptions
                ),
                $params['persistent'] ?? false
            );
        } catch (SQLAnywhereException $e) {
            throw DBALException::driverException($this, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sqlanywheredsn';
    }

    /**
     * Build the connection string for given connection parameters and driver options.
     *
     * @param string  $host          Host address to connect to.
     * @param int     $port          Port to use for the connection (default to SQL Anywhere standard port 2638).
     * @param string  $server        Database server name on the host to connect to.
     *                               SQL Anywhere allows multiple database server instances on the same host,
     *                               therefore specifying the server instance name to use is mandatory.
     * @param string  $dsn           DataSourceName for [ODBC] named data source.
     * @param string  $dbname        Name of the database on the server instance to connect to.
     * @param string  $username      User name to use for connection authentication.
     * @param string  $password      Password to use for connection authentication.
     * @param mixed[] $driverOptions Additional parameters to use for the connection.
     *
     * @return string
     */
    private function buildDsn($host, $port, $server, $dsn, $dbname, $username = null, $password = null, array $driverOptions = [])
    {
        /* If DSN is set, disregard host/port and set "host" to DSN */
        if(! empty($dsn) ){
            $host = 'DSN=' . $dsn;
        }else{
            /* No need to use this driver if not using DSN to connect, but just in case - no BC break */
            $host = $host ?: 'localhost';
            $port = $port ?: 2638;
            $host = 'HOST=' . $host . ':' . $port;
        }
        if (! empty($server)) {
            $server = ';ServerName=' . $server;
        }
        return
            $host .
            $server .
            ';DBN=' . $dbname .
            ';UID=' . $username .
            ';PWD=' . $password .
            ';' . \implode(
                ';',
                \array_map(static function ($key, $value) {
                    return $key . '=' . $value;
                }, \array_keys($driverOptions), $driverOptions)
            );
    }
}
