<?php

/*

Connection Information for the database
$def_coy - the default company that is pre-selected on login

'host' - the computer ip address or name where the database is. The default is 'localhost' assuming that the web server is also the sql server.

'dbuser' - the user name under which the company database should be accessed.
  NB it is not secure to use root as the dbuser with no password - a user with appropriate privileges must be set up.

'dbpassword' - the password required for the dbuser to authorise the above database user.

'dbname' - the name of the database as defined in the RDMS being used. Typically RDMS allow many databases to be maintained under the same server.
'tbpref' - prefix on table names, or '' if not used. Always use non-empty prefixes if multiply company use the same database.

*/


$def_coy = 0;

$tb_pref_counter = 33;

$db_connections = array (
  0 => 
  array (
    'name' => 'RPL',
    'host' => 'localhost',
    'dbname' => 'cloudso1_rpl',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_rpl',
    'dbpassword' => 'myz47m',
  ),
 1 => 
  array (
    'name' => 'HOC',
    'host' => 'localhost',
    'dbname' => 'cloudso1_hoc',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'root',
    'dbpassword' => '',
  ),
 2 => 
  array (
    'name' => 'AIPL',
    'host' => 'localhost',
    'dbname' => 'cloudso1_aipl',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_aipl',
    'dbpassword' => 'myz47m',
  ),
 3 => 
  array (
    'name' => 'AIPLTAX',
    'host' => 'localhost',
    'dbname' => 'cloudso1_aipl_tax',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_aipl_ta',
    'dbpassword' => 'myz47m',
  ),
4 => 
  array (
    'name' => 'SKF',
    'host' => 'localhost',
    'dbname' => 'cloudso1_sakuf',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_sakuf',
    'dbpassword' => 'myz47m',
  ),
5 => 
  array (
    'name' => 'FLC',
    'host' => 'localhost',
    'dbname' => 'cloudso1_flc',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_flc',
    'dbpassword' => 'myz47m',
  ),
6 => 
  array (
    'name' => 'KBS',
    'host' => 'localhost',
    'dbname' => 'cloudso1_kbs',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_kbs',
    'dbpassword' => 'myz47m',
  ),
7 => 
  array (
    'name' => 'RPLT',
    'host' => 'localhost',
    'dbname' => 'cloudso1_rpl',
    'collation' => 'utf8_xx',
    'tbpref' => '1_',
    'dbuser' => 'cloudso1_rpl',
    'dbpassword' => 'myz47m',
  ),
8 => 
  array (
    'name' => 'DEMO',
    'host' => 'localhost',
    'dbname' => 'cloudso1_demo',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_demo',
    'dbpassword' => 'myz47m',
  ),
9 => 
  array (
    'name' => 'AOIS',
    'host' => 'localhost',
    'dbname' => 'cloudso1_aois',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_aois',
    'dbpassword' => 'myz47m',
  ),
10 => 
  array (
    'name' => 'MGI',
    'host' => 'localhost',
    'dbname' => 'cloudso1_mgi',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_mgi',
    'dbpassword' => 'myz47m',
  ),
11 =>
  array (
    'name' => 'AKR',
    'host' => 'localhost',
    'dbname' => 'cloudso1_akr',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_akr',
    'dbpassword' => 'myz47m',
  ),
12 =>
  array (
    'name' => 'BNT',
    'host' => 'localhost',
    'dbname' => 'cloudso1_bnt',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_bnt',
    'dbpassword' => 'myz47m',
  ),
13 =>
  array (
    'name' => 'SE1',
    'host' => 'localhost',
    'dbname' => 'cloudso1_se1',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_se1',
    'dbpassword' => 'myz47m',
  ),
14 =>
  array (
    'name' => 'NKR',
    'host' => 'localhost',
    'dbname' => 'cloudso1_nkr',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_nkr',
    'dbpassword' => 'myz47m',
  ),
   15 => 
  array (
    'name' => 'RPL1',
    'host' => 'localhost',
    'dbname' => 'cloudso1_rplbeta',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_rplbeta',
    'dbpassword' => 'myz47m',
  ),
  16 => 
  array (
    'name' => 'EURO',
    'host' => 'localhost',
    'dbname' => 'cloudso1_euro',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_euro',
    'dbpassword' => 'myz47m',
  ),
  17 => 
  array (
    'name' => 'WSL',
    'host' => 'localhost',
    'dbname' => 'cloudso1_wsl',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_wsl',
    'dbpassword' => 'myz47m',
  ),
  18 => 
  array (
    'name' => 'JASCO',
    'host' => 'localhost',
    'dbname' => 'cloudso1_jasco',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_jasco',
    'dbpassword' => 'myz47m',
  ),
    19 => 
  array (
    'name' => 'WTRUCK',
    'host' => 'localhost',
    'dbname' => 'cloudso1_wtruck',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_wtruck',
    'dbpassword' => 'myz47m',
  ),
    20 => 
  array (
    'name' => 'AMAC',
    'host' => 'localhost',
    'dbname' => 'cloudso1_amac',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_amac',
    'dbpassword' => 'myz47m',
  ),
    21 => 
  array (
    'name' => 'ATQC',
    'host' => 'localhost',
    'dbname' => 'cloudso1_atqc',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_atqc',
    'dbpassword' => 'myz47m',
  ),
    22 => 
  array (
    'name' => 'CHI',
    'host' => 'localhost',
    'dbname' => 'cloudso1_chi',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_chi',
    'dbpassword' => 'myz47m',
  ),  
  23 => 
  array (
    'name' => 'PTI',
    'host' => 'localhost',
    'dbname' => 'cloudso1_pti',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_pti',
    'dbpassword' => 'myz47m',
  ), 
   24 => 
  array (
    'name' => 'MEI',
    'host' => 'localhost',
    'dbname' => 'cloudso1_mei',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_mei',
    'dbpassword' => 'myz47m',
  ), 
    25 => 
  array (
    'name' => 'RPLB',
    'host' => 'localhost',
    'dbname' => 'cloudso1_rplbeta',
    'collation' => 'utf8_xx',
    'tbpref' => '1_',
    'dbuser' => 'cloudso1_rplbeta',
    'dbpassword' => 'myz47m',
  ),
    26 => 
  array (
    'name' => 'RPL2',
    'host' => 'localhost',
    'dbname' => 'cloudso1_rpl2',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_rpl2',
    'dbpassword' => 'myz47m',
  ),  
  27 => 
  array (
    'name' => 'HOC2',
    'host' => 'localhost',
    'dbname' => 'cloudso1_hoc2',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_hoc2',
    'dbpassword' => 'myz47m',
  ),
  28 => 
  array (
    'name' => 'MTG',
    'host' => 'localhost',
    'dbname' => 'cloudso1_mtg',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_mtg',
    'dbpassword' => 'myz47m',
  ),
  29 => 
  array (
    'name' => 'COMP1',
    'host' => 'localhost',
    'dbname' => 'cloudso1_comp1',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_comp1',
    'dbpassword' => 'myz47m',
  ),
  30 => 
  array (
    'name' => 'IMEC',
    'host' => 'localhost',
    'dbname' => 'cloudso1_imec',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_imec',
    'dbpassword' => 'myz47m',
  ),
  31 => 
  array (
    'name' => 'COMP3',
    'host' => 'localhost',
    'dbname' => 'cloudso1_comp3',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_comp3',
    'dbpassword' => 'myz47m',
  ),
  32 => 
  array (
    'name' => 'COMP4',
    'host' => 'localhost',
    'dbname' => 'cloudso1_comp4',
    'collation' => 'utf8_xx',
    'tbpref' => '0_',
    'dbuser' => 'cloudso1_comp4',
    'dbpassword' => 'myz47m',
  ),
  
);