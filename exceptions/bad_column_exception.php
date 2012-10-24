<?php

    class BadColumnException extends BadMethodCallException {
        protected $table;
        protected $column;

        /**
         * BadColumnException constructor
         *
         * Creates a new BadColumnException, which saves the table and column
         * name in question, and generates a default error message if
         * required.
         *
         * @param string $table The table which caused the error
         * @param string $column The column which caused the error
         * @param string $message The exception error message
         * @param int $code The exception code
         * @return BadColumnException
         */
        public function __construct($table, $column, $message = '',
                                    $code = 0) {
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

?>
