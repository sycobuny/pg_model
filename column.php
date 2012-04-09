<?php

    include('date.php');

    class Column {
        private $name;
        private $datatype;
        private $allow_null;
        private $primary_key;

        /* new Column(String, String, Bool, Bool)
         * returns Column
         *
         * Constructs a column object.
         */
        function __construct($name, $datatype, $allow_null, $primary_key) {
            $this->name        = $name;
            $this->datatype    = $datatype;
            $this->allow_null  = (boolean) $allow_null;
            $this->primary_key = (boolean) $primary_key;
        }

        /* public Column->name()
         * returns String
         *
         * Returns the name of this column.
         */
        public function name() {
            return $this->name;
        }

        /* public Column->datatype()
         * returns String
         *
         * Returns the datatype of this column.
         */
        public function datatype() {
            return $this->datatype;
        }

        /* public Column->allow_null()
         * returns Bool
         *
         * Returns whether this column allows NULL values in the database.
         */
        public function allow_null() {
            return $this->allow_null;
        }

        /* public Column->primary_key()
         * returns Bool
         *
         * Returns whether this column is part of the primary key of the table.
         */
        public function primary_key() {
            return $this->primary_key;
        }

        /* public Column->stringify(String)
         * returns String
         *
         * Converts a value to a String for displaying on an HTML page, based
         * on its datatype.
         */
        public function stringify($value) {
            switch ($this->datatype) {
                case 'boolean':
                    if ($value === NULL)
                        return '?';

                    return $value ? 'Yes' : 'No';
                case 'date':
                    return $value ? $value->to_s() : 'Unknown/empty';
                default:
                    return htmlspecialchars((string) $value);
            }
        }

        /* public Column->formify(String, [String])
         * returns String
         *
         * Converts a value to a String, which is a fragment of an HTML form
         * suitable for filling in the current value.
         */
        public function formify($value, $comparison = NULL) {
            if ($value === NULL)
                return '';

            if (($comparison !== NULL) && ($value == $comparison))
                return ' checked';

            switch ($this->datatype) {
                case 'boolean':
                    return $value ? ' checked' : '';
                case 'date':
                    return ' value="' . $value->to_s() . '"';
                default:
                    return ' value="' . ((string) $value) . '"';
            }
        }

        /* public Column->prep_for_database(String)
         * returns String
         *
         * Converts a value to a string suitable for insertion into q query to
         * the database (eg, INSERT or UPDATE queries).
         */
        public function prep_for_database($value) {
            if ($value === NULL)
                return NULL;

            switch ($this->datatype) {
                case 'boolean':
                    return $value ? 't' : 'f';
                case 'date':
                    return $value->to_s();
                default:
                    return (string) $value;
            }
        }

        /* public Column->process_value(Mixed)
         * returns Mixed
         *
         * Processes a value coming either from the database or an HTML form for
         * inclusion in an object, depending on the datatype of the column.
         */
        public function process_value($value) {
            switch ($this->datatype) {
                case 'boolean':
                    if ($value === 'f' || $value === 0 || $value === false ||
                        $value === 'false' || !$value) {
                        return FALSE;
                    }
                    else {
                        return TRUE;
                    }
                case 'double precision':
                case 'numeric':
                    return (double) $value;
                case 'integer':
                    return (integer) $value;
                case 'text':
                case 'character varying':
                    return $value;
                case 'date':
                    if (is_object($value) &&
                        ((string) get_class($value)) == 'Date')
                        return $value;
                    return Date::parse($value);
                case 'timestamp with time zone':
                case 'timestamp without time zone':
                    return $value;
                case 'interval':
                    return $value;
                default:
                    return $value;
            }
        }
    }

?>
