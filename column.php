<?php

    include_once(join(DIRECTORY_SEPARATOR,
                      array(dirname(__FILE__), 'date.php')));

    class Column {
        private $name;
        private $datatype;
        private $allow_null;
        private $primary_key;

        /**
         * Column constructor
         *
         * @param string $name The name of the column
         * @param string $datatype The datatype of the column
         * @param boolean $allow_null Whether NULL is an allowable value
         * @param boolean $primary_key Whether the column is a primary key
         * @return Column
         */
        function __construct($name, $datatype, $allow_null, $primary_key) {
            $this->name        = $name;
            $this->datatype    = $datatype;
            $this->allow_null  = (boolean) $allow_null;
            $this->primary_key = (boolean) $primary_key;
        }

        /**
         * Return the name of the column
         *
         * @return string
         */
        public function name() {
            return $this->name;
        }

        /**
         * Return the datatype of the column
         *
         * @return string
         */
        public function datatype() {
            return $this->datatype;
        }

        /**
         * Return whether this column allows NULL values in the database
         *
         * @return boolean
         */
        public function allow_null() {
            return $this->allow_null;
        }

        /**
         * Returns whether this column is part of the primary key of the table
         *
         * @return boolean
         */
        public function primary_key() {
            return $this->primary_key;
        }

        /**
         * Convert value to string for HTML
         *
         * Converts a value to a String for displaying on an HTML page, based
         * on its datatype.
         *
         * @param string $value The value to convert
         * @return string
         */
        public function stringify($value) {
            switch ($this->datatype) {
                case 'boolean':
                    if ($value === null) {
                        return '?';
                    }

                    return $value ? 'Yes' : 'No';
                case 'date':
                    return $value ? $value->to_s() : '';
                default:
                    return htmlspecialchars((string) $value);
            }
        }

        /**
         * Convert value to string for a form
         *
         * Converts a value to a String, which is a fragment of an HTML form
         * suitable for filling in the current value.
         *
         * @param string $value The value to convert
         * @param string $comparison The value to compare for a checkbox
         * @return string
         */
        public function formify($value, $comparison = null) {
            if ($value === null) {
                return '';
            }

            if ($comparison !== null) {
                if ($value == $comparison) {
                    return ' checked';
                }
                else {
                    return '';
                }
            }


            switch ($this->datatype) {
                case 'boolean':
                    return $value ? ' checked' : '';
                case 'date':
                    return ' value="' . $value->to_s() . '"';
                default:
                    return ' value="' . ((string) $value) . '"';
            }
        }

        /**
         * Prepare a string for the database
         *
         * Converts a value to a string suitable for insertion into q query to
         * the database (eg, INSERT or UPDATE queries).
         *
         * @param string $value The value to prepare
         * @return string
         */
        public function prep_for_database($value) {
            if ($value === null) {
                return null;
            }

            switch ($this->datatype) {
                case 'boolean':
                    return $value ? 't' : 'f';
                case 'date':
                    return $value->to_s();
                default:
                    return (string) $value;
            }
        }

        /**
         * Process value from database or form
         *
         * Processes a value coming either from the database or an HTML form
         * for inclusion in an object, depending on the datatype of the
         * column.
         *
         * @param string $value The value to process
         * @return mixed
         */
        public function process_value($value) {
            if (($value === '') || ($value === null)) {
                return null;
            }

            switch ($this->datatype) {
                case 'boolean':
                    if ($value === 'null') {
                        return null;
                    }
                    else if ($value === 'f' || $value === 0 ||
                             $value === false || $value === 'false' ||
                             !$value) {
                        return false;
                    }
                    else {
                        return true;
                    }
                case 'double precision':
                case 'numeric':
                    return (double) $value;
                case 'integer':
                    return (integer) $value;
                case 'date':
                    if (is_object($value) &&
                        ((string) get_class($value)) == 'Date') {
                        return $value;
                    }

                    return Date::parse($value);
                default:
                    if ($value == '') {
                        return null;
                    }
                    else {
                        return $value;
                    }
            }
        }
    }

?>
