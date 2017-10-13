<?php
$page_security = $_POST['PARAM_0'] == $_POST['PARAM_1'] ?
	'SA_SALESTRANSVIEW' : 'SA_SALESBULKREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Print Invoices
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

//----------------------------------------------------------------------------------------------------

print_invoices();

//----------------------------------------------------------------------------------------------------
function get_phoness($debtor_no)
{
	$sql = "SELECT phone FROM `0_crm_persons` WHERE `id` IN (
   SELECT person_id FROM `0_crm_contacts` WHERE `type`='cust_branch' AND `action`='general'
    AND entity_id IN (
   SELECT branch_code FROM `0_cust_branch` WHERE debtor_no=".db_escape($debtor_no).')) ';

	$db  = db_query($sql,"item prices could not be retreived");
	$ft = db_fetch($db);
	return $ft;


}

function get_tax_rate_1()
{
	$sql = "SELECT ".TB_PREF."tax_types.rate FROM ".TB_PREF."tax_types
	 WHERE ".TB_PREF."tax_types.id = 1";
	$result = db_query($sql, 'error');
	return $result;
}

function print_invoices()
{
	global $path_to_root, $alternative_tax_include_on_docs, $suppress_tax_rates, $no_zero_lines_amount;

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$currency = $_POST['PARAM_2'];
	$email = $_POST['PARAM_3'];
	$pay_service = $_POST['PARAM_4'];
	$comments = $_POST['PARAM_5'];
	$orientation = $_POST['PARAM_6'];

	if (!$from || !$to) return;

	$orientation = ($orientation ? 'L' : 'P');
	$dec = user_price_dec();

	$fno = explode("-", $from);
	$tno = explode("-", $to);
	$from = min($fno[0], $tno[0]);
	$to = max($fno[0], $tno[0]);

	$cols = array(4, 40, 280, 340, 395, 460);

	// $headers in doctext.inc

	$aligns = array('left',	'left',	'left', 'left', 'left', 'right');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
		$rep = new FrontReport(_('INVOICE'), "InvoiceBulk", user_pagesize(), 9, $orientation);
	if ($orientation == 'L')
		recalculate_cols($cols);
	for ($i = $from; $i <= $to; $i++)
	{
		if (!exists_customer_trans(ST_SALESINVOICE, $i))
			continue;
		$sign = 1;
		$myrow = get_customer_trans($i, ST_SALESINVOICE);

		$baccount = get_default_bank_account($myrow['curr_code']);
		$params['bankaccount'] = $baccount['id'];

		$branch = get_branch($myrow["branch_code"]);
		$sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER);
		if ($email == 1)
		{
			$rep = new FrontReport("", "", user_pagesize(), 9, $orientation);
			$rep->title = _('INVOICE');
			$rep->filename = "Invoice" . $myrow['reference'] . ".pdf";
		}
		$rep->SetHeaderType('Header1071');
		$rep->currency = $cur;
		$rep->Font('');
		$rep->Info($params, $cols, null, $aligns);

		$contacts = get_branch_contacts($branch['branch_code'], 'invoice', $branch['debtor_no'], true);
		$baccount['payment_service'] = $pay_service;


		$rep->SetCommonData($myrow, $branch, $sales_order, $baccount, ST_SALESINVOICE, $contacts);
		$rep->NewPage();
		$myrow3 = db_fetch(get_tax_rate_1());

		$result = get_customer_trans_details(ST_SALESINVOICE, $i);
		$SubTotal = 0;
		$DisplayFreight=0;
		$DisplaySubTot=0;
		$DisplayTotal=0;
		$sales_tax_total=0;
		while ($myrow2=db_fetch($result))
		{



			//$rep->MultiCell(100, 10,"NTN :  ".$myrow['ntn_id'] , 0, 'L', 0, 2, 50,140, true);
//				if ($myrow2["quantity"] == 0)
//					continue;

			$Net = round2($sign * ((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
				user_price_dec());
			$SubTotal += $Net;
			$DisplayPrice = number_format2($myrow2["unit_price"],$dec);
			$DisplayQty =   number_format2($sign*$myrow2["quantity"]);
			$price_net=$myrow2["unit_price"];

			$DisplayNet =   number_format2($Net,$dec);
			if ($myrow2["discount_percent"]==0)
				$DisplayDiscount ="";
			else
				$DisplayDiscount = number_format2($myrow2["discount_percent"]*100,user_percent_dec()) . "%";
			//	$rep->TextCol(0, 1,	$myrow2['stock_id'], -2);
			$oldrow = $rep->row;
			$rep->TextColLines(1, 2, $myrow2['StockDescription'], -2);
			$newrow = $rep->row;
			$rep->row = $oldrow;


			$unit_price=round2($DisplayPrice / 1.17,$dec);
			$Net_amount=round2(($DisplayPrice / 1.17) * $myrow2["quantity"],$dec);
			$sales_tax_amount=round2($Net_amount * 0.17,$dec);
			$Gross=$Net_amount + $sales_tax_amount;

			if ($Net != 0.0 || !is_service($myrow2['mb_flag']) || !isset($no_zero_lines_amount) || $no_zero_lines_amount == 0)
			{
                    $pref = get_company_pref();
//                $item=get_item($myrow2['stk_code']);
                if($pref['alt_uom'] == 1)
                {
                    $rep->TextCol(0, 1,$DisplayQty.' '. $myrow2['units_id'], -2);
                }
                else
                {
                    $rep->TextCol(0, 1,$DisplayQty.' '. $myrow2['units'], -2);
                }

			
				$rep->AmountCol(2, 3,	$unit_price, $dec);
				$rep->AmountCol(3, 4,	$Net_amount, $dec);
				$DisplaySubTot += $Net_amount;
				$sales_tax_total +=$sales_tax_amount;
				$DisplayTotal += $Net_amount + $sales_tax_amount;
				$tax_total=$myrow3['rate']/100;
				$tax_grand_amount=$tax_total*$myrow2["unit_price"]*$DisplayQty;

				$rep->AmountCol(4, 5,$sales_tax_amount	, $dec);
				$gross_amount_=$myrow2["unit_price"]*$DisplayQty+$tax_grand_amount;
				$rep->AmountCol(5, 6,$Net_amount + $sales_tax_amount, $dec);
			}
			$rep->row = $newrow;
			$rep->NewLine(1);
			if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight))
				$rep->NewPage();
		}

		$memo = get_comments_string(ST_SALESINVOICE, $i);
		if ($memo != "")
		{
			$rep->NewLine();
			//$rep->TextColLines(1, 5, $memo, -2);
		}



		$rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
		$doctype = ST_SALESINVOICE;
		$rep->NewLine(-2.5);
		$rep->font('');
		$rep->TextCol(3, 5, _("Total Net Amount"), -2);
		$rep->AmountCol(5, 6,	$DisplaySubTot, $dec);
		$rep->NewLine();
		$rep->TextCol(3, 5, _("Total S.Tax Amount"), -2);
		$rep->AmountCol(5, 6,	$sales_tax_total, $dec);
		$rep->NewLine();
		$rep->TextCol(3, 5,_("Delivery charges"), -2);
		$rep->AmountCol(5, 6,	$myrow['freight_cost'], $dec);

		//$rep->NewLine();
		$tax_items = get_trans_tax_details(ST_SALESINVOICE, $i);
		$first = true;
		while ($tax_item = db_fetch($tax_items))
		{
			if ($tax_item['amount'] == 0)
				continue;
			$DisplayTax = number_format2($sign*$tax_item['amount'], $dec);

			if (isset($suppress_tax_rates) && $suppress_tax_rates == 1)
				$tax_type_name = $tax_item['tax_type_name'];
			else
				$tax_type_name = $tax_item['tax_type_name']." (".$tax_item['rate']."%) ";

			if ($tax_item['included_in_price'])
			{
				if (isset($alternative_tax_include_on_docs) && $alternative_tax_include_on_docs == 1)
				{
					if ($first)
					{
						$rep->TextCol(4, 5, _("Total Net Amount"), -2);
						$rep->AmountCol(6, 7,	number_format2($sign*$tax_item['net_amount'], $dec), -2);
						$rep->NewLine();
					}
					$rep->AmountCol(3, 6, $tax_type_name, $dec);
					//$rep->AmountCol(6, 7,	$DisplayTax, $dec);
					$first = false;
				}
				//else
				//$rep->TextCol(3, 7, _("Included") . " " . $tax_type_name . _("Amount") . ": " . $DisplayTax, -2);
			}
			else
			{
				$rep->TextCol(3, 6, $tax_type_name, -2);
				$rep->TextCol(5, 6,	$DisplayTax, -2);
			}
			$rep->NewLine();
		}

		$rep->NewLine();

		$rep->Font('');

		$rep->TextCol(3, 5,_("Invoice Total (Rs.)"), - 2);
		$rep->AmountCol(5, 6, $DisplayTotal, $dec);
		$words = price_in_words($myrow['Total'], ST_SALESINVOICE);
		$cust_phone = get_phoness($rep->formData['debtor_no']);

		$rep->MultiCell(250, 20, "Tel:           ".$cust_phone['phone'], 0, 'L', 0, 2, 50,259, true);

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
	}
	if ($email == 0)
		$rep->End();
}

?>