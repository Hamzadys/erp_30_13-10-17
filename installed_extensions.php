<?php

/* List of installed additional extensions. If extensions are added to the list manually
	make sure they have unique and so far never used extension_ids as a keys,
	and $next_extension_id is also updated. More about format of this file yo will find in 
	FA extension system documentation.
*/

$next_extension_id = 21; // unique id for next installed extension

$installed_extensions = array (
  7 => 
  array (
    'name' => 'Inventory Items CSV Import',
    'package' => 'import_items',
    'version' => '2.4.0-2',
    'type' => 'extension',
    'active' => false,
    'path' => 'modules/import_items',
  ),
  8 => 
  array (
    'name' => 'Annual balance breakdown report',
    'package' => 'rep_annual_balance_breakdown',
    'version' => '2.4.0-2',
    'type' => 'extension',
    'active' => false,
    'path' => 'modules/rep_annual_balance_breakdown',
  ),
  9 => 
  array (
    'name' => 'Annual expense breakdown report',
    'package' => 'rep_annual_expense_breakdown',
    'version' => '2.4.0-1',
    'type' => 'extension',
    'active' => false,
    'path' => 'modules/rep_annual_expense_breakdown',
  ),
  10 => 
  array (
    'name' => 'Dated Stock Sheet',
    'package' => 'rep_dated_stock',
    'version' => '2.4.0-1',
    'type' => 'extension',
    'active' => false,
    'path' => 'modules/rep_dated_stock',
  ),
  11 => 
  array (
    'name' => 'Inventory History',
    'package' => 'rep_inventory_history',
    'version' => '2.4.0-1',
    'type' => 'extension',
    'active' => false,
    'path' => 'modules/rep_inventory_history',
  ),
  13 => 
  array (
    'name' => 'Sales Summary Report',
    'package' => 'rep_sales_summary',
    'version' => '2.4.0-1',
    'type' => 'extension',
    'active' => false,
    'path' => 'modules/rep_sales_summary',
  ),
  14 => 
  array (
    'name' => 'Requisitions',
    'package' => 'requisitions',
    'version' => '2.4.0-2',
    'type' => 'extension',
    'active' => false,
    'path' => 'modules/requisitions',
  ),
  15 => 
  array (
    'name' => 'Company Dashboard',
    'package' => 'dashboard',
    'version' => '2.3.15-4',
    'type' => 'extension',
    'active' => false,
    'path' => 'modules/dashboard',
  ),
  16 => 
  array (
    'name' => 'Import Transactions',
    'package' => 'import_transactions',
    'version' => '2.4.0-2',
    'type' => 'extension',
    'active' => false,
    'path' => 'modules/import_transactions',
  ),
  17 => 
  array (
    'name' => 'Import Multiple Journal Entries',
    'package' => 'import_multijournalentries',
    'version' => '2.4.0-1',
    'type' => 'extension',
    'active' => false,
    'path' => 'modules/import_multijournalentries',
  ),
  18 => 
  array (
    'name' => 'Cash Flow Statement Report',
    'package' => 'rep_cash_flow_statement',
    'version' => '2.4.0-1',
    'type' => 'extension',
    'active' => false,
    'path' => 'modules/rep_cash_flow_statement',
  ),
  19 => 
  array (
    'name' => 'Bank Statement w/ Reconcile',
    'package' => 'rep_statement_reconcile',
    'version' => '2.4.0-1',
    'type' => 'extension',
    'active' => false,
    'path' => 'modules/rep_statement_reconcile',
  ),
  20 => 
  array (
    'name' => 'Tax inquiry and detailed report on cash basis',
    'package' => 'rep_tax_cash_basis',
    'version' => '2.4.0-1',
    'type' => 'extension',
    'active' => false,
    'path' => 'modules/rep_tax_cash_basis',
  ),
);
