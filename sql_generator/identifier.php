<?php

    class _QueryIdentifier extends _QueryValueExpression {
        private $identifier;

        /**
         * _QueryIdentifier constructor
         *
         * @param string $identifier The SQL identifier to include
         * @return _QueryIdentifier
         */
        function __construct($identifier) {
            if (is_a($identifier, '_QueryIdentifier')) {
                $identifier = $identifier->identifier;
            }
            else if (is_a($identifier, '_QueryExpression')) {
                $identifier = $identifier->sql_string();
            }
            else if ((is_object($identifier) &&
                      !method_exists($identifier, '__toString')) ||
                     is_array($identifier)) {
                /* TODO: blow up */
            }

            $this->identifier = "$identifier";
        }

        /**
         * _QueryIdentifier sugary constructor
         *
         * Constructs a _QueryIdentifier object, or simply returns a
         * _QueryIdentifier object if passed a preconstructed one.
         *
         * @param string $identifier The SQL identifier to include
         * @return _QueryIdentifier
         */
        public static function create($identifier) {
            if (is_a($identifier, '_QueryIdentifier')) {
                return $identifier;
            }
            else {
                return new _QueryIdentifier($identifier);
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
            return Database::quote_identifier($this->identifier);
        }
    }

?>
