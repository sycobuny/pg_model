<?php

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
