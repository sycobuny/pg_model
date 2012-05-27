<?php

    class _QueryString extends _QueryAliasableExpression
                       implements _QueryValueExpression {
        private $string;

        /**
         * _QueryString constructor
         *
         * @param string $string The SQL string to include
         * @return _QueryString
         */
        function __construct($string) {
            if (is_a($string, '_QueryString')) {
                $string = $string->string;
            }
            else if (is_a($string, '_QueryExpression')) {
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
         * _QueryString sugary constructor
         *
         * Constructs a _QueryString object, or simply returns a
         * _QueryString object if passed a preconstructed one.
         *
         * @param string $string The SQL string to include
         * @return _QueryString
         */
        public static function create($string) {
            if (is_a($string, '_QueryString')) {
                return $string;
            }
            else {
                return new _QueryString($string);
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
            return "'" . pg_escape_string($this->string) . "'";
        }
    }

?>
