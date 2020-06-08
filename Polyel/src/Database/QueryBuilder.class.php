<?php

namespace Polyel\Database;

use Closure;
use DateTimeInterface;
use Polyel\Database\Support\SqlCompile;
use Polyel\Database\Statements\Inserts;
use Polyel\Database\Statements\Updates;
use Polyel\Database\Statements\Deletes;
use Polyel\Database\Statements\Aggregates;
use Polyel\Database\Support\ClosureSupport;

class QueryBuilder
{
    use Inserts;
    use Updates;
    use Deletes;
    use Aggregates;
    use SqlCompile;
    use ClosureSupport;

    private $dbManager;

    // The type of query that will be executed: read or write
    private $type = 'read';

    /*
     * The compile mode used to render the final SQL query
     * 0 = Render the query as normal, used for when the builder is used from DB::table()
     * 1 = Only compile the actual set statements, used when the builder is operated inside a Closure
     */
    private $compileMode;

    private $data = [];

    private $selects;

    private $distinct = false;

    private $from;

    private $joins;

    private $wheres;

    private $groups;

    private $havings;

    private $order;

    private $limit;

    private $offset;

    public function __construct(DatabaseManager $dbManager = null, $compileMode = 0)
    {
        $this->dbManager = $dbManager;
        $this->compileMode = $compileMode;
    }

    public function from($table)
    {
        $this->from = $table;

        return $this;
    }

    public function select($columns = ['*'])
    {
        // Get func args when no array is used
        if(!is_array($columns) || func_num_args() > 1)
        {
            // Either a single argument or multiple...
            $columns = func_get_args();
        }

        // Process the array of selects into a single string
        foreach($columns as $key => $column)
        {
            // If the array is only containing the select all symbol...
            if($column === '*')
            {
                // Return because we want to select everything...
                return $this;
            }

            // Support converting JSON paths in select statements using the -> operator
            if(strpos($column, '->') !== false)
            {
                $column = explode('->', $column);

                $jsonSelectColumn = array_shift($column) . '->>"$.';

                foreach($column as $pathKey => $jsonPath)
                {
                    $jsonSelectColumn .= $jsonPath;

                    if($pathKey < array_key_last($column))
                    {
                        $jsonSelectColumn .= '.';
                    }
                }

                $jsonSelectColumn .= '"';

                $column = $jsonSelectColumn;
            }

            // Add the column to the select statement
            $this->selects .= $column;

            if($key < array_key_last($columns))
            {
                // If the column is not the last one, add the separator
                $this->selects .= ', ';
            }
        }

        return $this;
    }

    public function join($table, $column1, $operator, $column2, $type = 'INNER')
    {
        $join = "$type JOIN " . $table . " ON " . $column1 . " $operator " . $column2;

        $this->joins .= " $join";

        return $this;
    }

    public function leftJoin($table, $column1, $operator, $column2)
    {
        $this->join($table, $column1, $operator, $column2, 'LEFT');

        return $this;
    }

    public function rightJoin($table, $column1, $operator, $column2)
    {
        $this->join($table, $column1, $operator, $column2, 'RIGHT');

        return $this;
    }

    public function crossJoin($table)
    {
        $this->joins .= " CROSS JOIN $table";

        return $this;
    }

    public function where($column, $operator = null, $value = null, $type = ' AND ', $prepareData = true)
    {
        if(is_array($column))
        {
            return $this->wheres($column, $type);
        }

        if($column instanceof Closure)
        {
            $whereClosureQuery = $this->processClosure($column);

            if(exists($this->wheres))
            {
                $this->wheres .= $type;
            }

            $this->wheres .= $whereClosureQuery;

            return $this;
        }

        if($prepareData)
        {
            $where = $column . " $operator " . '?';

            $this->data[] = $value;
        }
        else
        {
            $where = $column . " $operator " . $value;
        }

        if(exists($this->wheres))
        {
            $where = $type . $where;

            $this->wheres .= $where;
        }
        else
        {
            $this->wheres = $where;
        }

        return $this;
    }

