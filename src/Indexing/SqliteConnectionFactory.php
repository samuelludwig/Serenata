<?php

namespace Serenata\Indexing;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

/**
 * Creates connections to an SQLite database.
 */
final class SqliteConnectionFactory
{
    /**
     * @return Configuration
     */
    private function getConfiguration(): Configuration
    {
        return new Configuration();
    }

    /**
     * @param string $databaseUri
     *
     * @return Connection
     */
    public function create(string $databaseUri): Connection
    {
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path'   => str_replace('file://', '', $databaseUri),
        ], $this->getConfiguration());

        // Data could become corrupted if the operating system were to crash during synchronization, but this
        // matters very little as we will just reindex the project next time. In the meantime, this majorly reduces
        // hard disk I/O during indexing and increases indexing speed dramatically (dropped from over a minute to a
        // couple of seconds for a very small (!) project).
        $connection->executeQuery('PRAGMA synchronous=OFF');

        // Activate memory-mapped I/O. See also https://www.sqlite.org/mmap.html . In a test case, this halved the
        // time it took to build information about a classlike (from 250 ms to 125 ms). On systems that do not
        // support it, this pragma just does nothing.
        $connection->executeQuery('PRAGMA mmap_size=100000000'); // About 100 MB.

        // Have to be a douche about this as these PRAGMA's seem to reset, even though the connection is not closed.
        $connection->executeQuery('PRAGMA foreign_keys=ON');

        // Use the new Write-Ahead Logging mode, which offers performance benefits for our purposes. See also
        // https://www.sqlite.org/draft/wal.html
        $connection->executeQuery('PRAGMA journal_mode=WAL');

        return $connection;
    }
}
