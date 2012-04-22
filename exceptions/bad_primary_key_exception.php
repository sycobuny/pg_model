<?php

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

?>
