<?php

    include_once('lib.php');
    include_once('database.php');
    include_once('column.php');

    abstract class Model {
        private static $columns    = array();
        private static $tbl_lookup = array();
        private static $cls_lookup = array();

        private static $associations = array();
        private static $one_to_many  = array();
        private static $many_to_many = array();
        private static $many_to_one  = array();

        private $assoc;
        private $cols;
        private $clean;
        private $dirty;

        /**
         * Model constructor
         *
         * Preloads column definitions and creates an object where all columns
         * are set to NULL to prevent any "undefined key" errors.
         *
         * @return Model
         */
        function __construct() {
            $this->assoc = array();

            $this->cols(); // get the value and discard the result
            $this->_clear();
        }

        /**
         * Associate the Model with a table
         *
         * Links a table to a class and vice versa. This is necessary because
         * PHP 5.1 does not have particularly good class introspection.
         *
         * @param string $table The name of the table to associate
         * @param string $class The class name of the Model
         * @return void
         */
        public static function associate_table($table, $class) {
            self::$tbl_lookup[$class] = $table;
            self::$cls_lookup[$table] = $class;

            return NULL;
        }

        /**
         * Create a one-to-many relationship with another Model
         *
         * Links a Model to another Model, where the first named Model has many
         * rows for one row in the second named Model. For the inverse
         * relationship, see Model::many_to_one. The middle parameter is used to
         * declare a name for the relationship, which must be unique among all
         * associations for the second named Model.
         *
         * @param string $model The Model on the 'many' side of the relationship
         * @param string $name The name of the relationship
         * @param string $class The Model on the 'one' side of the relationship
         * @return void
         */
        public static function one_to_many($model, $name, $class) {
            self::$one_to_many[$class][$name] = $model;
            self::$associations[$class][$name] = 'otm';

            return NULL;
        }

        /**
         * Create a many-to-many relationship with another Model
         *
         * Links a Model to another Model, where each Model has many rows for
         * each row in the other Model, linked through a join table. By
         * convention, the join table is expected to be the two table names,
         * alphabetically sorted and joined by '_'. The middle parameter is used
         * to declare a name for the relationship, which must be unique among
         * all associations for the second named Model.
         *
         * @param string $model The first Model to include in the relationship
         * @param string $name The name of the relationship
         * @param string $class The second Model to include in the relationship
         * @return void
         */
        public static function many_to_many($model, $name, $class) {
            self::$many_to_many[$class][$name] = $model;
            self::$associations[$class][$name] = 'mtm';

            return NULL;
        }

        /**
         * Create a many-to-one relationship with another Model
         *  public Model::many_to_one(String, String, String)
         *
         * Links a Model to another Model, where the first named Model has one
         * row for many in the second named Model. For the inverse relationship,
         * see Model::one_to_many(). The middle parameter is used to declare a
         * name for the relationship, which must be unique among all
         * associations for the second named Model.
         *
         * @param string $model The Model on the 'one' side of the relationship
         * @param string $name The name of the relationship
         * @param string $class The Model on the 'many' side of the relationship
         * @return void
         */
        public static function many_to_one($model, $name, $class) {
            self::$many_to_one[$class][$name] = $model;
            self::$associations[$class][$name] = 'mto';

            return NULL;
        }

        /**
         * Gather information about table structure
         *
         * Runs a query to fetch column definitions from a table. Returns the
         * dataset of rows of name, db_type, default, allow_null, and
         * primary_key for something else to process.
         *
         * @param string $table The name of the table to inspect
         * @return array
         */
        private static function _colquery($table) {
            if (!Database::prepared('_colquery'))
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
            return Database::prefetch($str, array($table), '_colquery');
        }

        /**
         * Load an association which has been created previously
         *
         * Loads an association based on a preset specification given with
         * one of Model::one_to_many(), Model::many_to_many(), or
         * Model::many_to_one(). Returns a cached version if it's already been
         * loaded unless force is set to TRUE.
         *
         * @param string $name The name of the association to load
         * @param boolean $force Force reload of the association
         * @return array
         */
        private function _load_association($name, $force = FALSE) {
            if (array_key_exists($name, $this->assoc) && !$force)
                return $this->assoc[$name];

            $myclass = (string) get_class($this);
            $mytable = self::$tbl_lookup[$myclass];
            $myid    = preg_replace('/s$/', '_id', $mytable);

            $type   = self::$associations[$myclass][$name];
            $qname  = "_assoc_{$mytable}_{$name}";
            $params = array($this->id());

            switch ($type) {
                case 'otm':
                    $class = self::$one_to_many[$myclass][$name];
                    $table = self::$tbl_lookup[$class];

                    $query = "SELECT * FROM $table WHERE $myid = \$1";
                    $rows  = Database::prefetch($query, $params, $qname);

                    $return = array();
                    foreach ($rows as $row) {
                        $obj = new $class();
                        $obj->_set_all($row);
                        array_push($return, $obj);
                    }

                    $this->assoc[$name] =& $return;
                    return $return;
                case 'mtm':
                    $class  = self::$many_to_many[$name];
                    $table  = self::$tbl_lookup[$class];
                    $tblary = array($table, $mytable);

                    sort($tblary);
                    $jtable = join('_', $tblary);
                    $id     = preg_replace('/s$/', '_id', $table);

                    $query = "SELECT $table.* FROM $table INNER JOIN $jtable " .
                             "ON $jtable.$id = $table.id WHERE $jtable.$myid " .
                             '= $1';
                    $rows  = Database::prefetch($query, $params, $qname);

                    $return = array();
                    foreach ($rows as $row) {
                        $obj = new $class;
                        $this->_set_all($row);

                        array_push($return, $obj);
                    }

                    $this->assoc[$name] =& $return;
                    return $return;
                case 'mto':
                    $class  = self::$many_to_one[$myclass][$name];
                    $table  = self::$tbl_lookup[$class];
                    $id     = preg_replace('/s$/', '_id', $table);
                    $params = array($this->column($id));

                    $query = "SELECT * FROM $table WHERE id = \$1";
                    $rows  = Database::prefetch($query, $params, $qname);
                    $obj   = new $class();
                    $obj->_set_all($rows[0]);

                    $this->assoc[$name] =& $obj;
                    return $obj;
            }
        }

        /**
         * Get paginated row data
         *
         * Returns the Nth "page" of objects, assuming Y objects per page.
         * Defaults to assuming the first page, with 50 objects per page, is
         * desired.
         *
         * @param integer $num The page number to retrieve
         * @param integer $count The number of rows to retrieve
         * @param string $sorts How the results should be sorted
         * @return array
         */
        public static function page($num = 1, $count = 50, $sorts = 'pkeys') {
            $class = get_called_class();

            $table = self::table($class);
            $cols  = self::column_names($class);
            $pkeys = self::primary_keys($class);
            $order = array();

            if ($sorts == 'pkeys') {
                $pkeys = self::primary_keys($class);
                $name  = "_page_{$table}_pkeys";

                foreach ($pkeys as $pkey)
                    array_push($order, "$pkey ASC");
            } else {
                $idx = array();

                foreach ($sorts as $sort) {
                    if (preg_match('/^(.*)\s*(ASC|DESC)$/i', $sort, $matches)) {
                        $sort  = $matches[1];
                        $order = strtoupper($matches[2]);
                    }
                    else
                        $order = 'ASC';

                    $index = array_search($sort, $cols);
                    if ($index === FALSE)
                        trigger_error("$sort is not a valid sort column",
                                      E_USER_ERROR);

                    array_push($idx, $index);
                    array_push($order, "$sort $order");
                }

                $name = "_page_{$table}_" . join($idx, ',');
            }

            if (Database::prepared($name))
                $query = '';
            else {
                $cols  = join(', ', $cols);
                $ords  = join(', ', $order);

                $query = "SELECT $cols FROM $table ORDER BY $ords " .
                         'LIMIT $1 OFFSET $2';
            }

            $offset = ($num - 1) * $count;

            $return = array();
            $result = Database::prefetch($query, array($count, $offset), $name);

            foreach ($result as $row) {
                $obj = new $class();
                $obj->_set_all($row);

                array_push($return, $obj);
            }

            return $return;
        }

        /**
         * Load a row into the Model
         *
         * Pulls data from the database for a given model into the object. Note
         * that this clears any state (modifications/etc.) that have been set
         * on the object first, for any Model-controlled columns.
         *
         * @param mixed $id The value of the 'id' column
         * @return Model
         */
        public function load($id) {
            $table = $this->table();
            $query = "SELECT * FROM $table WHERE id = $1";
            $name  = "_load_$table";

            $data = Database::prefetch($query, array($id), $name);
            $this->_set_all($data[0]);

            return $this;
        }

        /**
         * Save current Model data to the database
         *
         * Saves the data to the database. This method knows whether or not to
         * run an INSERT or an UPDATE operation based on whether primary keys
         * have already been set on this object. It also resets the clean and
         * dirty states to whatever the current values of the table are when the
         * query completes.
         *
         * @return Model
         */
        public function save() {
            $pkeys = $this->primary_keys();

            if ($this->clean[ $pkeys[0] ])
                return $this->_update();
            else
                return $this->_insert();
        }

        /**
         * Update the database with Model data
         *
         * Runs an UPDATE query against the database, based on the current
         * contents of the "dirty" array. Note that this query will return the
         * current value of the database, and only update affected columns. This
         * means that it MAY modify the object in unintended ways, but it WILL
         * NOT modify the database beyond the object's scope.
         *
         * @return Model
         */
        protected function _update() {
            $table = $this->table();
            $cols  = $this->columns();

            $sets  = array();
            $colx  = array();
            $vals  = array();
            $rets  = array();
            $where = array();

            $x = 1;
            $y = 1;
            foreach ($cols as $name => $col) {
                array_push($rets, $name);

                if ($this->clean[$name] !== $this->dirty[$name]) {
                    $val = $col->prep_for_database($this->column($name));

                    array_push($sets, "$name = \$$x");
                    array_push($colx, $y);
                    array_push($vals, $val);
                    $x++;
                }
                $y++;
            }

            // nothing to do!
            if (count($sets) == 0)
                return $this;

            foreach ($this->primary_keys() as $name) {
                $cols = $this->columns();

                $col = $cols[$name];
                $val = $col->prep_for_database($this->column($name));

                array_push($where, "($name = \$$x)");
                array_push($vals,  $val);
                $x++;
            }

            $sets  = join(', ', $sets);
            $colx  = join(',',  $colx);
            $rets  = join(', ', $rets);
            $where = "(" . join(' AND ', $where) . ")";
            $name  = "_update_{$table}_$colx";

            $query   = "UPDATE $table SET $sets WHERE $where RETURNING $rets";
            $results = Database::prefetch($query, $vals, $name);

            $this->_set_all($results[0]);
            return $this;
        }

        /**
         * Create a new entry in the database based on current Model data
         *
         * Runs an INSERT query against the database, based on the current
         * contents of the "dirty" array.
         *
         * @return Model
         */
        protected function _insert() {
            $table = $this->table();
            $cols  = $this->columns();

            $names = array();
            $holds = array();
            $vals  = array();
            $rets  = array();

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
            $rets  = join(', ', $rets);

            $query = "INSERT INTO $table ($names) VALUES ($holds) " .
                     "RETURNING $rets";

            $results = Database::prefetch($query, $vals, "_insert_$table");
            $this->_set_all($results[0]);

            return $this;
        }

        /**
         * Reset all columns contained to values with an array (dirty only)
         *
         * Sets all of the Model-controlled columns at once, clearing their
         * current values first. This assumes the values have come from an
         * HTML form or similar, and only sets the dirty array. To set both the
         * clean and the dirty array, see Model->_set_all().
         *
         * @param array $hash An array of values to assign to the Model
         * @return Model
         */
        public function set_all($hash) {
            $check_re = '/^_check_([a-zA-Z0-9_]+)$/';
            $cols     = $this->columns();

            foreach ($hash as $key => $value) {
                /* if there's a value _check_some_col that we come across, then
                 * reset the $key. this can be used to fill a value in case
                 * the HTML form doesn't send checkboxes (which is the case in
                 * most browsers). Since it's not always going to need to be
                 * used, we'll skip it if there actually *was* a value supplied.
                 */
                if (preg_match($check_re, $key, $matches)) {
                    $key = $matches[1];
                    if (array_key_exists($key, $hash))
                        continue;
                }

                if (!array_key_exists($key, $cols)) {
                    $table = $this->table();
                    trigger_error("Unknown column $key for $table",
                                  E_USER_ERROR);
                    continue;
                }

                $col = $cols[$key];
                $this->dirty[$key] = $cols[$key]->process_value($value);
            }

            return $this;
        }

        /**
         * Reset all columns contained to values with an array (clean and dirty)
         *
         * Sets all of the Model-controlled columns at once, clearing their
         * current values first. This assumes the values have come from the
         * database, and sets the clean array as well as the dirty array. To
         * only set the dirty array, see Model->set_all().
         *
         * @param array $hash
         * @return Model
         */
        protected function _set_all($hash) {
            $cols = $this->columns();
            $this->_clear();

            foreach ($hash as $key => $value) {
                if (!array_key_exists($key, $cols)) {
                    $table = $this->table();
                    trigger_error("Unknown column $key for $table",
                                  E_USER_ERROR);
                    continue;
                }

                $col = $cols[$key];
                $val = $col->process_value($value);

                $this->clean[$key] = $val;
                $this->dirty[$key] = $val;
            }

            return $this;
        }

        /**
         * Clear all data from the Model
         *
         * Clears the current Model-controlled state of the object (the columns
         * and any modifications therein).
         *
         * @return Model
         */
        protected function _clear() {
            $this->clean = array();
            $this->dirty = array();

            foreach ($this->columns() as $key => $col) {
                $this->clean[$key] = NULL;
                $this->dirty[$key] = NULL;
            }

            return $this;
        }

        /**
         * Cache the static call to Model::columns() (which is expensive)
         *
         * @return array
         */
        public function cols() {
            if ($this->cols)
                return $this->cols;

            $this->cols = $this->columns((string) get_class($this));
            return $this->cols;
        }

        /**
         * Return the named Column
         *
         * Returns the Column named, for use in external functions which may
         * need to query certain information such as the datatype of a column.
         * It is purely syntactic sugar, as the data is still accessible without
         * this method.
         *
         * @param string $column The name of the column to return
         * @return Column
         */
        public function column_inspect($column) {
            $cols = $this->cols();
            return $cols[$column];
        }

        /**
         * Return an array of the Column objects for a given model
         *
         * @param string $class The Model for which columns should be returned
         * @return array
         */
        public static function columns($class = NULL) {
            if (!$class)
                $class = get_called_class();

            if (!array_key_exists($class, self::$columns)) {
                $table = self::table($class);
                $ary   = self::_colquery($table);

                self::$columns[$class] = array();
                $columns =& self::$columns[$class];

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

            return self::$columns[$class];
        }

        /**
         * Sort an array of Model objects
         *
         * A sorting function for a default sort of an array of Model objects,
         * suitable for passing to usort().
         *
         * @param Model $a
         * @param Model $b
         * @return integer
         */
        public static function sort($a, $b) {
            $aid = $a->id();
            $bid = $b->id();
            if ($aid == $bid)
                return 0;

            return ($aid > $bid) ? 1 : -1;
        }

        /**
         * Return a sorted array of all column names for a given model
         *
         * @param string $class The class for which to get columns
         * @return array
         */
        public function column_names($class = NULL) {
            if (!$class)
                $class = get_called_class();

            $keys = array_keys(self::columns($class));
            sort($keys);

            return $keys;
        }

        /**
         * Return an array of columns constituting the Model's primary key
         *
         * Returns a list of columns which constitute the primary key of this
         * table. This will usually be one-element arrays, ie "['id']", but it
         * allows us to support more complex operations.
         *
         * @param string $class The class for which to get the primary key
         * @return array
         */
        public static function primary_keys($class = NULL) {
            if (!$class)
                $class = get_called_class();

            $pkeys = array();

            foreach (self::columns($class) AS $name => $col)
                if ($col->primary_key())
                    array_push($pkeys, $name);

            return $pkeys;
        }

        /**
         * Get a column's value
         *
         * Returns the current value of a particular column (that is, whatever
         * modifications have already been done, not whatever value is stored in
         * the database). If there's no such column, NULL is returned.
         *
         * @param string $name The name of the column to get
         * @return mixed
         */
        public function column($name) {
            if (!array_key_exists($name, $this->cols()))
                return;

            return $this->dirty[$name];
        }

        /**
         * Return the value portion of the form element for the specified column
         *
         * Returns a piece of a form element which will fill in the value
         * currently in the model.
         *
         * @param string $name The name of the column to get
         * @param mixed $comparison The value to compare for a checkbox
         * @param boolean $echo Whether to echo the value string or not
         * @return string
         */
        public function form($name, $comparison = NULL, $echo = TRUE) {
            $cols = $this->cols();

            if (!array_key_exists($name, $cols))
                return;

            $col = $cols[$name];
            $val = $col->formify($this->column($name), $comparison);

            if ($echo)
                echo $val;
            return $val;
        }

        /**
         * Return a sanitized-for-HTML version of the value of a column
         *
         * @param string $name The name of the column
         * @return string
         */
        public function display($name) {
            $cols = $this->cols();

            if (!array_key_exists($name, $cols))
                return;

            $col = $cols[$name];
            return $col->stringify($this->column($name));
        }

        /**
         * Set a column to the specified value
         *
         * Sets the column specified to the value specified. If there is no such
         * column, NULL is returned. If there is a column, then the processed
         * value is returned. This may not always be the same value that was
         * sent to be set (for instance, a Date provided as a String will return
         * a Date object).
         *
         * @param string $name The name of the column
         * @param mixed $value The value to set
         * @return mixed
         */
        public function set_column($name, $value) {
            $cols = $this->cols();

            if (!array_key_exists($name, $cols))
                return;

            $col   = $cols[$name];
            $value = $col->process_value($value);
            $this->dirty[$name] = $value;

            return $value;
        }

        /**
         * Magic call method public
         *
         * Locates suitable handlers for methods which are not defined by hand
         * in PHP files. This allows for making calls to column_name() and
         * set_column_name() without writing out those methods, a tedious
         * process.
         *
         * @param string $name The name of the method being called
         * @param array $args An array of arguments to the method
         * @return mixed
         */
        public function __call($name, $args) {
            if (array_key_exists($name, $this->cols()))
                return $this->column($name);

            if (preg_match('/^set_([a-zA-Z0-9_]+)$/', $name, $matches))
                if (array_key_exists($matches[1], $this->cols()))
                    return $this->set_column($matches[1], $args[0]);

            if (preg_match('/^load_([a-zA-Z0-9_]+)$/', $name, $matches)) {
                $assoc = self::$associations[(string) get_class($this)];
                if (array_key_exists($matches[1], $assoc)) {
                    $force = count($args) ? ((boolean) $args[0]) : FALSE;
                    return $this->_load_association($matches[1], $force);
                }
            }

            $class = ((string) get_class($this));
            trigger_error("Method $class::$name does not exist",
                          E_USER_ERROR);
        }

        /**
         * Return the name of the table previously associated with the Model
         *
         * Returns the table name for a given class. It must have been
         * previously registered with Model::associate_table().
         *
         * @param string $class The name of the class
         * @return string
         */
        public static function table($class = NULL) {
            if (!$class)
                $class = get_called_class();

            if (array_key_exists($class, self::$tbl_lookup))
                return self::$tbl_lookup[$class];
            else
                trigger_error("Table for $class is not defined",
                              E_USER_ERROR);
        }
    }

?>
