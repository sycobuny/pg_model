<?php

    class Database {
        private static $connection;
        private static $prepared = Array();

        /* public Database::connect([Array])
         * returns Resource
         *
         * Connects to the database (if necessary) using the given options. If
         * no options are given, defaults to using $DBCONFIG. Future calls will
         * have no effect unless the database connection closes.
         */
        public static function connect($params = NULL) {
            global $DBCONFIG;
            static $DB;

            if (!$params)
                $params = $DBCONFIG;

            if (self::$connection)
                return self::$connection;

            $connect_ary = Array();
            foreach ($params as $key => $value) {
                $value = preg_replace('/( |\\\\)/', '\\\\\1', $value);

                if (preg_match('/ /', $value))
                    $value = "'$value'";

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
        public static function query($str, $params = Array(), $name = NULL) {
            $p =& self::$prepared;

            if (($name && !array_key_exists($name, $p)) || !$name) {
                if ($name)
                    $p[$name] = true;
                else
                    $name = '';

                error_log("Preparing query ($name): $str");
                pg_prepare(Database::connect(), $name, $str);
            }

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
        public static function prefetch($str, $params = Array(), $name = NULL) {
            $ret = Array();
            $r = Database::query($str, $params, $name);

            while ($row = pg_fetch_assoc($r))
                $ret[] = $row;

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
        public static function prefetch_int($str, $params = Array(),
                                            $name = NULL) {
            $ret = Array();
            $r = Database::query($str, $params, $name);

            while ($row = pg_fetch_row($r))
                $ret[] = $row;

            return $ret;
        }
    }

?>
