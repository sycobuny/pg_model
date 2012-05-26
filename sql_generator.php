<?php

    foreach (
        array(
            'expression', 'function', 'aliasable_expression',
            'table_expression', 'table', 'join_expression', 'set_function',
            'query', 'cte', 'value_expression', 'identifier',
            'qualified_identifier', 'string', 'value_function',
            'literal_expression', 'set_modifying_expression',
            'conditional_expression', 'limiting_expression',
            'ordering_expression'
        ) as $__) {
        include_once(
            join(DIRECTORY_SEPARATOR,
                 array(dirname(__FILE__), 'sql_generator', "$__.php"))
        );
    }

?>
