<?php

    class Date {
        private $year;
        private $month;
        private $day;

        /**
         * Parse a date string
         *
         * Creates a new Date object by parsing the string. It currently only
         * understands the YYYY-MM-DD format, and does no error checking. I
         * should fix that.
         *
         * @param string $str The date
         * @return Date
         */
        public static function parse($str) {
            if (($str === null) || ($str == '')) {
                return null;
            }

            $ary = explode('-', $str);

            $year  = (integer) $ary[0];
            $month = (integer) $ary[1];
            $day   = (integer) $ary[2];

            return new Date($year, $month, $day);
        }

        /**
         * Date constructor
         *
         * @param integer $year The year
         * @param integer $month The month
         * @param integer $day The day of the month
         * @return Date
         */
        function __construct($year, $month, $day) {
            $this->year  = (integer) $year;
            $this->month = (integer) $month;
            $this->day   = (integer) $day;
        }

        /**
         * Return the year
         *
         * @return integer
         */
        public function year() {
            return $this->year;
        }

        /**
         * Return the month
         *
         * @return integer
         */
        public function month() {
            return $this->month;
        }

        /**
         * Return the day of the month
         *
         * @return integer
         */
        public function day() {
            return $this->day;
        }

        /**
         * Stringify the date (YYYY-MM-DD)
         *
         * @return string
         */
        public function to_s() {
            $format = "%04d-%02d-%02d";

            return sprintf($format, $this->year, $this->month, $this->day);
        }

        /**
         * Calculate an age based on this date
         *
         * Returns the number of whole years that have passed between this
         * Date and now.
         *
         * @return integer
         */
        public function age() {
            $years = ((integer) date('Y')) - $this->year;

            if (((integer) date('n')) < $this->month) {
                $years -= 1;
            }
            else if (((integer) date('n')) == $this->month) {
                if (((integer) date('j')) < $this->day) {
                    $years -= 1;
                }
            }

            return $years;
        }
    }

?>
