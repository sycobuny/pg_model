<?php

    class BadColumnException extends BadMethodCallException {
        protected $table;
        protected $column;

        /**
         * BadColumnException constructor
         *
         * Creates a new BadColumnException, which saves the table and column
         * name in question, and generates a default error message if required.
         *
         * @param string $table The table which caused the error
         * @param string $column The column which caused the error
         * @param string $message The exception error message
         * @param int $code The exception code
         * @return BadColumnException
         */
        public function __construct($table, $column, $message = '', $code = 0) {
            $this->table  = $table;
            $this->column = $column;

            if (!$message) {
                $message = "Column $table.$column does not exist.";
            }

            parent::__construct($message, $code);
        }

        /**
         * Retrieve the table member
         *
         * This was created as an accessor method to fit with the style of
         * built-in Exceptions.
         *
         * @return string
         */
        public function getTable() {
            return $this->table;
        }

        /**
         * Retrieve the column member
         *
         * This was created as an accessor method to fit with the style of
         * built-in Exceptions.
         *
         * @return string
         */
        public function getColumn() {
            return $this->column;
        }
    }

    class BadPrimaryKeyException extends BadColumnException {
        /**
         * BadPrimaryKeyException constructor
         *
         * Creates a new BadPrimaryKeyException, which is like a regular column
         * exception, except that the column *may* exist, but it is not a
         * primary key in any case. It generates an error message if required.
         *
         * @param string $table The table which caused the error
         * @param string $column The name of the column which caused the error
         * @param string $message The exception error message
         * @param int $code The exception
         * @return BadPrimaryKeyException
         */
        public function __construct($table, $column, $message = '', $code = 0) {
            if (!$message) {
                $message = "Column $column is not a primary key for $table.";
            }

            parent::__construct($table, $column, $message, $code);
        }
    }

    class NoSuchRowException extends RuntimeException {
    }

    class AmbiguousInflectionException extends InvalidArgumentException {
        protected $function;
        protected $original;
        protected $inflected;
        protected $existing;

        /**
         * AmbiguousInflectionException constructor
         *
         * Creates a new AmbiguousInflectionException, which saves the name of
         * the inflection function in question, the original uninflected value,
         * the calculated value, and what was found in a cached version (which
         * should be different). It also generates an error message, if
         * required.
         *
         * @param string $function The function which generated the error
         * @param string $original The original uninflected value
         * @param string $inflected The value after inflection
         * @param string $existing The cached inflected value
         * @param int $code The exception code
         * @return AmbiguousInflectionException
         */
        public function __construct($function, $original, $inflected, $existing,
                                    $message = '', $code = 0) {
            $this->function  = $function;
            $this->original  = $original;
            $this->inflected = $inflected;
            $this->existing  = $existing;

            if (!$message) {
                $message = "$function(): Value $inflected was calculated " .
                           "from $original, but $existing was found caached.";
            }

            parent::__construct($message, $code);
        }

        /**
         * Retrieve the inflector function
         *
         * This was created as an accessor method to fit with the style of
         * built-in Exceptions.
         *
         * @return string
         */
        public function getFunction() {
            return $this->function;
        }

        /**
         * Retrieve the original uninflected value
         *
         * This was created as an accessor method to fit with the style of
         * built-in Exceptions.
         *
         * @return string
         */
        public function getOriginal() {
            return $this->original;
        }

        /**
         * Retrieve the inflected value
         *
         * This was created as an accessor method to fit with the style of
         * built-in Exceptions.
         *
         * @return string
         */
        public function getInflected() {
            return $this->inflected;
        }

        /**
         * Retrieve the cached inflected value
         *
         * This was created as an accessor method to fit with the style of
         * built-in Exceptions.
         *
         * @return string
         */
        public function getExisting() {
            return $this->existing;
        }
    }

?>
