<?php

    class Database {
        private static $connection;
        private static $prepared = array();
        private static $quoted   = array();

        /**
         * Connect to a database
         *
         * Will connect to the database if a connection does not yet exist. If
         * one does exist, that is returned (regardless of arguments passed).
         * If there's a global $DBCONFIG variable, then that can be used
         * instead of passing connection paramters. If parameters are passed,
         * they will supercede the $DBCONFIG variable.
         *
         * @param string $params The connection parameters
         * @throws InvalidArgumentException
         * @return resource
         */
        public static function connect($params = null) {
            global $DBCONFIG;

            if (self::$connection) {
                return self::$connection;
            }

            if (!$params) {
                $params = $DBCONFIG;
            }

            if (!isset($params)) {
                $msg = "No parameters were passed and \$DBCONFIG is empty";
                throw new InvalidArgumentException($msg);
            }

            $connect_ary = array();
            foreach ($params as $key => $value) {
                $value = preg_replace('/( |\\\\)/', '\\\\\1', $value);

                if (preg_match('/ /', $value)) {
                    $value = "'$value'";
                }

                $connect_ary[] = "$key=$value";
            }

            self::$connection = pg_connect(join(' ', $connect_ary));
            return self::$connection;
        }

        /**
         * Check whether a given statement name has been prepared.
         *
         * @param string $name The name of the prepared statement
         * @return boolean
         */
        public static function prepared($name) {
            return array_key_exists($name, self::$prepared);
        }

        /**
         * Execute a query and return the result
         *
         * Executes a query with optional parameters (which are automatically
         * escaped). If a name is provided, then the query will be prepared
         * and saved if necessary (or simply executed if it was already
         * prepared). Unnamed queries are prepared and executed each time they
         * are called. Note that the second and later times a named statement
         * is called, the query string is disregarded (as the statement is
         * already prepared). There is no way to reallocate a name for a
         * query.
         *
         * @param string $str The query string to execute
         * @param array $params The query parameters
         * @param string $name The name of the query
         * @throws DatabaseException
         * @return resource
         */
        public static function query($str, $params = array(), $name = null) {
            $p =& self::$prepared;

            if (($name && !array_key_exists($name, $p)) || !$name) {
                if ($name) {
                    $p[$name] = true;
                }
                else {
                    $name = '';
                }

                error_log("Preparing query ($name): $str");
                $prepped = pg_prepare(Database::connect(), $name, $str);

                if ($prepped === false) {
                    $pgerr = pg_last_error();

                    if ($name) {
                        $msg = "Could not prepare query $name: $pgerr";
                    }
                    else {
                        $msg = "Could not prepare query: $pgerr";
                    }

                    throw new DatabaseException($msg);
                }
            }

            error_log("Executing query $name");
            $resource = pg_execute(Database::connect(), $name, $params);

            if ($resource === false) {
                $pgerr = pg_last_error();

                if ($name) {
                    $msg = "Could not execute query $name: $pgerr";
                }
                else {
                    $msg = "Could not execute query: $pgerr";
                }

                throw new DatabaseException($msg);
            }

            return $resource;
        }

        /**
         * Execute a query and fetch all results
         *
         * This function pre-loads all results from a query into an array and
         * returns it. Note that this is not always memory-efficient to do,
         * and if you're not going to use all of the results, it may not be
         * useful at all. All of the same caveats from Database::query()
         * apply. The individual result rows are associative arrays.
         *
         * @param string $str The query string
         * @param array $params The query parameters
         * @param string $name The name of the query
         * @throws DatabaseException
         * @return array
         */
        public static function prefetch($str, $params = array(),
                                        $name = null) {
            $ret      = array();
            $resource = Database::query($str, $params, $name);

            while ($row = pg_fetch_assoc($resource)) {
                $ret[] = $row;
            }

            return $ret;
        }

        /**
         * Execute a query and fetch all results
         *
         * This function performs the same function as Database::prefetch(),
         * but returns an array of regular integer-indexed arrays. It is
         * faster for cases when the names of the columns are not known to the
         * query planner in advance of their execution (eg: 'SELECT * FROM
         * table').
         *
         * @param string $str The query string
         * @param array $params The query paramters
         * @param string $name The query name
         * @throws DatabaseException
         * @return array
         */
        public static function prefetch_int($str, $params = array(),
                                            $name = null) {
            $ret = array();
            $r = Database::query($str, $params, $name);

            while ($row = pg_fetch_row($r)) {
                $ret[] = $row;
            }

            return $ret;
        }

        /**
         * Prepare an identifier for safe use in a query
         *
         * This prepares a single identifier for use in a query and returns
         * it. If you want to return multiple identifiers in a single query,
         * see Database::quote_identifiers() (for which this is merely a
         * wrapper).
         *
         * @param string $ident The identifier to quote
         * @return string
         */
        public static function quote_identifier($ident) {
            $out = self::quote_identifiers($ident);
            return $out[$ident];
        }

        /**
         * Prepare identifiers for safe use in queries
         *
         * Call a PostgreSQL server function to quote identifiers
         * (table/column/function/etc. names). There's ideally a function to
         * do this in the PHP core but it doesn't actually seem to exist. It
         * takes a variable number of arguments and returns the results as an
         * array where the keys are the original values and the values are the
         * quoted versions. Note that sometimes these values will be the same,
         * as the PG function only quotes identifiers when specifically
         * necessary.
         *
         * @return array
         */
        public static function quote_identifiers() {
            $ret   = array();
            $holds = array();
            $args  = array();

            $in = func_get_args();
            $fargs = array();

            foreach ($in as $arg) {
                if (is_array($arg)) {
                    $fargs = array_merge($fargs, $arg);
                }
                else {
                    array_push($fargs, $arg);
                }
            }

            foreach ($fargs as $arg) {
                if (array_key_exists($arg, self::$quoted)) {
                    $ret[$arg] = self::$quoted[$arg];
                }
                else {
                    array_push($holds, '$' . (count($holds) + 1));
                    array_push($args, $arg);
                }
            }

            // we already found all args cached!
            if (!count($args)) {
                return $ret;
            }

            $unnest = 'ARRAY[' . join(', ', $holds) . ']';
            $query  = 'SELECT u.i, pg_catalog.quote_ident(u.i) q FROM ' .
                      "UNNEST($unnest) u(i)";
            $name   = '_quote_identifiers_' . count($args);
            $rows   = self::prefetch($query, $args, $name);

            foreach ($rows as $row) {
                $key = $row['i'];
                $val = $row['q'];

                $ret[$key] = $val;
                self::$quoted[$key] = $val;
            }

            return $ret;
        }
    }

?>
