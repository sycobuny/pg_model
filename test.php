<?php

    include_once('test/tap.php');
    no_plan();

    $pid = posix_getpid();
    $cmd = "lsof -p $pid | grep txt | head -n1 | awk '{print \$9 }'";
    $bin = exec($cmd);

    $cwd = dirname(__FILE__);
    $td  = join(DIRECTORY_SEPARATOR, array($cwd, 'test'));
    $dir = opendir($td);

    $tdq = '"' . preg_replace('/\"/', '\\"', $td) . '"';

    $pass = 0;
    $fail = 0;
    $all  = 0;

    while ($fn = readdir($dir)) {
        if ($fn == '.' || $fn == '..' || $fn == 'tap.php')
            continue;

        $file = join(DIRECTORY_SEPARATOR, array($td, $fn));
        $file = '"' . preg_replace('/\"/', '\\"', $file) . '"';
        $exec = "(cd $tdq && $bin $file)";

        ob_start();
        system($exec, $res);
        $test = ob_get_contents();
        ob_end_clean();

        $out = parse_contents($fn, $test);
        preg_match('/^(\d+)\/(\d+)\/(\d+)$/', $out, $matches);

        $opass = $matches[1];
        $ofail = $matches[2];
        $oall  = $matches[3];

        $pass += $opass;
        $fail += $ofail;
        $all  += $oall;

        ok($opass == $oall, $fn);
    }

    is($pass, $all, "All $all Tests Pass");

?>
