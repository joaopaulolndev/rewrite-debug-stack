<?php

namespace Doctrine\DBAL\Logging;

use Doctrine\Common\Collections\ArrayCollection;
use function microtime;

/**
 * Includes executed SQLs in a Debug Stack.
 */
class DebugStack implements SQLLogger
{
    /**
     * Executed SQL queries.
     *
     * @var mixed[][]
     */
    public $queries = [];

    public $distinctedQueries = [];

    /**
     * If Debug Stack is enabled (log queries) or not.
     *
     * @var bool
     */
    public $enabled = true;

    /** @var float|null */
    public $start = null;

    /** @var int */
    public $currentQuery = 0;

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, ?array $params = null, ?array $types = null)
    {
        if (! $this->enabled) {
            return;
        }

        $this->start                          = microtime(true);
        $this->queries[++$this->currentQuery] = ['sql' => $sql, 'params' => $params, 'types' => $types, 'executionMS' => 0];

        if (isset($params[0])) {
            $this->distinctedQueries[$sql]['params'][] = $params[0];
        } else {
            $this->distinctedQueries[$sql]['params'] = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
        if (! $this->enabled) {
            return;
        }

        $this->queries[$this->currentQuery]['executionMS'] = microtime(true) - $this->start;
    }

    public function __destruct()
    {
        $queries = [];

//        foreach ($this->distinctedQueries as $query => $params) {
//            if (is_array($params["params"])) {
//                $query = str_replace(
//                    '= ?',
//                    'in (' . implode(', ', $params["params"]) . ');',
//                    $query
//                );
//            }
//
//            $queries[] = $query;
//        }

        $cInsert = 0;
        $cUpdate = 0;
        $cSelect = 0;
        foreach ($this->queries as $query) {

            $command = substr($query["sql"], 0, 6);

            if ($command === 'INSERT') {
                $cInsert++;
            }
            if ($command === 'UPDATE') {
                $cUpdate++;
            }
            if ($command === 'SELECT') {
                $cSelect++;
            }

            if ($command !== 'SELECT') {
                $sqlInstruction = $query["sql"];
                if (is_array($query["params"])) {
                    foreach ($query["params"] as $param) {
                        $param = $param ?? "null";
                        $sqlInstruction = $this->str_replace_first("?", $param, $sqlInstruction);
                    }
                }
                $queries[] = $sqlInstruction;
            }
        }

        dump($queries);
        dump('INSERT: ' . $cInsert);
        dump('UPDATE: ' . $cUpdate);
        dump('SELECT: ' . $cSelect);
    }

    private function str_replace_first($from, $to, $content)
    {
        $from = '/'.preg_quote($from, '/').'/';

        return preg_replace($from, $to, $content, 1);
    }

}

/*
namespace Doctrine\DBAL\Logging;

use function microtime;

/**
 * Includes executed SQLs in a Debug Stack.
 * /
class DebugStack implements SQLLogger
{
    /**
     * Executed SQL queries.
     *
     * @var mixed[][]
     * /
    public $queries = [];

    /**
     * If Debug Stack is enabled (log queries) or not.
     *
     * @var bool
     * /
    public $enabled = true;

    /** @var float|null * /
    public $start = null;

    /** @var int * /
    public $currentQuery = 0;

    /**
     * {@inheritdoc}
     * /
    public function startQuery($sql, ?array $params = null, ?array $types = null)
    {
        if (! $this->enabled) {
            return;
        }

        $this->start                          = microtime(true);
        $this->queries[++$this->currentQuery] = ['sql' => $sql, 'params' => $params, 'types' => $types, 'executionMS' => 0];
    }

    /**
     * {@inheritdoc}
     * /
    public function stopQuery()
    {
        if (! $this->enabled) {
            return;
        }

        $this->queries[$this->currentQuery]['executionMS'] = microtime(true) - $this->start;
    }
}
*/