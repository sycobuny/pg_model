<?php

    include('lib.php');
    include('database.php');
    include('column.php');

    abstract class Model {
        private static $columns    = Array();
        private static $tbl_lookup = Array();
        private static $cls_lookup = Array();

        private static $associations = Array();
        private static $one_to_many  = Array();
        private static $many_to_many = Array();
        private static $many_to_one  = Array();

        private $assoc;
        private $cols;
        private $clean;
        private $dirty;

        /* new Model()
         * returns Model
         *
         * Preloads column definitions and creates an object where all columns
         * are set to NULL to prevent any "undefined key" errors.
         */
        function __construct() {
            $this->assoc = Array();

            $this->cols(); // get the value and discard the result
            $this->_clear();
        }

        /* public Model::associate_table(String, String)
         * returns NULL
         *
         * Links a table to a class and vice versa. This is necessary because
         * PHP 5.1 does not have particularly good class introspection.
         */
        public static function associate_table($table, $class) {
            self::$tbl_lookup[$class] = $table;
            self::$cls_lookup[$table] = $class;

            return NULL;
        }

        /* public Model::one_to_many(String, String, String)
         * returns NULL
         *
         * Links a Model to another Model, where the first named Model has many
         * rows for one row in the second named Model. For the inverse
         * relationship, see Model::many_to_one. The middle parameter is used to
         * declare a name for the relationship, which must be unique among all
         * associations for the second named Model.
         */
        public static function one_to_many($model, $name, $class) {
            self::$one_to_many[$class][$name] = $model;
            self::$associations[$class][$name] = 'otm';

            return NULL;
        }

        /* public Model::many_to_many(String, String, String)
         * returns NULL
         *
         * Links a Model to another Model, where each Model has many rows for
         * each row in the other Model, linked through a join table. By
         * convention, the join table is expected to be the two table names,
         * alphabetically sorted and joined by '_'. The middle parameter is used
         * to declare a name for the relationship, which must be unique among
         * all associations for the second named Model.
         */
        public static function many_to_many($model, $name, $class) {
            self::$many_to_many[$class][$name] = $model;
            self::$associations[$class][$name] = 'mtm';

            return NULL;
        }

        /* public Model::many_to_one(String, String, String)
         * returns NULL
         *
         * Links a Model to another Model, where the first named Model has one
         * row for many in the second named Model. For the inverse relationship,
         * see Model::one_to_many(). The middle parameter is used to declare a
         * name for the relationship, which must be unique among all
         * associations for the second named Model.
         */
        public static function many_to_one($model, $name, $class) {
            self::$many_to_one[$class][$name] = $model;
            self::$associations[$class][$name] = 'mto';

            return NULL;
        }

        /* private Model::_colquery()
         * returns Array
         *
         * Runs a query to fetch column definitions from a table. Returns the
         * dataset of rows of name, db_type, default, allow_null, and
         * primary_key for something else to process.
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
            return Database::prefetch($str, Array($table), '_colquery');
        }

        /* private _load_association(String, [Boolean])
         * returns Array
         *
         * Loads an association based on a preset specification given with
         * one of Model::one_to_many(), Model::many_to_many, or
         * Model::many_to_one(). Returns a cached version if it's already been
         * loaded unless force is set to TRUE.
         */
        private function _load_association($name, $force = FALSE) {
            if (array_key_exists($name, $this->assoc) && !$force)
                return $this->assoc[$name];

            $myclass = (string) get_class($this);
            $mytable = self::$tbl_lookup[$myclass];
            $myid    = preg_replace('/s$/', '_id', $mytable);

            $type   = self::$associations[$myclass][$name];
            $qname  = "_assoc_{$mytable}_{$name}";
            $params = Array($this->id());

            switch ($type) {
                case 'otm':
                    $class = self::$one_to_many[$myclass][$name];
                    $table = self::$tbl_lookup[$class];

                    $query = "SELECT * FROM $table WHERE $myid = \$1";
                    $rows  = Database::prefetch($query, $params, $qname);

                    $return = Array();
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
                    $tblary = Array($table, $mytable);

                    sort($tblary);
                    $jtable = join('_', $tblary);
                    $id     = preg_replace('/s$/', '_id', $table);

                    $query = "SELECT $table.* FROM $table INNER JOIN $jtable " .
                             "ON $jtable.$id = $table.id WHERE $jtable.$myid " .
                             '= $1';
                    $rows  = Database::prefetch($query, $params, $qname);

                    $return = Array();
                    foreach ($rows as $row) {
                        $obj = new $class;
                        $this->_set_all($row);

                        array_push($return, $obj);
                    }

                    $this->assoc[$name] =& $return;
                    return $return;
                case 'mto':
                    $class = self::$many_to_one[$myclass][$name];
                    $table = self::$tbl_lookup[$class];

                    $query = "SELECT * FROM $table WHERE $myid = \$1";
                    $rows  = Database::prefetch($query, $params, $qname);
                    $obj   = new $class();
                    $obj->_set_all($rows[0]);

                    $this->assoc[$name] =& $obj;
                    return $obj;
            }
        }

        /* public Model::page([Integer], [Integer])
         * returns Array
         *
         * Returns the Nth "page" of objects, assuming Y objects per page.
         * Defaults to assuming the first page, with 50 objects per page, is
         * desired.
         */
        public static function page($num = 1, $count = 50, $sorts = 'pkeys') {
            $class = get_called_class();

            $table = self::table($class);
            $cols  = self::column_names($class);
            $pkeys = self::primary_keys($class);
            $order = Array();

            if ($sorts == 'pkeys') {
                $pkeys = self::primary_keys($class);
                $name  = "_page_{$table}_pkeys";

                foreach ($pkeys as $pkey)
                    array_push($order, "$pkey ASC");
            } else {
                $idx = Array();

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

            $return = Array();
            $result = Database::prefetch($query, Array($count, $offset), $name);

            foreach ($result as $row) {
                $obj = new $class();
                $obj->_set_all($row);

                array_push($return, $obj);
            }

            return $return;
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
            $name  = "_load_$table";

            $data = Database::prefetch($query, Array($id), $name);
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

        /* protected Model->_update()
         * returns $this;
         *
         * Runs an UPDATE query against the database, based on the current
         * contents of the "dirty" array. Note that this query will return the
         * current value of the database, and only update affected columns. This
         * means that it MAY modify the object in unintended ways, but it WILL
         * NOT modify the database beyond the object's scope.
         */
        protected function _update() {
            $table = $this->table();
            $cols  = $this->columns();

            $sets  = Array();
            $colx  = Array();
            $vals  = Array();
            $rets  = Array();
            $where = Array();

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

            $results = Database::prefetch($query, $vals, "_insert_$table");
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

        /* protected Model->_clear()
         * returns $this
         *
         * Clears the current Model-controlled state of the object (the columns
         * and any modifications therein).
         */
        protected function _clear() {
            $this->clean = Array();
            $this->dirty = Array();

            foreach ($this->columns() as $key => $col) {
                $this->clean[$key] = NULL;
                $this->dirty[$key] = NULL;
            }

            return $this;
        }

        /* public Model->cols()
         * returns Array
         *
         * Caches the static call to Model::columns() (which is expensive).
         */
        public function cols() {
            if ($this->cols)
                return $this->cols;

            $this->cols = $this->columns((string) get_class($this));
            return $this->cols;
        }

        /* public Model::columns([String])
         * returns Array
         *
         * Returns an array of the Column objects for a given model.
         */
        public static function columns($class = NULL) {
            if (!$class)
                $class = get_called_class();

            if (!array_key_exists($class, self::$columns)) {
                $table = self::table($class);
                $ary   = self::_colquery($table);

                self::$columns[$class] = Array();
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

        /* public Model::sort(Model, Model)
         * returns Integer
         *
         * A sorting function for a default sort of an array of Model objects,
         * suitable for passing to usort().
         */
        public static function sort($a, $b) {
            $aid = $a->id();
            $bid = $b->id();
            if ($aid == $bid)
                return 0;

            return ($aid > $bid) ? 1 : -1;
        }

        /* public Model::column_names([String])
         * returns Array
         *
         * Returns a sorted array of all column names for a given model.
         */
        public function column_names($class = NULL) {
            if (!$class)
                $class = get_called_class();

            $keys = array_keys(self::columns($class));
            sort($keys);

            return $keys;
        }

        /* public Model::primary_keys([String])
         * returns Array
         *
         * Returns a list of columns which constitute the primary key of this
         * table. This will usually be one-element arrays, ie "['id']", but it
         * allows us to support more complex operations.
         */
        public static function primary_keys($class = NULL) {
            if (!$class)
                $class = get_called_class();

            $pkeys = Array();

            foreach (self::columns($class) AS $name => $col)
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
            if (!array_key_exists($name, $this->cols()))
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
            $cols = $this->cols();

            if (!array_key_exists($name, $cols))
                return;

            $col = $cols[$name];
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
            $cols = $this->cols();

            if (!array_key_exists($name, $cols))
                return;

            $col = $cols[$name];
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
            $cols = $this->cols();

            if (!array_key_exists($name, $cols))
                return;

            $col   = $cols[$name];
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

        /* public (abstract) Model::table([String])
         * returns String
         *
         * Returns the table name for a given class. It must have been
         * previously registered with Model::associate_table().
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
