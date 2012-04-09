<?php

    include('column.php');

    abstract class Model {
        private static $columns_list = Array();
        private static $prepared     = Array();

        private $columns;
        private $clean;
        private $dirty;

        /* new Model()
         * returns Model
         *
         * Preloads column definitions and creates an object where all columns
         * are set to NULL to prevent any "undefined key" errors.
         */
        function __construct() {
            if ($columns =& self::$columns_list[(string) get_class($this)])
                $this->columns =& $columns;
            else {
                $ary = $this->_colquery();

                self::$columns_list[(string) get_class($this)] = Array();
                $columns =& self::$columns_list[(string) get_class($this)];
                $this->columns =& $columns;

                foreach ($ary as $x => $row) {
                    $name = $row['name'];
                    $type = $row['db_type'];
                    $null = $row['allow_null'];
                    $pkey = $row['primary_key'];

                    $null = $null == 't' ? TRUE : FALSE;
                    $pkey = $pkey == 't' ? TRUE : FALSE;

                    $columns[$name] = new Column($name, $type, $null, $pkey);
                }
            }

            $this->_clear();
        }

        /* private Model->_colquery()
         * returns Array
         *
         * Runs a query to fetch column definitions from a table. Returns the
         * dataset of rows of name, db_type, default, allow_null, and
         * primary_key for something else to process.
         */
        private function _colquery() {
            if (!array_key_exists('_colquery', self::$prepared))
                $str = <<<COLQUERY
SELECT a.attname                                   AS "name",
       format_type(t.oid, a.atttypmod)             AS db_type,
       pg_get_expr(d.adbin, c.oid)                 AS "default",
       NOT a.attnotnull                            AS allow_null,
       COALESCE((a.attnum = ANY(i.indkey)), false) AS primary_key
  FROM ((((pg_class c INNER JOIN pg_attribute a ON a.attrelid = c.oid)
             INNER JOIN pg_type t ON t.oid = a.atttypid
           ) INNER JOIN pg_namespace n ON n.oid = c.relnamespace
         ) LEFT OUTER JOIN
         pg_attrdef d ON (d.adrelid = c.oid AND d.adnum = a.attnum)
       ) LEFT OUTER JOIN
       pg_index i ON (i.indrelid = c.oid AND i.indisprimary)
  WHERE (NOT a.attisdropped) AND
        a.attnum > 0         AND
        c.relname = $1       AND
        n.nspname !~* 'pg_*|information_schema'
  ORDER BY a.attnum;
COLQUERY;
            else
                $str = '';

            $str = preg_replace('/(\n|\s)+/m', ' ', $str);
            return $this->prefetch($str, Array($this->table()), '_colquery');
        }

        /* public Model->query(String, [Array], [String])
         * returns Resource
         *
         * Runs a given query. If it is named, then it prepares the statement
         * when it is run the first time, and then executes it. If the statement
         * was already prepared based on the name, then it is just executed.
         * Note that this means the query string is disregarded in future calls
         * which involve the same prepared statement name.
         */
        public function query($str, $params = Array(), $name = NULL) {
            $p =& self::$prepared;

            if (($name && !array_key_exists($name, $p)) || !$name) {
                if ($name)
                    $p[$name] = true;
                else
                    $name = '';

                error_log("Preparing query: $str");
                pg_prepare(DB(), $name, $str);
            }

            return pg_execute(DB(), $name, $params);
        }

        /* public Model->prefetch(String, [Array], [String])
         * returns Array
         *
         * Runs a given query under the same rules as Model->query() (see
         * above). This function, however, pre-processes the results rather than
         * returning the statement Result for processing. This may not always be
         * desired for large resultsets, but for small datasets or just datasets
         * where you always want to do something with all the rows, it may be
         * more desirable.
         */
        public function prefetch($str, $params = Array(), $name = NULL) {
            $ret = Array();
            $r = $this->query($str, $params, $name);

            while ($row = pg_fetch_assoc($r))
                $ret[] = $row;

            return $ret;
        }

        /* public Model->prefetch_int(String, [Array], [String])
         * returns Array
         *
         * Runs a query and returns the resultset like Model->prefetch() (see
         * above). This function, however, does not return an Array of
         * associative arrays (hashes), but just an array of regular integer-
         * indexed arrays. This will be faster for large resultsets where the
         * columns are not specified in the query (SELECT *). However, these
         * types of queries should be rare.
         */
        public function prefetch_int($str, $params = Array(), $name = NULL) {
            $ret = Array(); 
            $r = $this->query($str, $params, $name);

            while ($row = pg_fetch_row($r))
                $ret[] = $row;

            return $ret;
        }

        /* public Model->load(Integer)
         * returns $this
         *
         * Pulls data from the database for a given model into the object. Note
         * that this clears any state (modifications/etc.) that have been set
         * on the object first, for any Model-controlled columns.
         */
        public function load($id) {
            $table = $this->table();
            $query = "SELECT * FROM $table WHERE id = $1";
            $name  = '_load_' . ((string) get_class($this));

            $data = $this->prefetch($query, Array($id), $name);
            $this->_set_all($data[0]);

            return $this;
        }

        /* public Model->save()
         * returns $this
         *
         * Saves the data to the database. This method knows whether or not to
         * run an INSERT or an UPDATE operation based on whether primary keys
         * have already been set on this object. It also resets the clean and
         * dirty states to whatever the current values of the table are when the
         * query completes.
         */
        public function save() {
            $pkeys = $this->primary_keys();

            if ($this->clean[ $pkeys[0] ])
                return $this->_update();
            else
                return $this->_insert();
        }

        /* protected Model->_insert()
         * returns $this;
         *
         * Runs an INSERT query against the database, based on the current
         * contents of the "dirty" array.
         */
         protected function _insert() {
             $table = $this->table();
             $cols  = $this->columns();

             $names = Array();
             $holds = Array();
             $vals  = Array();
             $rets  = Array();

             $x = 1;
             foreach ($cols as $name => $col) {
                 array_push($rets, $name);

                 if ($col->primary_key()) continue;
                 $val = $col->prep_for_database($this->column($name));

                 array_push($names, $name);
                 array_push($holds, '$' . $x++);
                 array_push($vals,  $val);
             }

             $names = join(', ', $names);
             $holds = join(', ', $holds);

             $query = "INSERT INTO $table ($names) VALUES ($holds) " .
                      "RETURNING $rets";

             $results = $this->prefetch($query, $vals, "_insert_$table");
             $this->_set_all($results[0]);

             return $this;
         }

        /* protected Model->_set_all(Array)
         * returns $this
         *
         * Sets all of the Model-controlled columns at once, clearing their
         * current values first.
         */
        protected function _set_all($hash) {
            $this->_clear();

            foreach ($hash as $key => $value) {
                if (!array_key_exists($key, $this->columns)) {
                    $table = $this->table();
                    trigger_error("Unknown column $key for $table",
                                  E_USER_ERROR);
                    continue;
                }

                $col = $this->columns[$key];
                $val = $col->process_value($value);

                $this->clean[$key] = $val;
                $this->dirty[$key] = $val;
            }

            return $this;
        }

        /* protected Model->_clear()
         * returns $this
         *
         * Clears the current Model-controlled state of the object (the columns
         * and any modifications therein).
         */
        protected function _clear() {
            $this->clean = Array();
            $this->dirty = Array();

            foreach ($this->columns as $key => $col) {
                $this->clean[$key] = NULL;
                $this->dirty[$key] = NULL;
            }

            return $this;
        }

        /* public Model->columns()
         * returns Array
         *
         * Returns an array of the Column objects for a given model.
         */
        public function columns() {
            return $this->columns;
        }

        /* public Model->column_names()
         * returns Array
         *
         * Returns a sorted array of all column names for a given model.
         */
        public function column_names() {
            $keys = array_keys($this->columns);
            sort($keys);

            return $keys;
        }

        public function primary_keys() {
            $pkeys = Array();

            foreach ($this->columns AS $name => $col)
                if ($col->primary_key())
                    array_push($pkeys, $name);

            return $pkeys;
        }

        /* public Model->column(String)
         * returns Mixed
         *
         * Returns the current value of a particular column (that is, whatever
         * modifications have already been done, not whatever value is stored in
         * the database). If there's no such column, NULL is returned.
         */
        public function column($name) {
            if (!array_key_exists($name, $this->columns))
                return;

            return $this->dirty[$name];
        }

        /* public Model->form(String, [String])
         * returns String
         *
         * Returns a piece of a form element which will fill in the value
         * currently in the model.
         */
        public function form($name, $comparison = NULL, $echo = TRUE) {
            if (!array_key_exists($name, $this->columns))
                return;

            $col = $this->columns[$name];
            $val = $col->formify($this->column($name), $comparison);

            if ($echo)
                echo $val;
            return $val;
        }

        /* public Model->display(String)
         * returns String
         *
         * Returns a sanitized-for-HTML version of the value of a column.
         */
        public function display($name) {
            if (!array_key_exists($name, $this->columns))
                return;

            $col = $this->columns[$name];
            return $col->stringify($this->column($name));
        }

        /* public Model->set_column(String, Mixed)
         * returns Mixed
         *
         * Sets the column specified to the value specified. If there is no such
         * column, NULL is returned. If there is a column, then the processed
         * value is returned. This may not always be the same value that was
         * sent to be set (for instance, a Date provided as a String will return
         * a Date object).
         */
        public function set_column($name, $value) {
            if (!array_key_exists($name, $this->columns))
                return;

            $col   = $this->columns[$name];
            $value = $col->process_value($value);
            $this->dirty[$name] = $value;

            return $value;
        }

        /* public Model->__call(String, Array)
         * returns Mixed
         *
         * Locates suitable handlers for methods which are not defined by hand
         * in PHP files. This allows for making calls to column_name() and
         * set_column_name() without writing out those methods, a tedious
         * process.
         */
        public function __call($name, $args) {
            if (array_key_exists($name, $this->columns))
                return $this->column($name);

            if (preg_match('/^set_([a-z0-9_]+)$/i', $name, $matches))
                if (array_key_exists($matches[1], $this->columns))
                    return $this->set_column($matches[1], $args[0]);

            $class = ((string) get_class($this));
            trigger_error("Method $class::$name does not exist",
                          E_USER_ERROR);
        }

        /* public (abstract) Model->table()
         * returns String
         *
         * A method which must be defined in all child classes, which contains
         * the name of the table associated with the class. It is used for
         * automatically generating queries.
         */
        public abstract function table();
    }

?>
