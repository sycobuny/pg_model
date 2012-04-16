Where Do We Go From Here?
=========================

So, you think PGModel sounds cool? To that I have to say first, "what is wrong
with you?" Then, I follow it up quickly by "er, I mean, welcome aboard!"

There's lots to do to make this actually useful. Feel free to tackle anything
here, or maybe just add a feature here I missed. I'll try to sync it up with the
issues tracker on GitHub but this should do for people who just keep a copy of
the source code lying around.

Improved Introspection
----------------------

This is a Pretty Big Deal™, because at some point someone will want to write a
static method call to a Model class inside of an instance method call from some
other class or any of 50 other edge cases which are currently unaccounted for.
While it's nice to think that modern PHP has Reflection and friends, I'm really
going for dealing with 5.1 here.

* `get_called_class()`
  This should, without fail, return The Right Thing. Right now it only does if
  you have one level of method calls.

Improved Database Intuition
---------------------------

Right now you still have to describe certain features of the database. With some
improved introspection (see above) we can hopefully do away with most/all of
that.

* `Model::table()`
  Ideally, models should be named after their tables. The "accounts" table
  begets the "Account" class (or vice versa), for instance. Right now you have
  to call Model::associate_table() for each model and be very explicit about the
  class that it's associated to. Bad, bad, bad.
* `Model::one_to_many() and friends`
  Like Model::associate_table(), you have to be painfully explicit about the
  associated classes, AS WELL as the classes doing the associating. While I
  don't have an objection to declaring associations manually (though it would be
  REALLY cool and not at all impossible to intuit them from foreign keys), I do
  find it tedious to say 'this is the associated model, associated by whatever
  table to this main model' for every single thing.
* `Inflection`
  This is probably the biggest thing for database intuition, the first domino
  that must fall to set the others in motion. Because English is so weird with
  pluralization, and we need to deal with camel-casing/etc., we need to be able
  to inflect words in some sort of library. There are many implementations out
  there, so maybe this can be borrowed from them and credited, depending on the
  license?
