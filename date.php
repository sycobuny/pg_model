<?php

    class Date {
        private $year;
        private $month;
        private $day;

        /* public Date::parse(String)
         * returns Date
         *
         * Parses a string to extract the date. Currently only understands one
         * format: YYYY-MM-DD, but that's the way a proper form will submit the
         * value, and the way the database will serve it. Anything else will
         * spit back an error at the user when they attempt to use it.
         */
        public static function parse($str) {
            if (($str === null) || ($str == ''))
                return null;

            $ary = explode('-', $str);

            $year  = (integer) $ary[0];
            $month = (integer) $ary[1];
            $day   = (integer) $ary[2];

            return new Date($year, $month, $day);
        }

        /* new Date(Integer, Integer, Integer)
         * returns Date
         *
         * Processes the year, month, and day parts of a date and stores them.
         */
        function __construct($year, $month, $day) {
            $this->year  = (integer) $year;
            $this->month = (integer) $month;
            $this->day   = (integer) $day;
        }

        /* public Date->year()
         * returns Integer
         *
         * Returns the year for this Date.
         */
        public function year() {
            return $this->year;
        }

        /* public Date->month()
         * returns Integer
         *
         * Returns the month for this Date.
         */
        public function month() {
            return $this->month;
        }

        /* public Date->day()
         * returns Integer
         *
         * Returns the day of the month for this Date.
         */
        public function day() {
            return $this->day;
        }

        /* public Date->to_s()
         * returns String
         *
         * Returns the Date as a string representation (YYYY-MM-DD).
         */
        public function to_s() {
            $format = "%04d-%02d-%02d";

            return sprintf($format, $this->year, $this->month, $this->day);
        }

        /* public Date->age()
         * returns Integer
         *
         * Returns the number of whole years that have passed between this Date
         * and now.
         */
        public function age() {
            $years = ((integer) date('Y')) - $this->year;

            if (((integer) date('n')) < $this->month)
                $years -= 1;
            else if (((integer) date('n')) == $this->month)
                if (((integer) date('j')) < $this->day)
                    $years -= 1;

            return $years;
        }
    }

?>
