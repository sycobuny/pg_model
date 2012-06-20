<?php

    $__TEST__COUNT__     = 0;
    $__TEST__SUCCESS__   = 0;
    $__TEST__FAILURE__   = 0;
    $__EXPECTED__TESTS__ = null;

    function no_plan() {
        global $__EXPECTED__TESTS__;
        $__EXPECTED__TESTS__ = 'none';
    }

    function plan($tests) {
        global $__EXPECTED__TESTS__;

        $__EXPECTED__TESTS__ = $tests;
        echo "# planned to run $tests tests\n";
        echo "1..$tests\n";
    }

    function finalize() {
        global $__TEST__COUNT__, $__TEST__SUCCESS__, $__TEST__FAILURE__,
               $__EXPECTED__TESTS__;

        if ($__EXPECTED__TESTS__ == 'none')
            exit(0);

        $cnt  = $__TEST__COUNT__;
        $pass = $__TEST__SUCCESS__;
        $fail = $__TEST__FAILURE__;
        $exp  = $__EXPECTED__TESTS__;
        $all  = $cnt === $exp;

        if ($exp !== null) {
            if (!$all)
                echo "# planned $exp tests but only executed $cnt\n";
            if ($fail)
                echo "# $fail tests failed, $pass passed\n";
        }
        else {
            echo "# no test plan - $pass passed, $fail failed\n";
        }

        exit($fail);
    }

    function notate($message) {
        $lines = explode("\n", $message);
        foreach ($lines as $line) {
            echo "# $line\n";
        }
    }

    function ok($cond, $message = null) {
        global $__TEST__COUNT__, $__TEST__SUCCESS__, $__TEST__FAILURE__;
        $__TEST__COUNT__++;

        if ($message) {
            $lines   = explode("\n", $message);
            $message = " - " . $lines[0];
        }

        if ($cond) {
            $__TEST__SUCCESS__++;
            echo "ok $__TEST__COUNT__$message\n";
        }
        else {
            $__TEST__FAILURE__++;
            echo "not ok $__TEST__COUNT__$message\n";
        }

        return $cond;
    }

    function not_ok($cond, $message = null) {
        return ok(!$cond, $message);
    }

    function is($left, $right, $message = null) {
        $ret = ok($left === $right, $message);
        if (!$ret) {
            echo "# expected '$right', got '$left'\n";
        }

        return $ret;
    }

    function is_not($left, $right, $message = null) {
        $ret = not_ok($left === $right, $message);
        if (!$ret) {
            echo "# got '$left', should be not be '$right'\n";
        }

        return $ret;
    }

    function raises($callback, $args, $exception, $message = null) {
        try {
            call_user_func_array($callback, $args);
        }
        catch (Exception $e) {
            if (!ok(is_a($e, $exception), $message)) {
                $class = (string) get_class($e);
                echo "# expected to raise '$exception', got '$class'\n";
                return false;
            }
            return true;
        }

        ok(false, $message);
        echo "# expected to raise '$exception', but nothing raised\n";
        return false;
    }

    function raises_nothing($callback, $args, $message = null) {
        try {
            call_user_func_array($callback, $args);
        }
        catch (Exception $e) {
            $class = (string) get_class($e);
            ok(false, $message);
            echo "# expected nothing raised, got '$class'\n";
            return false;
        }

        return ok(true, $message);
    }

    function parse_contents($name, $tests) {
        $lines   = explode("\n", $tests);
        $current = 0;
        $pass    = 0;
        $fail    = 0;

        foreach ($lines as $lineno => $line) {
            if ($line == '')
                continue;
            else if (preg_match('/^(\d+)\.\.(\d+)$/', $line, $matches)) {
                $from = $matches[1];
                $to   = $matches[2];

                $count = ($to - $from) + 1;
            }
            else if (preg_match('/^ok (\d+)/', $line, $matches)) {
                $current++;
                $pass++;

                $test = $matches[1];
                if ($test != $current)
                    echo "# bad test! $name test $test (expected $current)!\n";
            }
            else if (preg_match('/^not ok (\d+)/', $line, $matches)) {
                $current++;
                $fail++;

                $test = $matches[1];
                if ($test != $current)
                    echo "# bad test! $name test $test (expected $current)!\n";
            }
            else if (!preg_match('/^\#/', $line)) {
                echo "# unknown output format $line ($name:$lineno)\n";
            }
        }

        echo "# $name $pass/$count\n";

        if ($pass > $count)
            $pass = $count;

        return "$pass/$fail/$count";
    }

    register_shutdown_function('finalize');

?>
