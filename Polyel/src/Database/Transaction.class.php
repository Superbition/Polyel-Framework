<?php

namespace Polyel\Database;

use Closure;
use Exception;
use Polyel\Database\Query\QueryBuilder;

class Transaction implements DatabaseInteraction
{
    // The DB connection from the DB Manager used for the entire transaction
    private $connection;

    // Maximum number of attempts that the transaction can re-try for if en error occurs
    private $maxAttempts;

    // Used to dictate when the transaction is in auto or manual mode
    private $manualMode;

    public function __construct(&$connection, $attempts = 1, $manualMode = false)
    {
        $this->connection = $connection;
        $this->maxAttempts = $attempts;
        $this->manualMode = $manualMode;
    }

    public function status()
    {
        return $this->connection['connection']->transactionStatus();
    }

    public function start()
    {
        $this->connection['connection']->startTransaction();
    }

    public function rollBack()
    {
        $this->connection['connection']->rollBackTransaction();
    }

    public function commit()
    {
        $this->connection['connection']->commitTransaction();
    }

    public function run(Closure $callback)
    {
        /*
         * This is the main transaction loop where the callback will attempt to run its statements
         * inside a transaction, if no error is caught, then the transaction will be automatically
         * committed to the database. If an error occurs, then we will re-try until the maximum
         * number of attempts are reached, then the last resort is to re throw the original error.
         */
        for($currentAttempt = 1; $currentAttempt <= $this->maxAttempts; $currentAttempt++)
        {
            if($this->manualMode === false)
            {
                // Begin the transaction before the callback
                $this->start();
            }

            try
            {
                /*
                 * Execute the callback where the query statements are.
                 * We pass in this class because it gives the callback access to the
                 * query builder, raw SQL functions and transactional connection.
                 */
                $callbackResult = $callback($this, $this);
            }
            catch(Exception $exception)
            {
                if($this->manualMode === false)
                {
                    // An error has been caught, let's roll back and try again...
                    $this->rollBack();
                }

                // If the maximum number of attempts have not been reached, we can try again...
                if($currentAttempt < $this->maxAttempts)
                {
                    continue;
                }

                // Else, we re throw the original error
                throw $exception;
            }

            if($this->manualMode === false)
            {
                // Upon successful transaction, we can safely perform a commit
                $this->commit();
            }

            // Finally return the callback result
            return $callbackResult;
        }
    }

    /*
     * Allows the callback to execute its statements within a connection that
     * is in a transactional state. As connections are from a pool, we have to keep a connection for
     * the entire transaction process. The query builder and raw SQL functions will call this execute
     * when inside a transaction, so they are all using the same connection to perform their transaction.
     *
     * Error handling is handled within the main run() function where the callback is called from.
     */
    public function execute($type, $query, $data = null)
    {
        // Preare our query statement and execute with any data that may have been set
        $statement = $this->connection['connection']->prepare($query);
        $statement->execute($data);

        /*
         * An INSERT statement will return the last insert ID
         *
         * A write statement will return the row count or affected rows
         *
         * And finally, any read statements will return a fetchAll result
         */
        if((strpos($query, 'INSERT') === 0))
        {
            $result = $this->connection['connection']->lastInsertId();
        }
        else if($type === 'write')
        {
            $result = $statement->rowCount();
        }
        else
        {
            $result = $statement->fetchAll();
        }

        return $result;
    }

    public function raw($query, $data = null, $type = 'write')
    {
        return $this->execute($type, $query, $data);
    }

    public function select($query, $data = null)
    {
        return $this->raw($query, $data, 'read');
    }

    public function insert($query, $data = null)
    {
        return $this->raw($query, $data, 'write');
    }

    public function update($query, $data = null)
    {
        return $this->raw($query, $data, 'write');
    }

    public function delete($query, $data = null)
    {
        return $this->raw($query, $data, 'write');
    }

    public function table($table)
    {
        /*
         * Creates a new query builder which is setup in a transactional state.
         * Getting access to this transaction instance, so it can perform queries on the
         * same DB connection.
         */
        $transactionalQueryBuilder = new QueryBuilder($this);

        $connectionName = $this->connection['name'];
        $prefix = config("database.connections.$connectionName.prefix");

        // Setup the table FROM cluase
        $transactionalQueryBuilder->from($table, $prefix);

        return $transactionalQueryBuilder;
    }
}