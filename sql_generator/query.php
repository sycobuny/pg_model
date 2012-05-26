<?php

    class Query extends _QueryTableExpression {
        public static function lit($string) {
            return _QueryLiteral::create($string);
        }
    }

?>
