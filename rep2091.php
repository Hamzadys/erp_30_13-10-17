<?php

$page_security = $_POST['PARAM_0'] == $_POST['PARAM_1'] ?
	'SA_SUPPTRANSVIEW' : 'SA_SUPPBULKREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Purchase Orders
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/db/crm_contacts_db.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");

//----------------------------------------------------------------------------------------------------

print_po();

//----------------------------------------------------------------------------------------------------
function get_po($order_no)
{
	$sql = "SELECT ".TB_PREF."purch_orders.*, ".TB_PREF."suppliers.supp_name,".TB_PREF."suppliers.ntn_no,  "
		.TB_PREF."suppliers.supp_account_no,".TB_PREF."suppliers.tax_included,".TB_PREF."suppliers.gst_no AS tax_id,
   		".TB_PREF."suppliers.curr_code, ".TB_PREF."suppliers.payment_terms, ".TB_PREF."locations.location_name,
   		".TB_PREF."suppliers.address, ".TB_PREF."suppliers.contact, ".TB_PREF."suppliers.tax_group_id
		FROM ".TB_PREF."purch_orders, ".TB_PREF."suppliers, ".TB_PREF."locations
		WHERE ".TB_PREF."purch_orders.supplier_id = ".TB_PREF."suppliers.supplier_id
		AND ".TB_PREF."locations.loc_code = into_stock_location
		AND ".TB_PREF."purch_orders.order_no = ".db_escape($order_no);
	$result = db_query($sql, "The order cannot be retrieved");
	return db_fetch($result);
}

function get_tax_rate_1($id)
{
	$sql = "SELECT * FROM ".TB_PREF."tax_types
	 		WHERE id = ".db_escape($id);
	$result = db_query($sql, 'error');
	return db_fetch($result);
}

function get_tax_group_items($id)
{
	$sql = "SELECT * FROM ".TB_PREF."tax_group_items
	 		WHERE tax_group_id = ".db_escape($id);
	$result = db_query($sql, 'error');
	return db_fetch($result);
}

function get_tax_rate_group()
{
	$sql = "SELECT ".TB_PREF."tax_groups.name FROM ".TB_PREF."tax_groups
	 		WHERE ".TB_PREF."tax_groups.id = 3";
	$result = db_query($sql, 'error');
	return $result;
}

function get_po_details($order_no)
{
	$sql = "SELECT ".TB_PREF."purch_order_details.*, units, ".TB_PREF."stock_master.tax_type_id
		FROM ".TB_PREF."purch_order_details
		LEFT JOIN ".TB_PREF."stock_master
		ON ".TB_PREF."purch_order_details.item_code=".TB_PREF."stock_master.stock_id
		WHERE order_no =".db_escape($order_no)." ";
	$sql .= " ORDER BY po_detail_item";
	return db_query($sql, "Retreive order Line Items");
}

