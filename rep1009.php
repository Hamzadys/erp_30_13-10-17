<?php

$page_security = $_POST['PARAM_0'] == $_POST['PARAM_1'] ?
	'SA_SALESTRANSVIEW' : 'SA_SALESBULKREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Print Sales Orders
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");

//----------------------------------------------------------------------------------------------------

print_sales_orders();

function get_payment_terms_name_($id)
{
    $sql = "SELECT terms FROM ".TB_PREF."payment_terms WHERE terms_indicator =" .db_escape($id);
    $result = db_query($sql, 'error');
    $row = db_fetch_row($result);
    return $row[0];
}
function get_packing($stock_id)
{
	$sql = "SELECT carton FROM ".TB_PREF."stock_master WHERE stock_id=".db_escape($stock_id);

	$result = db_query($sql, "could not get customer");

	$row = db_fetch_row($result);

	return $row[0];
}
//
function get_salman($stock_id)
{
	$sql = "SELECT salesman FROM ".TB_PREF."stock_master WHERE stock_id=".db_escape($stock_id);

	$result = db_query($sql, "could not get customer");

	$row = db_fetch_row($result);

	return $row[0];
}
function get_units($stock_id)
{
	$sql = "SELECT units FROM ".TB_PREF."stock_master WHERE stock_id=".db_escape($stock_id);

	$result = db_query($sql, "could not get customer");

	$row = db_fetch_row($result);

	return $row[0];
}
function get_phoneno_for_crm_persons($debtor_no)
{
    $sql = "SELECT * FROM `".TB_PREF."crm_persons` WHERE `id` IN (
            SELECT person_id FROM `".TB_PREF."crm_contacts` WHERE `type`='cust_branch' AND `action`='general' AND entity_id IN (
            SELECT branch_code FROM `".TB_PREF."cust_branch` WHERE debtor_no = ".db_escape($debtor_no)."))";
    $query = db_query($sql, "Error");
    return db_fetch($query);
}
function get_cust_branch_new($customer_id, $branch_code)
{
	$sql = "SELECT * FROM ".TB_PREF."cust_branch
		WHERE branch_code=".db_escape($branch_code)."
		AND debtor_no=".db_escape($customer_id);
	$result = db_query($sql,"check failed");
	return db_fetch($result);
}
function print_sales_orders()
{
	global $path_to_root, $SysPrefs;

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$currency = $_POST['PARAM_2'];
	$email = $_POST['PARAM_3'];
	$print_as_quote = $_POST['PARAM_4'];
	$comments = $_POST['PARAM_5'];
	$orientation = $_POST['PARAM_6'];

	if (!$from || !$to) return;

	$orientation = ($orientation ? 'L' : 'P');
	$dec = user_price_dec();

	$cols = array(4, 60,200, 275, 335, 390, 460, 515);

	// $headers in doctext.inc
	$headers = array(_("S.No"),_("Description"),_("Register"), _("Packing"),
		_("Qty"), _("Rate"), _("Amount"));
	$aligns = array('left',	'left',	'left',	'left', 'left', 'right', 'right');

	$params = array('comments' => $comments, 'print_quote' => $print_as_quote);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
	{

		if ($print_as_quote == 0)
			$rep = new FrontReport(_("SALES ORDER"), "SalesOrderBulk", user_pagesize(), 9, $orientation);
		else
			$rep = new FrontReport(_("QUOTE"), "QuoteBulk", user_pagesize(), 9, $orientation);
	}
    if ($orientation == 'L')
    	recalculate_cols($cols);

	for ($i = $from; $i <= $to; $i++)
	{
		$myrow = get_sales_order_header($i, ST_SALESORDER);
		if ($currency != ALL_TEXT && $myrow['curr_code'] != $currency) {
			continue;
		}
		$baccount = get_default_bank_account($myrow['curr_code']);
		$params['bankaccount'] = $baccount['id'];
		$branch = get_branch($myrow["branch_code"]);
		if ($email == 1)
			$rep = new FrontReport("", "", user_pagesize(), 9, $orientation);
		$rep->SetHeaderType('Header1009');
		$rep->currency = $cur;
		$rep->Font();
		if ($print_as_quote == 1)
		{
			$rep = new FrontReport("", "", user_pagesize(), 9, $orientation);
			if ($print_as_quote == 1)
			{
				$rep->title = _('QUOTE');
				$rep->filename = "Quote" . $i . ".pdf";
			}
			else
			{
				$rep->title = _("SALES ORDER");
				$rep->filename = "SalesOrder" . $i . ".pdf";
			}
		}
		else
			$rep->title = ($print_as_quote==1 ? _("QUOTE") : _("SALES ORDER"));
		$rep->currency = $cur;
		$rep->Font();
		$rep->Info($params, $cols, $headers, $aligns);

		$contacts = get_branch_contacts($branch['branch_code'], 'order', $branch['debtor_no'], true);
		$rep->SetCommonData($myrow, $branch, $myrow, $baccount, ST_SALESORDER, $contacts);
		$rep->SetHeaderType('Header1009');
		$rep->NewPage();

		$result = get_sales_order_details($i, ST_SALESORDER);
		$SubTotal = 0;
		$items = $prices = array();
		$s_no=0;
		while ($myrow2=db_fetch($result))
		{
			$Net = round2(((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
			   user_price_dec());
			$prices[] = $Net;
			$items[] = $myrow2['stk_code'];
			$SubTotal += $Net;
			$DisplayPrice = number_format2($myrow2["unit_price"],$dec);
			$DisplayQty = number_format2($myrow2["quantity"],get_qty_dec($myrow2['stk_code']));
			$DisplayNet = number_format2($Net,$dec);
			if ($myrow2["discount_percent"]==0)
				$DisplayDiscount ="";
			else
				$DisplayDiscount = number_format2($myrow2["discount_percent"]*100,user_percent_dec()) . "%";
			$s_no++;

			$rep->TextCol(0, 1,	$s_no, -2);
			$oldrow = $rep->row;
			$rep->TextColLines(1, 2, $myrow2['description'], -2);
			$newrow = $rep->row;
			$rep->row = $oldrow;
			if ($Net != 0.0 || !is_service($myrow2['mb_flag']) || !$SysPrefs->no_zero_lines_amount())
			{

				$rep->TextCol(2, 3,	get_salesman_name(get_salman($myrow2['stk_code'])), -2);
				$rep->TextCol(3, 4,	get_packing($myrow2['stk_code']), -2);
				$rep->TextCol(4, 5,	$DisplayQty.'-'.get_units($myrow2['stk_code']), -2);
				$rep->TextCol(5, 6,	$DisplayPrice, -2);
				$rep->TextCol(6, 7,	$DisplayNet, -2);
				//$rep->TextCol(6, 7,	$DisplayNet, -2);
			}
			$rep->row = $newrow;
			if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight))
				$rep->NewPage();
		}
		if ($myrow['comments'] != "")
		{
			$rep->NewLine();
			$rep->TextColLines(1, 2, $myrow['comments'], -2);

		}
		$rep->MultiCell(770, 30, "".get_payment_terms_name_($rep->formData['payment_terms']), 0, 'L', 0,1,420,142, true);
	$rep->MultiCell(770, 30, "Payment Terms :", 0, 'L', 0,1,350,142, true);
		$rep->MultiCell(130, 15, "".$rep->formData['customer_ref'], 0, 'L', 0,1,100,140, true);
		$DisplaySubTot = number_format2($SubTotal,$dec);

		$rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
		$doctype = ST_SALESORDER;

		//$rep->TextCol(3, 6, _("Sub-total"), -2);

	//	$rep->TextCol(5, 6,	$DisplaySubTot, -2);
		$rep->NewLine();
		if ($myrow['freight_cost'] != 0.0)
		{
//			$DisplayFreight = number_format2($myrow["freight_cost"],$dec);
//			$rep->TextCol(6, 7, _("Shipping"), -2);
//			$rep->TextCol(6, 7,	$DisplayFreight, -2);
//			$rep->NewLine();
		}
		$DisplayTotal = number_format2($myrow["freight_cost"] + $SubTotal, $dec);
		if ($myrow['tax_included'] == 0) {
			$rep->TextCol(6, 7, _("TOTAL ORDER EX VAT"), - 2);
			$rep->TextCol(6, 7,	$DisplayTotal, -2);
			$rep->NewLine();
		}

		$tax_items = get_tax_for_items($items, $prices, $myrow["freight_cost"],
		  $myrow['tax_group_id'], $myrow['tax_included'],  null);
		$first = true;
		foreach($tax_items as $tax_item)
		{
			if ($tax_item['Value'] == 0)
				continue;
			$DisplayTax = number_format2($tax_item['Value'], $dec);

			$tax_type_name = $tax_item['tax_type_name'];

			if ($myrow['tax_included'])
			{
				if ($SysPrefs->alternative_tax_include_on_docs() == 1)
				{
					if ($first)
					{
						$rep->TextCol(3, 6, _("Total Tax Excluded"), -2);
						$rep->TextCol(6, 7,	number_format2($sign*$tax_item['net_amount'], $dec), -2);
						$rep->NewLine();
					}
					$rep->TextCol(3, 6, $tax_type_name, -2);
					$rep->TextCol(6, 7,	$DisplayTax, -2);
					$first = false;
				}
				//else
					//$rep->TextCol(3, 7, _("Included") . " " . $tax_type_name . " " . _("Amount"). ": " . $DisplayTax, -2);
			}
			else
			{
				$SubTotal += $tax_item['Value'];
				//$rep->TextCol(3, 6, $tax_type_name, -2);
				$rep->TextCol(6, 7,	$DisplayTax, -2);
			}
			$rep->NewLine();
		}

		$rep->NewLine();

		$DisplayTotal = number_format2($myrow["freight_cost"] + $SubTotal, $dec);
		$rep->Font('bold');
		$rep->TextCol(1, 2, _("TOTAL ORDER VAT INCL."), - 2);
//		$rep->NewLine(-29);
		$rep->TextCol(6, 7,	$DisplayTotal, -2);
		$words = price_in_words($myrow["freight_cost"] + $SubTotal, ST_SALESORDER);
		if ($words != "")
		{
			$rep->NewLine(1);
			$rep->TextCol(1, 7, $myrow['curr_code'] . ": " . $words, - 2);
		}
		$rep->Font();
		if ($email == 1)
		{
			$rep->End($email);
		}

		$branch=get_cust_branch_new($myrow['debtor_no'],$myrow['branch_code']);
		//$this->MultiCell(200, 18,  get_salesman_name($branch['salesman']), 0, 'L', 0, 2, 500,620, true);

		$rep->MultiCell(770, 30, "Invoice:", 0, 'L', 0,1,357,125, true);

		$rep->MultiCell(123, 30, get_salesman_name($branch['salesman']), 0, 'L', 0,1,115,730, true);
		$rep->MultiCell(123, 30, "________________________", 0, 'L', 0,1,80,730, true);
		$rep->MultiCell(123, 30, "Requested By", 0, 'C', 0,1,80,750, true);
		$rep->MultiCell(123, 30, "". $_SESSION["wa_current_user"]->username, 0, 'L', 0,1,290,730, true);

		$rep->MultiCell(123, 30, "________________________", 0, 'L', 0,1,240,730, true);
        $rep->MultiCell(123, 30, "Prepaid By", 0, 'C', 0,1,240,750, true);

		$rep->MultiCell(123, 30, "________________________", 0, 'L', 0,1,410,730, true);
       	$rep->MultiCell(123, 30, "Approved By", 0, 'C', 0,1,410,750, true);

	if ($rep->formData['sample']==1){

		$a="yes";

	}
	else{

		$a="No";
	}
	if ($rep->formData['supply']==1){

		$b="yes";

	}
	else{

		$b="No";
	}
	if ($rep->formData['dc']==1){

		$c="yes";

	}
	else{

		$c="No";
	}
	if ($rep->formData['invoice']==1){

		$d="yes";

	}
	else{

		$d="No";
	}
		$rep->MultiCell(770, 30, "Sale Order", 0, 'L', 0,1,300,40, true);
		$rep->MultiCell(770, 30, "Dc:", 0, 'L', 0,1,357,108, true);
		$rep->MultiCell(770, 30, "Supply:", 0, 'L', 0,1,357,55, true);
		$rep->MultiCell(770, 30, "Sample:", 0, 'L', 0,1,357,40, true);
		$rep->MultiCell(130, 15,  $a, 1, 'L', 0,1,400,40, true);
		$rep->MultiCell(130, 15,$b, 1, 'L', 0,1,400,55, true);
$rep->MultiCell(175, 34.5, "", 1, 'L', 0,1,355,70, true);
$rep->MultiCell(175, 34.5, " Application", 0, 'L', 0,1,355,72, true);
$rep->MultiCell(175, 34.5, " Site Survey Date", 0, 'L', 0,1,355,83, true);
$rep->MultiCell(175, 34.5, " Address", 0, 'L', 0,1,355,92, true);
		$rep->MultiCell(100, 34,$rep->formData['application'], 1, 'L', 0,1,430,70, true);


		$rep->MultiCell(130, 15,$c, 1, 'L', 0,1,400,104, true);
		$rep->MultiCell(130, 15, $d, 1, 'L', 0,1,400,123, true);
//		$rep->MultiCell(52, 160, "", 1, 'L', 0,1,448,172, true);
//		//$rep->MultiCell(525, 20, "67", 1, 'L', 0,1,40,171, true);
//		$rep->MultiCell(60, 160, "", 1, 'C', 0,1,310,172, true);
//		$rep->MultiCell(50, 178, "", 1, 'L', 0,1,40,154, true);
//		$rep->MultiCell(75, 160, "", 1, 'L', 0,1,235,172, true);

	}
	if ($email == 0)
		$rep->End();

}

