PGModel
=======

A library for providing a simple base Model class to build queries in PostgreSQL
without having to do a lot of drudgery.

For Example
-----------

```php
<?php
    class Account extends Model {
        public function debit($amt) {
            $this->balance = $this->balance - (double) $amt;
        }
    }

    // ...

    $account_id = (integer) $_GET['id'];
    $debit      = (double)  $_GET['debit'];

    $account = new Account();
    $account->load($account_id);
    $account->debit($debit);
    $account->save();
```

But Why?
--------

"PHP has PDO", "There's cake...", etc.

I don't, honestly, know PHP all that well anymore and I needed something to bury
my head deep into it. Also, I'm a big fan of [Sequel][sequel] for Ruby, and how
it intuits most things based on the database structure, and doesn't require you
to write hardly any method definitions yourself to fully query the database.

It also turns out that, there are OSes which have PHP 5.1 as their primary PHP
version, so I needed something that would work on such outdated software, so
there's that, too.

"Hey that sounds neat" And Other Lies
-------------------------------------

If you actually think this sounds cool, then feel free to contribute. Right now
it's in its infancy, and only supports basic loading, saving, and associations.
I also have had to include custom class introspection as PHP 5.1 doesn't have it
and that is quite painfully fragile at this point. Finally, as the name implies,
it's geared completely towards PostgreSQL. I don't have any objections to adding
MySQL support, but it doesn't have any value to me so I'm probably not going to
do it.

[sequel]: http://sequel.rubyforge.org/ "Sequel - The Database Toolkit for Ruby"