    /*
     * Used to process an array of where statements
     */
    private function wheres($wheres, $type)
    {
        foreach($wheres as $where)
        {
            $this->where($where[0], $where[1], $where[2], $type);
        }

        return $this;
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        if(is_array($column))
        {
            return $this->wheres($column, ' OR ');
        }

        return $this->where($column, $operator, $value, ' OR ');
    }

    public function whereBetween($column, $range, $bool = ' AND ', $not = false)
    {
        if($not)
        {
            $not = ' NOT';
        }
        else
        {
            $not = '';
        }

        $whereBetween = $column . $not . " BETWEEN " . '?' . " AND " . '?';

        $this->data[] = $range[0];
        $this->data[] = $range[1];

        if(exists($this->wheres))
        {
            $whereBetween = $bool . $whereBetween;
            $this->wheres .= $whereBetween;
        }
        else
        {
            $this->wheres .= $whereBetween;
        }

        return $this;
    }

    public function orWhereBetween($column, $range)
    {
        return $this->whereBetween($column, $range, ' OR ');
    }

    public function whereNotBetween($column, $range)
    {
        return $this->whereBetween($column, $range, ' AND ', true);
    }

    public function orWhereNotBetween($column, $range)
    {
        return $this->whereBetween($column, $range, ' OR ', true);
    }

    public function whereIn($column, $values, $bool = ' AND ', $not = false)
    {
        if($not)
        {
            $not = ' NOT';
        }
        else
        {
            $not = '';
        }

        $inValues = '';
        foreach($values as $key => $value)
        {
            $inValues .= '?';

            $this->data[] = $value;

            if($key < array_key_last($values))
            {
                $inValues .= ', ';
            }
        }

        $whereIn = $column . $not . ' IN (' . $inValues . ')';

        if(exists($this->wheres))
        {
            $whereIn = $bool . $whereIn;
            $this->wheres .= $whereIn;
        }
        else
        {
            $this->wheres .= $whereIn;
        }

        return $this;
    }

    public function whereNotIn($column, $values)
    {
        return $this->whereIn($column, $values, ' AND ', true);
    }

    public function orWhereIn($column, $values)
    {
        return $this->whereIn($column, $values, ' OR ');
    }

    public function orWhereNotIn($column, $values)
    {
        return $this->whereIn($column, $values, ' OR ', true);
    }

    public function whereNull($column, $bool = ' AND ', $not = false)
    {
        if($not)
        {
            $not = ' NOT ';
        }
        else
        {
            $not = ' ';
        }

        $whereNull = $column . ' IS' . $not . 'NULL';

        if(exists($this->wheres))
        {
            $whereNull = $bool . $whereNull;
            $this->wheres .= $whereNull;
        }
        else
        {
            $this->wheres .= $whereNull;
        }

        return $this;
    }

    public function whereNotNull($column)
    {
        return $this->whereNull($column, ' AND ', true);
    }

    public function orWhereNull($column)
    {
        return $this->whereNull($column, ' OR ');
    }

    public function orWhereNotNull($column)
    {
        return $this->whereNull($column, ' OR ', true);
    }

    private function whereDateTime($column, $operator, $value, $bool = ' AND ', $type = 'DATE')
    {
        if($value instanceof DateTimeInterface && $type === 'DATE')
        {
            $value = $value->format('Y-m-d');
        }
        else if($value instanceof DateTimeInterface && $type === 'TIME')
        {
            $value = $value->format('H:i:s');
        }

        $whereDate = "$type($column)" . " $operator " . '?';

        $this->data[] = $value;

        if(exists($this->wheres))
        {
            $whereDate = $bool . $whereDate;
            $this->wheres .= $whereDate;
        }
        else
        {
            $this->wheres .= $whereDate;
        }

        return $this;
    }

    public function whereDate($column, $operator, $value)
    {
        return $this->whereDateTime($column, $operator, $value);
    }

