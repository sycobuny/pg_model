<?php

    class Query extends _QueryTableExpression {
        /**
         * _QueryLiteral sugary constructor
         *
         * Constructs a _QueryLiteral object, or simply returns a
         * _QueryLiteral object if passed a preconstructed one.
         *
         * @param string $string The literal SQL to include
         * @return _QueryLiteral
         */
        public static function lit($string) {
            return _QueryLiteral::create($string);
        }

        /**
         * _QueryString sugary constructor
         *
         * Constructs a _QueryString object, or simply returns a
         * _QueryString object if passed a preconstructed one.
         *
         * @param string $string The SQL string to include
         * @return _QueryString
         */
        public static function string($string) {
            return _QueryString::create($string);
        }
    }

?>
