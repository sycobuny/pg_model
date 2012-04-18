<?php

    /**
     * get_called_class()
     *
     * Defines a hack around the lack of late static binding in PHP <5.3. Only
     * defines it if it doesn't already exist (thus future-proofing against
     * upgrades).
     *
     * NB: This code does not work if multiple calls are made on the same line.
     * Use with care.
     *
     * Pulled from PHP user comments:
     * http://www.php.net/manual/en/function.get-called-class.php#107445
     *
     * @return string
     */
    if (!function_exists('get_called_class')) {
         function get_called_class() {
             $btrace  = debug_backtrace();
             $frame   = count($btrace) - 1;
             $matches = array();

             foreach ($btrace as $f)
                 if (array_key_exists('object', $f) && $f['object'])
                     return (string) get_class($f['object']);

             while (empty($matches) && ($frame > -1)) {
                 $lines  = file($btrace[$frame]['file']);
                 $caller = $lines[ $btrace[$frame]['line'] - 1 ];
                 $func   = $btrace[$frame]['function'];

                 if ($func == '__construct') {
                     $pat = '/new\s+([a-zA-Z0-9_]+)\s*\(/';

                     if (preg_match($pat, $caller, $matches))
                         break;
                 }

                 $pat = "/(\\\$?[a-zA-Z0-9_]+)\\s*(::|->)\\s*$func\\s*\\(/";
                 preg_match($pat, $caller, $matches);

                 $frame--;
             }

             // stop notices for undefined indexes
             if (!isset($matches[1]))
                 $matches[1] = null;

             // we've got an object trying to call a static method, so it's at
             // least one layer out.
             if (preg_match('/^\$/', $matches[1])) {
                 $obj = $btrace[$frame + 1]['object'];
                 return (string) get_class($obj);
             }

             if ($matches[1] == 'self') {
                 $line = $btrace[$frame]['line'] - 1;
                 $pat  = '/class[\s]+(.+?)[\s]+/si';

                 while (($line > 0) &&
                        (strpos($lines[$line], 'class') === false))
                     $line--;

                 preg_match($pat, $lines[$line], $matches);
             }

             if ($matches[1])
                 return $matches[1];
             else
                 trigger_error('Class name was not specified and could not ' .
                               'guess it', E_USER_ERROR);
         }
    }

?>
