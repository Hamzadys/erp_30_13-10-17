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

function get_customer_info($id){

	$sql ="SELECT * FROM `0_crm_persons` WHERE `id` IN (
	SELECT person_id FROM `0_crm_contacts` WHERE `type`='cust_branch' AND `action`='general' AND entity_id IN (
	SELECT branch_code FROM `0_cust_branch` WHERE debtor_no=".db_escape($id)."
    ) 
)";
	$result = db_query($sql, "Cant retrieve ");
	$row = db_fetch($result);
return $row;
}


function get_invoice_range($from, $to)
{
	global $SysPrefs;

	$ref = ($SysPrefs->print_invoice_no() == 1 ? "trans_no" : "reference");

	$sql = "SELECT trans.trans_no, trans.reference
		FROM ".TB_PREF."debtor_trans trans 
			LEFT JOIN ".TB_PREF."voided voided ON trans.type=voided.type AND trans.trans_no=voided.id
		WHERE trans.type=".ST_SALESINVOICE
			." AND ISNULL(voided.id)"
			." AND trans.reference>=".db_escape(get_reference(ST_SALESINVOICE, $from))
			." AND trans.reference<=".db_escape(get_reference(ST_SALESINVOICE, $to))
		." ORDER BY trans.tran_date, trans.$ref";

	return db_query($sql, "Cant retrieve invoice range");
}
function get_client_details($debtor_no){
$sql = "SELECT * FROM `0_crm_persons` WHERE `id` IN (
    SELECT person_id FROM `0_crm_contacts` WHERE `type`='cust_branch' AND `action`='general' AND entity_id IN (
    SELECT branch_code FROM `0_cust_branch` WHERE debtor_no='$debtor_no'
    ) 
)";
    $query =  db_query($sql, "Cant retrieve invoice range");
    return db_fetch($query);
}
print_invoices();

//----------------------------------------------------------------------------------------------------

