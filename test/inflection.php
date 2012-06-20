<?php

    require_once('tap.php');
    require_once('../inflection.php');
    require_once('../exceptions.php');

    $tests = 5;

    $test_plur = array(
        // [singular, plural, test name]
        array('equipment',   'equipment',    'uncountable'),
        array('mouse',       'mice',         'irregular - suffix as word'),
        array('doormouse',   'doormice',     'irregular - suffix as suffix'),
        array('ox',          'oxen',         'irregular'),
        array('vortex',      'vortices',     'irregular - ix/ex'),
        array('calf',        'calves',       'irregular - f  => ves'),
        array('knife',       'knives',       'irregular - fe => ves'),
        array('echo',        'echoes',       'irregular - o  => oes'),
        array('larva',       'larvae',       'irregular - a  => ae'),
        array('alumnus',     'alumni',       'irregular - us => i'),
        array('addendum',    'addenda',      'irregular - um => a'),
        array('analysis',    'analyses',     'irregular - is => es'),
        array('some_person', 'some_people',  'separates words'),
        array('solliloquy',  'solliloquys',  'regular - ends in [vowel]y'),
        array('symphony',    'symphonies',   'regular - ends in [consonant]y'),
        array('quiz',        'quizzes',      'regular - ends in [vowel]z'),
        array('church',      'churches',     'regular - ends in s-like sound'),
        array('cow',         'cows',         'regular'),
    );

    $test_sing = array(
        // [plural, singular, test name]
        array('fish',        'fish',       'uncountable'),
        array('lice',        'louse',      'irregular - suffix as word'),
        array('barklice',    'barklouse',  'irregular - suffix as suffix'),
        array('children',    'child',      'irregular'),
        array('vortices',    'vortex',     'irregular - ix/ex'),
        array('wolves',      'wolf',       'irregular - ves => f'),
        array('wives',       'wife',       'irregular - ves => fe'),
        array('vertebrae',   'vertebra',   'irregular - ae  => a'),
        array('nuclei',      'nucleus',    'irregular - i   => us'),
        array('errata',      'erratum',    'irregular - um  => a'),
        array('hypotheses',  'hypothesis', 'irregular - es  => is'),
        array('back_teeth',  'back_tooth', 'separates words'),
        array('solliloquys', 'solliloquy', 'regular - ends in [vowel]y'),
        array('harmonies',   'harmony',    'regular - ends in [cosonant]ies'),
        array('boxes',       'box',        'regular - ends in s-like sound'),
        array('slips',       'slip',       'regular'),
    );

    # plan our tests
    $tests += count($test_plur) + count($test_sing);
    plan($tests);

    // test the pluralize() method
    foreach ($test_plur as $t) {
        list($s, $p, $m) = $t;
        $m = sprintf('pluralize("%s") - %s', $s, $m);
        is(Inflection::pluralize($s), $p, $m);
    }

    // test the singularize() method
    foreach ($test_sing as $t) {
        list($p, $s, $m) = $t;
        $m = sprintf('singularize("%s") - %s', $p, $m);
        is(Inflection::singularize($p), $s, $m);
    }

    // test the camelize() method
    is(Inflection::camelize('test_camelize'), 'TestCamelize',
       'camelize("test_camelize") - upper-cases a standard string');
    raises(array('Inflection', 'camelize'), array('Test_camelize'),
           'AmbiguousInflectionException',
           'camelize("Test_camelize") - throws error with multiple results');

    // test the decamelize() method
    is(Inflection::decamelize('TestDeCamelize'), 'test_de_camelize',
       'decamelize("TestDeCamelize") - lower-cases a standard string');
    is(Inflection::decamelize('TestABDeCamelize'), 'test_ab_de_camelize',
       'decamelize("TestABDeCamelize") - lower-cases an abbreviated string');
    is(Inflection::camelize('test_ab_de_camelize'), 'TestABDeCamelize',
       'camelize("test_ab_de_camelize") - caches reverse of abbreviated ' .
       'strings');

?>
