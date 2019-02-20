## moreticket test suite

To run the moreticket test suite you need

* [atoum](http://atoum.org/)

Installing composer development dependencies
----------------------

Run the **composer install** command without --no-dev option in the top of moreticket tree:

```bash
$ composer install -o

Loading composer repositories with package information
Installing dependencies (including require-dev) from lock file
[...]
Generating optimized autoload files
```

Creating a dedicated database
-----------------------------

Use the **CliInstall** script to create a new database,
only used for the test suite, using the `--tests` option:

```bash
$ php scripts/cliinstall.php --db=glpitests --user=root --pass=xxxx --tests
Connect to the DB...
Create the DB...
Save configuration file...
Load default schema...
Done
```

The configuration file is saved as `tests/config_db.php`.

The database is created using the default schema for current version.

If you need to recreate the database (e.g. for a new schema), you need to run
**CliInstall** again with the `--force` option.


Changing database configuration
-------------------------------

Using the same database than the web application is not recommended. Use the `tests/config_db.php` file to adjust connection settings.

Running the test suite
----------------------

There are two directories for tests:
- `tests/units` for main core tests;
- `tests/api` for API tests.

You can choose to run tests on a whole directory, on any file, or on any \<class::method>. You have to specify a bootstrap file each time:

```bash
$ atoum -bf tests/bootstrap.php -mcn 1 -d tests/units/
[...]
$ atoum -bf tests/bootstrap.php -f tests/units/Html.php
[...]
$ atoum -bf tests/bootstrap.php -f tests/units/Html.php -m tests\units\Html::testConvDateTime
```