    public function orWhereDate($column, $operator, $value)
    {
        return $this->whereDateTime($column, $operator, $value, ' OR ');
    }

    public function whereTime($column, $operator, $value)
    {
        return $this->whereDateTime($column, $operator, $value, ' AND ', 'TIME');
    }

    public function orWhereTime($column, $operator, $value)
    {
        return $this->whereDateTime($column, $operator, $value, ' OR ', 'TIME');
    }

    public function whereYear($column, $operator, $value)
    {
        return $this->whereDateTime($column, $operator, $value, ' AND ', 'YEAR');
    }

    public function orWhereYear($column, $operator, $value)
    {
        return $this->whereDateTime($column, $operator, $value, ' OR ', 'YEAR');
    }

    public function whereMonth($column, $operator, $value)
    {
        return $this->whereDateTime($column, $operator, $value, ' AND ', 'MONTH');
    }

    public function orWhereMonth($column, $operator, $value)
    {
        return $this->whereDateTime($column, $operator, $value, ' OR ', 'MONTH');
    }

    public function whereDay($column, $operator, $value)
    {
        return $this->whereDateTime($column, $operator, $value, ' AND ', 'DAY');
    }

    public function orWhereDay($column, $operator, $value)
    {
        return $this->whereDateTime($column, $operator, $value, ' OR ', 'DAY');
    }

    public function whereWeekOfYear($column, $operator, $value)
    {
        return $this->whereDateTime($column, $operator, $value, ' AND ', 'WEEKOFYEAR');
    }

    public function orWhereWeekOfYear($column, $operator, $value)
    {
        return $this->whereDateTime($column, $operator, $value, ' OR ', 'WEEKOFYEAR');
    }

    public function whereDayOfYear($column, $operator, $value)
    {
        return $this->whereDateTime($column, $operator, $value, ' AND ', 'DAYOFYEAR');
    }

    public function orWhereDayOfYear($column, $operator, $value)
    {
        return $this->whereDateTime($column, $operator, $value, ' OR ', 'DAYOFYEAR');
    }

    public function whereColumn($columnOne, $operator, $columnTwo, $bool = ' AND ')
    {
        return $this->where($columnOne, $operator, $columnTwo, ' AND ', false);
    }

    public function orWhereColumn($columnOne, $operator, $columnTwo)
    {
        return $this->where($columnOne, $operator, $columnTwo, ' OR ', false);
    }

    public function whereExists(Closure $callback, $bool = ' AND ', $not = false)
    {
        if($callback instanceof Closure)
        {
            if($not)
            {
                $not = 'NOT ';
            }
            else
            {
                $not = '';
            }

            $whereExistsClosureQuery = $not . 'EXISTS ' . $this->processClosure($callback);

            if(exists($this->wheres))
            {
                $this->wheres .= $bool;
            }

            $this->wheres .= $whereExistsClosureQuery;
        }

        return $this;
    }

    public function orWhereExists(Closure $callback)
    {
        return $this->whereExists($callback, ' OR ');
    }

    public function whereNotExists(Closure $callback)
    {
        return $this->whereExists($callback, ' AND ', true);
    }

    public function orWhereNotExists(Closure $callback)
    {
        return $this->whereExists($callback, ' OR ', true);
    }

    public function whereJson($column, $operator = null, $value = null, $bool = ' AND ')
    {
        $column = explode('->', $column);

        $whereJsonColumn = array_shift($column) . '->>"$.';

        foreach($column as $key => $jsonPath)
        {
            $whereJsonColumn .= $jsonPath;

            if($key < array_key_last($column))
            {
                $whereJsonColumn .= '.';
            }
        }

        $whereJsonColumn .= '"';

        return $this->where($whereJsonColumn, $operator, $value, $bool);
    }

    public function orWhereJson($column, $operator = null, $value = null)
    {
        return $this->whereJson($column, $operator, $value, ' OR ');
    }

