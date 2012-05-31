<?php

    interface _QueryFunction extends _QueryExpression {
        public function name();
        public function arguments();
    }

?>
