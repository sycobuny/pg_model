<?php

    class Inflection {
        /* The following list of possibilities is taken from:
         *   http://www.barstow.edu/lrc/tutorserv/handouts/
                    015%20Irregular%20Plural%20Nouns.pdf
         * Matt Lanigan (@rintaun) found it for me.
         */

        /* This is a lookup for values which have previously been camelized or
         * decamelized. It serves as a cache for shortcutting processing as well
         * as preventing certain ambiguous situations.
         */
        private static $camelized   = array();
        private static $decamelized = array();

        /* This is the list of words which can't be counted; that is, a call to
         * singularize() or pluralize() will have no effect.
         */
        private static $uncountable = array(
            'cod', 'deer', 'fish', 'offspring', 'sheep', 'trout', 'barracks',
            'crossroads', 'gallows', 'headquarters', 'means', 'series',
            'species', 'equipment', 'fuzz', 'jazz', 'razzmatazz'
        );

        /* These words can be used as suffixes on other words, but are also
         * themselves somewhat irregular.
         */
        private static $suffixes = array(
            'man'    => 'men',
            'person' => 'people',
            'foot'   => 'feet',
            'goose'  => 'geese',
            'louse'  => 'lice',
            'mouse'  => 'mice',
            'tooth'  => 'teeth',
            'hertz'  => 'hertz',
        );

        /* These are either foreign loan words or are from Old English, or any
         * number of other situations which renders them completely irregular.
         * it's assumed they can't be used as suffixes as the previous list
         * can.
         */
        public static $irreg = array(
            'child'  => 'children',
            'ox'     => 'oxen',
            'corpus' => 'corpora',
            'genus'  => 'genera',
            'buzz'   => 'buzzes',

            // Italian loan words
            'libretto' => 'libretti',
            'tempo'    => 'tempi',
            'virtuoso' => 'virtuosi',

            // Hebrew loan words
            'cherub'   => 'cherubim',
            'seraph'   => 'seraphim',

            // Greek loan words
            'schema'   => 'schemata',

            // there's actually a rule for this but English ignores it except
            // here, so we're treating it as irregular.
            'vortex' => 'vortices',
        );

        /* Words that end in f and convert to ves in plural.
         */
        private static $f_ves = array(
            'cal', 'el', 'hal', 'hoo', 'lea', 'loa', 'scar', 'sel', 'shea',
            'shel', 'thie', 'wol'
        );

        /* Words that end in fe and convert to ves in plural.
         */
        private static $fe_ves = array(
            'kni', 'li', 'wi'
        );

        /* Words that end in o and convert to oes in plural.
         */
        private static $o_oes = array(
            'ech', 'embarg', 'her', 'potat', 'tomat', 'torped', 'vet'
        );

        /* Words that end in a and convert to ae in plural.
         */
        private static $a_ae = array(
            'alg', 'larv', 'nebul', 'vertebr'
        );

        /* Words that end in us and convert to i in plural.
         */
        private static $us_i = array(
            'alumn', 'bacill', 'foc', 'nucle', 'radi', 'stimul', 'termin'
        );

        /* Words that end in um and convert to a in plural.
         */
        private static $um_a = array(
            'addend', 'bacteri', 'dat', 'errat', 'medi', 'ov', 'strat'
        );

        /* Words that end in is and convert to es in plural.
         */
        private static $is_es = array(
            'analys', 'ax', 'bas', 'cris', 'diagnos', 'emphas', 'hypothes',
            'neuros', 'oas', 'parenthes', 'synops', 'thes'
        );

        /* Words that end in on and convert to a in plural.
         */
        private static $on_a = array(
            'criteri', 'phenomen', 'automat'
        );

        /**
         * Camel-case a string
         *
         * Converts a string from an_underscored_string to AnUnderscoredString.
         * It caches the results, so that subsequent calls to this function or
         * the inverse (decamelize()) will return consistent results. If the
         * situation would be ambiguous in either direction, the desired result
         * should be intentionally called first.
         *
         * @param string $str The string to camelize
         * @return string
         */
        public static function camelize($str) {
            if (array_key_exists($str, self::$camelized)) {
                return self::$camelized[$str];
            }

            $ary = explode('_', $str);
            $out = array();

            foreach ($ary as $word) {
                $upper = strtoupper(substr($word, 0, 1)) . substr($word, 1);
                array_push($out, $upper);
            }

            $camel = join('', $out);

            if (array_key_exists($camel, self::$decamelized)) {
                $f = __FUNCTION__;
                $e = self::$decamelized[$camel];

                throw new AmbiguousInflectionException($f, $str, $camel, $e);
            }

            self::$camelized[$str]     = $camel;
            self::$decamelized[$camel] = $str;

            return $camel;
        }

        /**
         * Convert a camel-cased string to an underscore-separated version
         *
         * Converts a string from ACamelCasedString to a_camel_cased_string.
         * It caches the results, so that subsequent calls to this function or
         * the inverse (camelize()) will return consistent results. It will
         * interpret multiple contiguous upper-case letters as being acronyms
         * and will group them in one word. This can lead to ambiguities with
         * assumptions from camelize(). If the situation would be ambiguous in
         * either direction, the desired result should be intentionally called
         * first.
         *
         * @param string $str The string to downcase
         * @return string
         */
        public static function decamelize($str) {
            if (array_key_exists($str, self::$decamelized)) {
                return self::$decamelized[$str];
            }

            $chars   = str_split($str, 1);
            $out     = '';
            $onupper = true;

            for ($x = 0; $x < count($chars); $x++) {
                if (!strlen($out)) {
                    $out .= strtolower($chars[$x]);
                }
                else if (preg_match('/[a-z]/', $chars[$x])) {
                    $out .= $chars[$x];
                    $onupper = false;
                }
                else if (preg_match('/[A-Z]/', $chars[$x])) {
                    if ((!$onupper) ||
                        ((count($chars) > $x) &&
                         preg_match('/[a-z]/', $chars[$x + 1]))) {
                        $out .= '_';
                    }

                    $out .= strtolower($chars[$x]);
                    $onupper = true;
                }
                else if (preg_match('/\s/', $chars[$x])) {
                    $out .= '_';
                }
            }

            if (array_key_exists($out, self::$camelized)) {
                $f = __FUNCTION__;
                $e = self::$camelized[$out];

                throw new AmbiguousInflectionException($f, $str, $out, $e);
            }

            self::$decamelized[$str] = $out;
            self::$camelized[$out]   = $str;

            return $out;
        }

        /**
         * Replace slightly-irregular suffixes
         *
         * private Inflection::_suf(String, String, Array, String, String)
         *
         * Searches through an array to see if a given suffix matches, and if
         * so replaces the suffix value with an output suffix. Otherwise it
         * returns null.
         *
         * @param string $str The original string
         * @param string $match The word stem
         * @param array $ary The list of possible words to match
         * @param string $suf The input suffix (being removed)
         * @param string $osuf The output suffix (being affixed)
         * @return string
         */
        private static function _suf($str, $match, $ary, $suf, $osuf) {
            foreach ($ary as $pat) {
                if (preg_match("/$pat$/", $match, $matches)) {
                    $pattern = "/(.*{$pat})$suf$/";
                    return preg_replace($pattern, '\1' . $osuf, $str);
                }
            }

            return null;
        }

        /**
         * Convert a singular word to a plural form
         *
         * Tries it's darnedest to replace the given string with a valid English
         * pluralization of said string. At the very least it'll add an s.
         *
         * @param string $str The word to pluralize
         * @return string
         */
        public static function pluralize($str) {
            // handle the most obviously irregular expressions, as they require
            // very little pattern matching.
            if (preg_match('/(^|.*_)([^_]+)$/', $str, $m)) {
                if (in_array($m[2], self::$uncountable)) {
                    return $str;
                }
                else if (array_key_exists($m[2], self::$irreg)) {
                    return $m[1] . self::$irreg[$m[2]];
                }
                else {
                    foreach (self::$suffixes as $sing => $plur) {
                        if (preg_match("/$sing$/", $m[2], $mm)) {
                            $pattern = "/(.*)$sing$/";
                            return preg_replace($pattern, '\1' . $plur, $str);
                        }
                    }
                }
            }

            // handle irregular pluralization
            $ret = null;
            if (preg_match('/(^|_)([^_]+)f(e?)$/', $str, $m)) {
                if ($m[3]) {
                    $ret = self::_suf($str, $m[2], self::$fe_ves, 'fe', 'ves');
                }
                else {
                    $ret = self::_suf($str, $m[2], self::$f_ves, 'f', 'ves');
                }
            }
            else if (preg_match('/(^|_)([^_]+)o$/', $str, $m)) {
                $ret = self::_suf($str, $m[2], self::$o_oes, 'o', 'oes');
            }
            else if (preg_match('/(^|_)([^_]+)a$/', $str, $m)) {
                $ret = self::_suf($str, $m[2], self::$a_ae, 'a', 'ae');
            }
            else if (preg_match('/(^|_)([^_]+)us$/', $str, $m)) {
                $ret = self::_suf($str, $m[2], self::$us_i, 'us', 'i');
            }
            else if (preg_match('/(^|_)([^_]+)um$/', $str, $m)) {
                $ret = self::_suf($str, $m[2], self::$um_a, 'um', 'a');
            }
            else if (preg_match('/(^|_)([^_]+)is$/', $str, $m)) {
                $ret = self::_suf($str, $m[2], self::$is_es, 'is', 'es');
            }
            else if (preg_match('/(^|_)([^_]+)on$/', $str, $m)) {
                $ret = self::_suf($str, $m[2], self::$on_a, 'on', 'a');
            }

            // this means we got an irregular pluralized result
            if ($ret !== null) {
                return $ret;
            }

            // by this point, we've tried all non-standard approaches. just do
            // regular English pluralization.
            if (preg_match('/(.*?)([aeiou]?)y$/', $str, $m)) {
                if ($m[2]) {
                    return "{$str}s";
                }
                else {
                    return "{$m[1]}ies";
                }
            }
            else if (preg_match('/(.*?)([aeiou]?)z$/', $str, $m)) {
                return "{$m[1]}{$m[2]}zzes";
            }
            else if (preg_match('/(.*)(s|ch|x)$/', $str, $m)) {
                return "{$m[1]}{$m[2]}es";
            }
            else {
                return "{$str}s";
            }
        }

        /**
         * Convert a plural word to a singular form
         *
         * Tries to singularize the given string into a standard English word.
         *
         * @param string $str The string to singularize
         * @return string
         */
        public static function singularize($str) {
            // handle the most irregular cases.
            if (preg_match('/(^|.*_)([^_]+)$/', $str, $m)) {
                if (in_array($m[2], self::$uncountable)) {
                    return $str;
                }
                else if (($idx = array_search($m[2], self::$irreg)) !== false) {
                    return $m[1] . $idx;
                }
                else {
                    foreach (self::$suffixes as $sing => $plur) {
                        if (preg_match("/$plur$/", $m[2], $mm)) {
                            $pattern = "/(.*)$plur$/";
                            return preg_replace($pattern, '\1' . $sing, $str);
                        }
                    }
                }
            }

            // handle other irregular rule-based singularization
            $ret = null;
            if (preg_match('/(^|_)([^_]+)ves$/', $str, $m)) {
                $ret = self::_suf($str, $m[2], self::$fe_ves, 'ves', 'fe');
                if ($ret !== null) {
                    return $ret;
                }

                $ret = self::_suf($str, $m[2], self::$f_ves, 'ves', 'f');
            }
            else if (preg_match('/(^|_)([^_]+)oes$/', $str, $m)) {
                $ret = self::_suf($str, $m[2], self::$o_oes, 'oes', 'o');
            }
            else if (preg_match('/(^|_)([^_]+)ae$/', $str, $m)) {
                $ret = self::_suf($str, $m[2], self::$a_ae, 'ae', 'a');
            }
            else if (preg_match('/(^|_)([^_]+)i$/', $str, $m)) {
                $ret = self::_suf($str, $m[2], self::$us_i, 'i', 'us');
            }
            else if (preg_match('/(^|_)([^_]+)a$/', $str, $m)) {
                $ret = self::_suf($str, $m[2], self::$um_a, 'a', 'um');
                if ($ret !== null) {
                    return $ret;
                }

                $ret = self::_suf($str, $m[2], self::$on_a, 'a', 'on');
            }
            else if (preg_match('/(^|_)([^_]+)es$/', $str, $m)) {
                $ret = self::_suf($str, $m[2], self::$is_es, 'es', 'is');
            }

            // we got a rule-based irregularity
            if ($ret !== null) {
                return $ret;
            }

            // otherwise try standard English rules to decode it.
            if (preg_match('/(.*)ies$/', $str, $m)) {
                return "{$m[1]}y";
            }
            else if (preg_match('/(.*)([aeiou])zzes$/', $str, $m)) {
                return "{$m[1]}{$m[2]}z";
            }
            else if (preg_match('/(.*)(s|ch|x)es$/', $str, $m)) {
                return $m[1] . $m[2];
            }
            else if (preg_match('/(.*)s$/', $str, $m)) {
                return $m[1];
            }
            else {
                // ideally we should never get here, because it means we failed
                // to find a way to singularize. we should return *something* in
                // any case, however.
                return $str;
            }
        }
    }

?>
