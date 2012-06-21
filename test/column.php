<?php

    require_once('tap.php');
    require_once('../column.php');

    plan(27);

    $column = new Column('field', 'boolean', true, true);

    is($column->name(),        'field',   'Column name is correct');
    is($column->datatype(),    'boolean', 'Column datatype is correct');
    is($column->allow_null(),  true,      'Column nullable is correct');
    is($column->primary_key(), true,      'Column primary key is correct');

    // general-purpose process_value() checks
    is($column->process_value(''),   null, 'Setting blank string returns null');
    is($column->process_value(null), null, 'Setting null returns null');

    // general-purpose prep_for_database() checks
    is($column->prep_for_database(null),  null, 'Prepping null returns null');

    // general-purpose formify() checks
    is($column->formify(null), '', 'Form of null is blank');

    // boolean stringify() checks
    is($column->stringify(null),  '?',   'null stringifies to "?"');
    is($column->stringify(true),  'Yes', 'true stringifies to "Yes"');
    is($column->stringify(false), 'No',  'false stringifies to "No"');

    // boolean process_value() checks
    is($column->process_value(1),       true, 'Setting 1 returns true');
    is($column->process_value(true),    true, 'Setting true returns true');
    is($column->process_value('true'),  true, 'Setting "true" returns true');
    is($column->process_value(0),       false, 'Setting 0 returns false');
    is($column->process_value(false),   false, 'Setting false returns false');
    is($column->process_value('false'), false, 'Setting "false" returns true');

    // boolean prep_for_database() checks
    is($column->prep_for_database(true),  't',  'Prepping true returns "t"');
    is($column->prep_for_database(false), 'f',  'Prepping false returns "f"');

    // boolean formify() checks
    is($column->formify(true, true),  ' checked', 'Form comparison checks');
    is($column->formify(true, false), '',         'Form comparison unchecks');
    is($column->formify(true),  ' checked', 'Form true value checks');
    is($column->formify(false), '',         'Form false value unchecks');

    $type = 'double precision';
    $column = new Column('field_again', $type, false, false);

    is($column->name(),        'field_again', 'Column name is correct');
    is($column->datatype(),    $type,         'Column datatype is correct');
    is($column->allow_null(),  false,         'Column nullable is correct');
    is($column->primary_key(), false,         'Column primary key is correct');

?>
