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
        notate("planned to run $tests tests");
        echo "1..$tests\n";
    }

    function finalize() {
        global $__TEST__COUNT__, $__TEST__SUCCESS__, $__TEST__FAILURE__,
               $__EXPECTED__TESTS__;

        if ($__EXPECTED__TESTS__ == 'none')
            exit($__TEST__FAILURE__);

        $cnt  = $__TEST__COUNT__;
        $pass = $__TEST__SUCCESS__;
        $fail = $__TEST__FAILURE__;
        $exp  = $__EXPECTED__TESTS__;
        $all  = $cnt === $exp;

        if ($exp !== null) {
            if (!$all)
                if ($exp > $cnt)
                    notate("planned $exp tests but only executed $cnt");
                else
                    notate("planned $exp tests and executed $cnt");
            if ($fail)
                notate("$fail tests failed, $pass passed");
        }
        else {
            notate("no test plan - $pass passed, $fail failed");
        }

        if (!$all)
            exit(255);
        else
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
            notate("expected '$right', got '$left'");
        }

        return $ret;
    }

    function is_not($left, $right, $message = null) {
        $ret = not_ok($left === $right, $message);
        if (!$ret) {
            notate("got '$left', should be not be '$right'");
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
                notate("# expected to raise '$exception', got '$class'");
                return false;
            }
            return true;
        }

        ok(false, $message);
        notate("expected to raise '$exception', but nothing raised");
        return false;
    }

    function raises_nothing($callback, $args, $message = null) {
        try {
            call_user_func_array($callback, $args);
        }
        catch (Exception $e) {
            $class = (string) get_class($e);
            ok(false, $message);
            notate("expected nothing raised, got '$class'");
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
                    notate("bad test! $name test $test (expected $current)!");
            }
            else if (preg_match('/^not ok (\d+)/', $line, $matches)) {
                $current++;
                $fail++;

                $test = $matches[1];
                if ($test != $current)
                    notate("bad test! $name test $test (expected $current)!");
            }
            else if (!preg_match('/^\#/', $line)) {
                notate("unknown output format $line ($name:$lineno)");
            }
        }

        notate("$name $pass/$count");

        return "$pass/$fail/$count";
    }

    register_shutdown_function('finalize');

?>
