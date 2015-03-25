<?php

class HelpController extends Controller
{
	public function indexAction()
	{
		return <<<USAGE
Usage:
generator.php -c=command/subcommand options
Commands and corresponding subcommands:
help
show
\ttables
\ttable
fill
\ttable

Options:
-u username
-p password
-h host
-n database name
-t table name
-s show generated data
-r rows count

USAGE;
	}
}
