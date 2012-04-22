<?php

    class Database {
        private static $connection;
        private static $prepared = array();
        private static $quoted   = array();

        /* public Database::connect([Array])
         * returns Resource
         *
         * Connects to the database (if necessary) using the given options. If
         * no options are given, defaults to using $DBCONFIG. Future calls will
         * have no effect unless the database connection closes.
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

        /* public Database::prepared(String)
         * returns Boolean
         *
         * Checks whether a given statement has been prepared through Database
         * already.
         */
        public static function prepared($name) {
            return array_key_exists($name, self::$prepared);
        }

        /* public Database::query(String, [Array], [String])
         * returns Resource
         *
         * Runs a given query. If it is named, then it prepares the statement
         * when it is run the first time, and then executes it. If the statement
         * was already prepared based on the name, then it is just executed.
         * Note that this means the query string is disregarded in future calls
         * which involve the same prepared statement name.
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
                pg_prepare(Database::connect(), $name, $str);
            }

            error_log("Executing query $name");
            return pg_execute(Database::connect(), $name, $params);
        }

        /* public Database::prefetch(String, [Array], [String])
         * returns Array
         *
         * Runs a given query under the same rules as Model->query() (see
         * above). This function, however, pre-processes the results rather than
         * returning the statement Result for processing. This may not always be
         * desired for large resultsets, but for small datasets or just datasets
         * where you always want to do something with all the rows, it may be
         * more desirable.
         */
        public static function prefetch($str, $params = array(), $name = null) {
            $ret = array();
            $r = Database::query($str, $params, $name);

            if ($r === false) {
                trigger_error("Query $str could not be executed: " .
                              pg_last_error(), E_USER_ERROR);
            }

            while ($row = pg_fetch_assoc($r)) {
                $ret[] = $row;
            }

            return $ret;
        }

        /* public Database::prefetch_int(String, [Array], [String])
         * returns Array
         *
         * Runs a query and returns the resultset like Model->prefetch() (see
         * above). This function, however, does not return an Array of
         * associative arrays (hashes), but just an array of regular integer-
         * indexed arrays. This will be faster for large resultsets where the
         * columns are not specified in the query (SELECT *). However, these
         * types of queries should be rare.
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
         * This prepares a single identifier for use in a query and returns it.
         * If you want to return multiple identifiers in a single query, see
         * Database::quote_identifiers() (for which this is merely a wrapper).
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
         * (table/column/function/etc. names). There's ideally a function to do
         * this in the PHP core but it doesn't actually seem to exist. It takes
         * a variable number of arguments and returns the results as an array
         * where the keys are the original values and the values are the quoted
         * versions. Note that sometimes these values will be the same, as the
         * PG function only quotes identifiers when specifically necessary.
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
