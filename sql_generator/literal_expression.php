<?php

    class _QueryLiteral implements _QueryExpression {
        private $string;

        function __construct($string) {
            if (is_a($string, '_QueryExpression')) {
                $string = $string->sql_string();
            }
            else if (is_object($string)) {
                if (method_exists($string, '__toString')) {
                    $string = "$string";
                }
                else {
                    /* TODO: blow up */
                }
            }
            else if (is_array($string)) {
                /* TODO: blow up */
            }

            $this->string = "$string";
        }

        public static function create($string) {
            if (is_a($string, '_QueryLiteral')) {
                return $string;
            }
            else {
                return new _QueryLiteral($string);
            }
        }

        public function sql_string() {
            return $this->string;
        }
    }

?>
