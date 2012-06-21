<?php

    list($startms, $starts) = explode(' ', microtime());

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

    $failed = array();

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

        $pass += ($opass > $oall ? $oall : $opass);
        $fail += $ofail;
        $all  += $oall;

        $name = "$fn ($oall subtests)";

        if (!is($opass, $oall, $name)) {
            array_push($failed, $fn);
        }
    }

    notate("finished testing files");
    is($pass, $all, "All $all subtests pass");
    $allpass = is(count($failed), 0, 'Test failures is empty');
    echo "\n";

    if (!$allpass) {
        notate("Failed test files:");
        foreach ($failed as $failure) {
            notate("  $failure");
        }
    }

    list($endms, $ends) = explode(' ', microtime());

    $slen  = ((integer) $ends) - ((integer) $starts);
    $mslen = ((double) $endms) - ((double) $startms);

    $dur = $slen + $mslen;

    notate("Tests ran in ${dur}s.");

?>