    public function groupBy(...$columns)
    {
        foreach($columns as $key => $groupByColumn)
        {
            $this->groups .= $groupByColumn;

            if($key < array_key_last($columns))
            {
                $this->groups .= ', ';
            }
        }

        return $this;
    }

    public function having($column, $operator, $value, $bool = ' AND ')
    {
        $having = $column . " $operator " . '?';

        $this->data[] = $value;

        if(exists($this->havings))
        {
            $having = $bool . $having;
        }

        $this->havings .= $having;

        return $this;
    }

    public function orHaving($column, $operator, $value)
    {
        return $this->having($column, $operator, $value, ' OR ');
    }

    public function havingBetween($column, $values, $bool = ' AND ', $not = false)
    {
        if($not)
        {
            $not = ' NOT ';
        }
        else
        {
            $not = ' ';
        }

        $havingBetween = $column . $not . 'BETWEEN ? AND ?';

        if(exists($this->havings))
        {
            $havingBetween = $bool . $havingBetween;
        }

        $this->data[] = $values[0];
        $this->data[] = $values[1];

        $this->havings .= $havingBetween;

        return $this;
    }

    public function orHavingBetween($column, $values)
    {
        return $this->havingBetween($column, $values, ' OR ');
    }

    public function havingNotBteween($column, $values)
    {
        return $this->havingBetween($column, $values, ' AND ', true);
    }

    public function orHavingNotBteween($column, $values)
    {
        return $this->havingBetween($column, $values, ' OR ', true);
    }

    public function distinct()
    {
        $this->distinct = true;

        return $this;
    }

    public function orderBy($column, $direction = 'ASC')
    {
        if(is_array($column))
        {
            foreach($column as $key => $orderBy)
            {
                if(array_key_exists(1, $orderBy))
                {
                    $direction = $orderBy[1];
                }

                $this->order .= $orderBy[0] . ' ' . $direction;

                $direction = 'ASC';

                if($key < array_key_last($orderBy))
                {
                    $this->order .= ', ';
                }
            }

            return $this;
        }

        $this->order = $column . " $direction";

        return $this;
    }

    public function latest($column)
    {
        return $this->orderBy($column, 'DESC');
    }

    public function oldest($column)
    {
        return $this->orderBy($column, 'ASC');
    }

    public function orderByRandom($seed = '')
    {
        $this->order = "RAND($seed)";

        return $this;
    }

    public function limit($value)
    {
        if($value >= 0)
        {
            $this->limit = $value;
        }

        return $this;
    }

    public function take($value)
    {
        return $this->limit($value);
    }

    public function offset($value)
    {
        $value = max(0, $value);

        $this->offset = $value;

        return $this;
    }

    public function skip($value)
    {
        return $this->offset($value);
    }

    public function first()
    {
        $this->limit = 1;

        $result = $this->get();

        if(array_key_exists(0, $result))
        {
            return $result[0];
        }

        return null;
    }

    public function value($column)
    {
        $result = $this->first();

        if(array_key_exists($column, $result))
        {
            return $result[$column];
        }

        return false;
    }

    public function get($dump = 0)
    {
        $query = $this->compileSql();

        if($dump >= 1)
        {
            echo "\e[30m\e[103mSQL Query:\e[39m\e[49m ";
            var_dump($query);
            echo "\n\n";

            echo "\e[30m\e[103mSQL Data:\e[39m\e[49m ";
            var_dump($this->data);
            echo "\n\n";
        }

        if($dump === 1)
        {
            return;
        }

        if($this->compileMode === 1)
        {
            $queryResult = [];
            $queryResult['query'] = $query;
            $queryResult['data'] = $this->data;

            return $queryResult;
        }

        $result = $this->dbManager->execute($this->type, $query, $this->data);

        return $result;
    }

    public function dd()
    {
        // Used to dump data and die, as in not execute the SQL statement but continue request
        return $this->get(1);
    }

    public function dump()
    {
        // Just dump out the SQL query and data, then execute it
        return $this->get(2);
    }
}