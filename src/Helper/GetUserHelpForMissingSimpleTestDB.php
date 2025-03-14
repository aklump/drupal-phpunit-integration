<?php

namespace AKlump\Drupal\PHPUnit\Integration\Helper;

class GetUserHelpForMissingSimpleTestDB {

  public function __invoke(): string {
    return <<<EOD
    SIMPLETEST_DB is not set.
    
    There are several solutions:
    
    Hard-coded:
    1. In phpunit.xml, set the SIMPLETEST_DB environment variable.
    1. e.g., export SIMPLETEST_DB=mysql://username:password@localhost/databasename#table_prefix
    
    Environment variables:
    
    1. If you are using a .env file, add SIMPLETEST_DB=mysql://username:password@localhost/databasename#table_prefix
    EOD;
  }
}
