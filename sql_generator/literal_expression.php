<?php

    class _QueryLiteral implements _QueryExpression {
        private $string;

        /**
         * _QueryLiteral constructor
         *
         * @param string $string The literal SQL to include
         * @return _QueryLiteral
         */
        function __construct($string) {
            if (is_a($string, '_QueryExpression')) {
                $string = $string->sql_string();
            }
            else if ((is_object($string) &&
                      !method_exists($string, '__toString')) ||
                     is_array($string)) {
                /* TODO: blow up */
            }

            $this->string = "$string";
        }

        /**
         * _QueryLiteral sugary constructor
         *
         * Constructs a _QueryLiteral object, or simply returns a
         * _QueryLiteral object if passed a preconstructed one.
         *
         * @param string $string The literal SQL to include
         * @return _QueryLiteral
         */
        public static function create($string) {
            if (is_a($string, '_QueryLiteral')) {
                return $string;
            }
            else {
                return new _QueryLiteral($string);
            }
        }

        /**
         * Produce SQL code from this object.
         *
         * Processes this expression and returns the resulting SQL
         * string.
         *
         * @return string
         */
        public function sql_string() {
            return $this->string;
        }
    }

?>