function print_po()
{
	global $path_to_root, $show_po_item_codes;

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$currency = $_POST['PARAM_2'];
	$email = $_POST['PARAM_3'];
	$comments = $_POST['PARAM_4'];
	$orientation = $_POST['PARAM_5'];

	if (!$from || !$to) return;

	$orientation = ($orientation ? 'L' : 'P');
	$dec = user_price_dec();

	$cols = array(4, 35, 65, 290, 350, 413,470);

	// $headers in doctext.inc
	$aligns = array('left',	'left',	'left', 'left', 'left', 'left', 'RIGHT');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
		$rep = new FrontReport(_('PURCHASE ORDER'), "PurchaseOrderBulk", user_pagesize(), 9, $orientation);
	if ($orientation == 'L')
		recalculate_cols($cols);

	for ($i = $from; $i <= $to; $i++)
	{
		$myrow = get_po($i);
		$baccount = get_default_bank_account($myrow['curr_code']);
		$params['bankaccount'] = $baccount['id'];

		if ($email == 1)
		{
			$rep = new FrontReport("", "", user_pagesize(), 9, $orientation);
			$rep->title = _('PURCHASE ORDER');
			$rep->filename = "PurchaseOrder" . $i . ".pdf";
		}
		$rep->SetHeaderType('Header209');
		$rep->currency = $cur;
		$rep->Font();
		$rep->Info($params, $cols, null, $aligns);

		$contacts = get_supplier_contacts($myrow['supplier_id'], 'order');
		$rep->SetCommonData($myrow, null, $myrow, $baccount, ST_PURCHORDER, $contacts);
		$rep->NewPage();

		$result = get_po_details($i);
		$SubTotal = $GrossTaxAmount = 0;
		$items = $prices = array();
		while ($myrow2=db_fetch($result))
		{

			$GetTaxID = get_tax_group_items($myrow['tax_group_id']);
			$GetTaxRate = get_tax_rate_1($GetTaxID['tax_type_id']);
			$TotalAmount = $myrow2['unit_price']*$myrow2["quantity_ordered"];
			$SalesTaxAmount = ($GetTaxRate['rate']/100)*$TotalAmount;

			$price_ = $myrow2['unit_price'];
			$data = get_purchase_data($myrow['supplier_id'], $myrow2['item_code']);
			if ($data !== false)
			{
				if ($data['supplier_description'] != "")
					$myrow2['description'] = $data['supplier_description'];
				if ($data['suppliers_uom'] != "")
					$myrow2['units'] = $data['suppliers_uom'];
				if ($data['conversion_factor'] != 1)
				{
					$myrow2['unit_price'] = round2($myrow2['unit_price'] * $data['conversion_factor'], user_price_dec());
					$myrow2['quantity_ordered'] = round2($myrow2['quantity_ordered'] / $data['conversion_factor'], user_qty_dec());
				}
			}
			$Net = round2(($price_ * $myrow2["quantity_ordered"]), user_price_dec());
			$prices[] = $Net;

			$items[] = $myrow2['item_code'];

//			$dec2 = 0;



//			if($myrow['tax_group_id'] == 3 && $myrow2['tax_type_id'] == 2)
			{

//				$sales_tax_amount1 = ($myrow3['rate']/100)*$Gross_Amount1;
//				$sale_tax_tot += $sales_tax_amount;
			}
//			display_error($myrow['tax_group_id']."++".$myrow2['tax_type_id']);
			$GrossTaxAmount += $SalesTaxAmount;
			$SubTotal += ($Net + $tax_grand_amount);
			$SubTotal1 = ($Net + $tax_grand_amount);
//			$loc_name = get_item_location($myrow['into_stock_location']);
//			$DisplayPrice = price_decimal_format($myrow2["unit_price"],$dec2);
//			$DisplayQty = number_format2($myrow2["quantity_ordered"],get_qty_dec($myrow2['item_code']));
//			$DisplayNet = number_format2($Net,$dec);
			$rep->TextCol(0, 1,	$myrow2['quantity_ordered'], -2);
			$rep->TextCol(1, 2,	$myrow2['units'], -2);
			$rep->TextCol(2, 3,	$myrow2['description'], -2);
//          $rep->NewLine(-1);
			$rep->TextCol(3, 4,price_format($price_), -2);
			$rep->TextCol(4, 5,	price_format($Net), -2);
			$rep->TextCol(5, 6,	$SalesTaxAmount);
			$rep->TextCol(6, 7,	price_format($SubTotal1), -2);
			$rep->NewLine(1);
			if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight))
				$rep->NewPage();
		}
		if ($myrow['comments'] != "")
		{
			$rep->NewLine();
			$rep->TextColLines(1, 5, $myrow['comments'], -2);
		}
		$DisplaySubTot = number_format2($SubTotal,$dec);

		$rep->row = $rep->bottomMargin + (8 * $rep->lineHeight);
		$doctype = ST_PURCHORDER;
		$rep->NewLine(-3.5);

		$rep->TextCol(4, 6, _("Sub-total"), -2);
		$rep->TextCol(6, 7,	$DisplaySubTot, -2);
		$rep->NewLine(+3.5);

		$rep->NewLine();

		$tax_items = get_tax_for_items($items, $prices, 0,
			$myrow['tax_group_id'], $myrow['tax_included'],  null);
//		$first = true;
//		foreach($tax_items as $tax_item)
//		{
//			if ($tax_item['Value'] == 0)
//				continue;
//			$DisplayTax = number_format2($tax_item['Value'], $dec);

//			$tax_type_name = $tax_item['tax_type_name'];
//
//			if ($myrow['tax_included'])
//			{
//				if (isset($alternative_tax_include_on_docs) && $alternative_tax_include_on_docs == 1)
//				{
//					if ($first)
//					{
//						$rep->TextCol(3, 6, _("Total Tax Excluded"), -2);
//						$rep->TextCol(6, 7,	number_format2($sign*$tax_item['net_amount'], $dec), -2);
//						$rep->NewLine();
//					}
//					$rep->TextCol(3, 6, $tax_type_name, -2);
//					$rep->TextCol(6, 7,	$DisplayTax,-2);
//					$first = false;
//				}
//				else
//					$rep->TextCol(3, 7, _("Included") . " " . $tax_type_name . _("Amount") . ": " . $DisplayTax, -2);
//			}
//			else
//			{
		$rep->NewLine(-3);
//				$SubTotal += $tax_item['Value'];
				$rep->TextCol(4, 6,"General Sales Tax(".$GetTaxRate['rate']."%)", -2);
				$rep->TextCol(6, 7,"  ".$GrossTaxAmount, -2);
		$rep->NewLine(+3);
//			}
			$rep->NewLine();
//		}
		$rep->NewLine();
		$DisplayTotal = number_format2($SubTotal + $SalesTaxAmount, $dec);
		$rep->Font('b');
		$rep->NewLine(-3.5);
		$rep->TextCol(4, 6, _("Order Total"), - 2);
		$rep->TextCol(6, 7,	$DisplayTotal, -2);
		$rep->NewLine(+3.5);
		$words = price_in_words($SubTotal, ST_PURCHORDER);
		$rep->Font('b');
		$rep->MultiCell(100, 10, " Delivery Address:" , 0, 'L', 0, 2, 36,723, true);
		$rep->Font('');
		$rep->MultiCell(100, 10, "".$myrow['delivery_address'], 0, 'L', 0, 2, 38, 735, true);
		$rep->MultiCell(400, 10, "This is a system generated purchase order & doesnot require signatures", 0, 'L', 0, 2, 180, 795, true);

		if ($words != "")
		{
			$rep->NewLine(1);
			$rep->TextCol(1, 7, $myrow['curr_code'] . ": " . $words, - 2);
		}
		$rep->Font();
		if ($email == 1)
		{
			$myrow['DebtorName'] = $myrow['supp_name'];

			if ($myrow['reference'] == "")
				$myrow['reference'] = $myrow['order_no'];
			$rep->End($email);
		}
	}
	if ($email == 0)
		$rep->End();
}

?>