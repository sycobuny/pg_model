<?php

    foreach (array('ambiguous_inflection', 'bad_column', 'bad_primary_key',
                   'database', 'no_such_row') as $fn) {
        $fn = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'exceptions',
                                              "{$fn}_exception.php"));
        include_once($fn);
    }

?>
