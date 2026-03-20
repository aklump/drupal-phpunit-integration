<?php

namespace AKlump\Drupal\PHPUnit\Integration\Helper;

class GetUserHelpForMissingSimpleTestDB {

  public function __invoke(string $phpunit_xml_path): string {
    return <<<EOD
    SIMPLETEST_DB is not set and could not be discovered.
    
    1. Open $phpunit_xml_path
    2. Find <env name="SIMPLETEST_DB" value=""/>
    1. If not found, add it as `<php><env.../></php>`
    1. Set the value to the database connection string like this:
    
       <env name="SIMPLETEST_DB" value='mysql://username:password@localhost/databasename#table_prefix'/>

    EOD;
  }
}
