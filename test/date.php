<?php

    require_once('tap.php');
    require_once('../date.php');

    plan(7);

    $date = new Date(2012, 3, 5);

    is($date->year(),  2012, 'Year is correct');
    is($date->month(), 3,    'Month is correct');
    is($date->day(),   5,    'Day is correct');

    $date = Date::parse('2011-10-25');

    is((string) get_class($date), 'Date', 'Date::parse() returns a Date');
    is($date->year(),  2011, 'Parsed year is correct');
    is($date->month(), 10,   'Parsed month is correct');
    is($date->day(),   25,   'Parsed day is correct');

?>