function print_invoices()
{
	global $path_to_root, $SysPrefs;
	
	$show_this_payment = true; // include payments invoiced here in summary

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$currency = $_POST['PARAM_2'];
	$email = $_POST['PARAM_3'];
	$pay_service = $_POST['PARAM_4'];
	$comments = $_POST['PARAM_5'];
	$customer = $_POST['PARAM_6'];
	$orientation = $_POST['PARAM_7'];

	if (!$from || !$to) return;

	$orientation = ($orientation ? 'L' : 'P');
	$dec = user_price_dec();

 	$fno = explode("-", $from);
	$tno = explode("-", $to);
	$from = min($fno[0], $tno[0]);
	$to = max($fno[0], $tno[0]);

	//-------------code-Descr-Qty--uom--tax--prc--Disc-Tot--//
	$cols = array(4, 60, 180,  300,  400);

	// $headers in doctext.inc
	$aligns = array('left',	'left',	'right', 'right', 'right');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
		$rep = new FrontReport(_('INVOICE'), "InvoiceBulk", user_pagesize(), 9, $orientation);
	if ($orientation == 'L')
		recalculate_cols($cols);

	$range = get_invoice_range($from, $to);
	while($row = db_fetch($range))
	{
			if (!exists_customer_trans(ST_SALESINVOICE, $row['trans_no']))
				continue;
			$sign = 1;
			$myrow = get_customer_trans($row['trans_no'], ST_SALESINVOICE);
            $get_client = get_client_details($myrow['debtor_no']);
			if ($customer && $myrow['debtor_no'] != $customer) {
				continue;
			}
			if ($currency != ALL_TEXT && $myrow['curr_code'] != $currency) {
				continue;
			}
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
			$rep->currency = $cur;
			$rep->Font();
			$rep->Info($params, $cols, null, $aligns);

			$contacts = get_branch_contacts($branch['branch_code'], 'invoice', $branch['debtor_no'], true);
			$baccount['payment_service'] = $pay_service;
            $myrow = array_merge($myrow, $get_client);
			$rep->SetCommonData($myrow, $branch, $sales_order, $baccount, ST_SALESINVOICE, $contacts);
			$rep->SetHeaderType('Header1075');
			$rep->NewPage();
			// calculate summary start row for later use
			$summary_start_row = $rep->bottomMargin + (15 * $rep->lineHeight);

			if ($rep->formData['prepaid'])
			{
				$result = get_sales_order_invoices($myrow['order_']);
				$prepayments = array();
				while($inv = db_fetch($result))
				{
					$prepayments[] = $inv;
					if ($inv['trans_no'] == $row['trans_no'])
					break;
				}

				if (count($prepayments) > ($show_this_payment ? 0 : 1))
					$summary_start_row += (count($prepayments)) * $rep->lineHeight;
				else
					unset($prepayments);
			}

   			$result = get_customer_trans_details(ST_SALESINVOICE, $row['trans_no']);
			$SubTotal = 0;
        $s=1;
		$total_qty=0;
			while ($myrow2=db_fetch($result))
			{
				if ($myrow2["quantity"] == 0)
					continue;

				$Net = round2($sign * ((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
				   user_price_dec());
				$SubTotal += $Net;
	    		$DisplayPrice = number_format2($myrow2["unit_price"],$dec);
	    		$DisplayQty = number_format2($sign*$myrow2["quantity"],get_qty_dec($myrow2['stock_id']));
	    		$DisplayNet = number_format2($Net,$dec);
                $Amount = number_format2($myrow2["unit_price"] * $DisplayQty, get_qty_dec($myrow2['stock_id']));
	    		if ($myrow2["discount_percent"]==0)
		  			$DisplayDiscount ="";
	    		else
		  			$DisplayDiscount = number_format2($myrow2["discount_percent"]*100,user_percent_dec()) . "%";
				$c=0;
				$rep->TextCol($c++, $c,	$s++ , -2);
				$oldrow = $rep->row;
				$rep->TextColLines($c++, $c, $myrow2['StockDescription'], -2);
				$newrow = $rep->row;
				$rep->row = $oldrow;
				if ($Net != 0.0 || !is_service($myrow2['mb_flag']) || !$SysPrefs->no_zero_lines_amount())
				{
					$rep->TextCol($c++, $c,	$DisplayQty, -2);
					$rep->TextCol($c++, $c,	$DisplayPrice, -2);
					$rep->TextCol($c++, $c,	$Amount, -2);
//					$rep->TextCol($c++, $c,	$DisplayDiscount, -2);
//					$rep->TextCol($c++, $c,	$DisplayNet, -2);
				}
				$rep->row = $newrow;
				//$rep->NewLine(1);
				if ($rep->row < $summary_start_row)
					$rep->NewPage();
			}
			$memo = get_comments_string(ST_SALESINVOICE, $row['trans_no']);
			if ($memo != "")
			{
				$rep->NewLine();
				$rep->TextColLines(1, 5, $memo, -2);
			}

   			$DisplaySubTot = number_format2($SubTotal,$dec);

			// set to start of summary line:
    		$rep->row = $summary_start_row;
			if (isset($prepayments))
			{
				// Partial invoices table
				$rep->TextCol(0, 3,_("Prepayments invoiced to this order up to day:"));
				$rep->TextCol(0, 3,	str_pad('', 150, '_'));
				$rep->cols[2] -= 20;
				$rep->aligns[2] = 'right';
				$rep->NewLine(); $c = 0; $tot_pym=0;
				$rep->TextCol(0, 3,	str_pad('', 150, '_'));
				$rep->TextCol($c++, $c, _("Date"));
				$rep->TextCol($c++, $c,	_("Invoice reference"));
				$rep->TextCol($c++, $c,	_("Amount"));

				foreach ($prepayments as $invoice)
				{
					if ($show_this_payment || ($invoice['reference'] != $myrow['reference']))
					{
						$rep->NewLine();
						$c = 0; $tot_pym += $invoice['prep_amount'];
						$rep->TextCol($c++, $c,	sql2date($invoice['tran_date']));
						$rep->TextCol($c++, $c,	$invoice['reference']);
						$rep->TextCol($c++, $c, number_format2($invoice['prep_amount'], $dec));
					}
					if ($invoice['reference']==$myrow['reference']) break;
				}
				$rep->TextCol(0, 3,	str_pad('', 150, '_'));
				$rep->NewLine();
				$rep->TextCol(1, 2,	_("Total payments:"));
				$rep->TextCol(2, 3,	number_format2($tot_pym, $dec));
			}

			$doctype = ST_SALESINVOICE;
    		$rep->row = $summary_start_row;
		$rep->NewLine(-2);
		$total_qty += $DisplayQty;
		$rep->TextCol(2, 3,$total_qty, -2);
		$rep->NewLine(+2);
		$rep->cols[2] += 20;
			$rep->cols[3] += 20;
			$rep->aligns[3] = 'left';

		$rep->NewLine(-1);
			$rep->TextCol(3, 6,_("Sub-total"). '     ' . $rep->formData['curr_code'], -2);
			$rep->TextCol(4, 5,	$DisplaySubTot, -2);
        $rep->NewLine();
        $rep->TextCol(3, 6,_("Discount"). '      ' . $rep->formData['curr_code'], -2);
        $rep->TextCol(4, 5,	number_format2($myrow['discount1'],$dec), -2);
        $rep->NewLine();
        $rep->TextCol(3, 6,_("Incentive"). '     ' . $rep->formData['curr_code'], -2);
        $rep->TextCol(4, 5,	number_format2($myrow['discount2'],$dec), -2);

			$rep->NewLine();
			if ($myrow['ov_freight'] != 0.0)
			{
   				$DisplayFreight = number_format2($sign*$myrow["ov_freight"],$dec);
				$rep->TextCol(3, 6, _("Shipping"), -2);
				$rep->TextCol(6, 7,	$DisplayFreight, -2);
				$rep->NewLine();
			}	
			$tax_items = get_trans_tax_details(ST_SALESINVOICE, $row['trans_no']);
			$first = true;
    		while ($tax_item = db_fetch($tax_items))
    		{
    			if ($tax_item['amount'] == 0)
    				continue;
    			$DisplayTax = number_format2($sign*$tax_item['amount'], $dec);

    			if ($SysPrefs->suppress_tax_rates() == 1)
    				$tax_type_name = $tax_item['tax_type_name'];
    			else
    				$tax_type_name = $tax_item['tax_type_name']." (".$tax_item['rate']."%) ";

    			if ($myrow['tax_included'])
    			{
    				if ($SysPrefs->alternative_tax_include_on_docs() == 1)
    				{
    					if ($first)
    					{
//							$rep->TextCol(3, 6, _("Balance"), -2);
//							$rep->TextCol(4, 5,	number_format2($sign*$tax_item['net_amount'], $dec), -2);
							$rep->NewLine();
    					}
						$rep->TextCol(3, 6, $tax_type_name, -2);
//						$rep->TextCol(6, 7,	$DisplayTax, -2);
						$first = false;
    				}
    				else
						$rep->TextCol(3, 7, _("Included") . " " . $tax_type_name . _("Amount") . ": " . $DisplayTax, -2);
				}
    			else
    			{
					$rep->TextCol(3, 6, $tax_type_name, -2);
//					$rep->TextCol(6, 7,	$DisplayTax, -2);
				}
				$rep->NewLine();
    		}

//    		$rep->NewLine();
			$DisplayTotal = number_format2($sign*($myrow["ov_freight"] - $myrow["ov_gst"] +
				$myrow["ov_amount"]+$myrow["ov_freight_tax"] - $myrow["discount1"] - $myrow["discount2"]),$dec);
//			$rep->Font('bold');
//			if (!$myrow['prepaid']) $rep->Font('bold');
				$rep->TextCol(3, 6, $rep->formData['prepaid'] ? _("TOTAL ORDER VAT INCL.") : _("Balance"). '       ' . $rep->formData['curr_code'], - 2);
			$rep->TextCol(4, 5, $DisplayTotal, -2);
			if ($rep->formData['prepaid'])
			{
				$rep->NewLine();
				$rep->Font('bold');
				$rep->TextCol(3, 6, $rep->formData['prepaid']=='final' ? _("THIS INVOICE") : _("TOTAL INVOICE"), - 2);
				$rep->TextCol(6, 7, number_format2($myrow['prep_amount'], $dec), -2);
			}
			$words = price_in_words($rep->formData['prepaid'] ? $myrow['prep_amount'] : $myrow['Total']
				, array( 'type' => ST_SALESINVOICE, 'currency' => $myrow['curr_code']));
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
    $rep->SetDrawColor(0, 0, 0);
    $rep->MultiCell(38, 13, "Subject:", 0, 'L', 0,1,40,70, true);
    $rep->MultiCell(350, 13,$rep->formData['application'], 0, 'L', 0,1,75,70, true);

    $rep->MultiCell(120, 12, "Operations", 0, 'R', 1,1,450,785, true);
    $rep->MultiCell(100, 8, "___________________", 0, 'L', 0,1,40,719, true);
    $rep->MultiCell(96, 8, "Received By", 0, 'C', 1,1,40,729, true);
    $rep->MultiCell(100, 8, "___________________", 0, 'L', 0,1,270,719, true);
    $rep->MultiCell(96, 8, "Released By", 0, 'C', 1,1,270,729, true);
    $rep->MultiCell(121, 8, "Approved By:", 1, 'R', 1,1,450,727, true);
    $rep->MultiCell(121, 91, "", 1, 'L', 0,1,450,740, true);
    $rep->MultiCell(120, 12, "Managing Director", 0, 'R', 1,1,450,740, true);
    $rep->setfontsize(7);
    $rep->MultiCell(121, 10, "Waseem Choudhry", 0, 'R', 0,1,450,752, true);
    $rep->MultiCell(121, 10, "AOIS", 0, 'R', 0,1,450,763, true);
    $rep->MultiCell(121, 10, "Cell:0333-5119949", 0, 'R', 0,1,450,774, true);

    $rep->MultiCell(121, 10, " Ali Ejaz", 0, 'R', 0,1,450,798, true);
    $rep->MultiCell(121, 10, "Cell:0321-7960049", 0, 'R', 0,1,450,809, true);
    //$rep->MultiCell(121, 10, "Email:aois.farhan@gmail.com", 0, 'R', 0,1,450,820, true);
    $rep->setfontsize(6);
    $rep->MultiCell(410, 12, "Warranty Disclaimer", 1, 'C', 1,1,40,740, true);
    $rep->MultiCell(410, 79, ""/*$rep->company['legal_text']*/, 1, 'L', 0,1,40,752, true);
    $rep->MultiCell(410, 79,
    htmlspecialchars_decode( $rep->company['legal_text']), 0, 'L', 0,1,40,752, true);

    if ($email == 0)
		$rep->End();
}

