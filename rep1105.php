<?php

$page_security = $_POST['PARAM_0'] == $_POST['PARAM_1'] ?
	'SA_SALESTRANSVIEW' : 'SA_SALESBULKREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Janusz Dobrwolski
// date_:	2008-01-14
// Title:	Print Delivery Notes
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

//----------------------------------------------------------------------------------------------------

print_deliveries();

//----------------------------------------------------------------------------------------------------


function get_packings_report ($stock_id)
{
	$sql = "SELECT carton FROM ".TB_PREF."stock_master WHERE stock_id=".db_escape($stock_id);

	$result = db_query($sql, "could not get customer");

	$row = db_fetch_row($result);

	return $row[0];
}
function get_customer_information_report ($debtor_no)
{
	$sql = "SELECT * FROM `0_crm_persons` WHERE `id` IN (
	SELECT person_id FROM `0_crm_contacts` WHERE `type`='cust_branch' AND `action`='general' AND entity_id IN (
	SELECT branch_code FROM `0_cust_branch` WHERE debtor_no = '$debtor_no'))";
	$result = db_query($sql,"Error");
	return db_fetch($result);
}
function get_location_reports ($loc_code)
{
	$sql = "SELECT location_name FROM ".TB_PREF."locations WHERE loc_code=".db_escape($loc_code);
	$result = db_query($sql,"Customer Record Retreive");
	$row = db_fetch_row($result);
	return $row[0];
	display_db_error("could not retreive the location name for $loc_code", $sql, true);
}
function print_deliveries()
{
	global $path_to_root, $SysPrefs;

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$email = $_POST['PARAM_2'];
	$packing_slip = $_POST['PARAM_3'];
	$comments = $_POST['PARAM_4'];
	$orientation = $_POST['PARAM_5'];

	if (!$from || !$to) return;

	$orientation = ($orientation ? 'L' : 'P');
	$dec = user_price_dec();

	$fno = explode("-", $from);
	$tno = explode("-", $to);
	$from = min($fno[0], $tno[0]);
	$to = max($fno[0], $tno[0]);

	$cols = array(4, 60, 150, 340,450);

	// $headers in doctext.inc
	$aligns = array('left',	'left',	'left', 'left', 'left');
	$headers = array(_("Qty"),_("Unit"), _("Description"), _("Packing"),
		_("Remarks"));
	$params = array('comments' => $comments, 'packing_slip' => $packing_slip);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
	{
		if ($packing_slip == 0)
			$rep = new FrontReport(_('DELIVERY'), "DeliveryNoteBulk", user_pagesize(), 9, $orientation);
		else
			$rep = new FrontReport(_('PACKING SLIP'), "PackingSlipBulk", user_pagesize(), 9, $orientation);
	}
    if ($orientation == 'L')
    	recalculate_cols($cols);
	for ($i = $from; $i <= $to; $i++)
	{
			if (!exists_customer_trans(ST_CUSTDELIVERY, $i))
				continue;
			$myrow = get_customer_trans($i, ST_CUSTDELIVERY);
			$branch = get_branch($myrow["branch_code"]);
			$sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER); // ?
			if ($email == 1)
			{
				$rep = new FrontReport("", "", user_pagesize(), 9, $orientation);
				if ($packing_slip == 0)
				{
					$rep->title = _('DELIVERY NOTE');
					$rep->filename = "Delivery" . $myrow['reference'] . ".pdf";
				}
				else
				{
					$rep->title = _('PACKING SLIP');
					$rep->filename = "Packing_slip" . $myrow['reference'] . ".pdf";
				}
			}
			$rep->currency = $cur;
			$rep->Font();
			$rep->Info($params, $cols, $headers, $aligns);

			$contacts = get_branch_contacts($branch['branch_code'], 'delivery', $branch['debtor_no'], true);
			$rep->SetCommonData($myrow, $branch, $sales_order, '', ST_CUSTDELIVERY, $contacts);
			$rep->SetHeaderType('Header1105');
			$rep->NewPage();

   			$result = get_customer_trans_details(ST_CUSTDELIVERY, $i);
			$SubTotal = 0;
		    $s_no=0;
			while ($myrow2=db_fetch($result))
			{
				if ($myrow2["quantity"] == 0)
					continue;

				$Net = round2(((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
				   user_price_dec());
				$SubTotal += $Net;
	    		$DisplayPrice = number_format2($myrow2["unit_price"],$dec);
	    		$DisplayQty = number_format2($myrow2["quantity"],get_qty_dec($myrow2['stock_id']));
	    		$DisplayNet = number_format2($Net,$dec);
	    		if ($myrow2["discount_percent"]==0)
		  			$DisplayDiscount ="";
	    		else
		  			$DisplayDiscount = number_format2($myrow2["discount_percent"]*100,user_percent_dec()) . "%";
				$s_no++;
			//	$rep->TextCol(0, 1,	$s_no, -2);
				$oldrow = $rep->row;
				$rep->TextColLines(2, 3, $myrow2['StockDescription'], -2);
				$newrow = $rep->row;
				$rep->row = $oldrow;
				if ($Net != 0.0  || !is_service($myrow2['mb_flag']) || !$SysPrefs->no_zero_lines_amount())
				{
					$rep->TextCol(1, 2,$myrow2['units']	, -2);
					$rep->TextCol(0, 1,$DisplayQty	, -2);
						$rep->TextCol(3, 4,	get_packings_report($myrow2['stock_id']), -2);
					//if ($packing_slip == 0)
					{
						//$rep->TextCol(4, 5,	$DisplayPrice, -2);
						//$rep->TextCol(5, 6,	$DisplayDiscount, -2);
						//$rep->TextCol(5, 6,	$DisplayNet, -2);
					}
				}
				
					$rep->TextCol(4, 5, $myrow2["text1"]	, -2);

				$rep->row = $newrow;
				//$rep->NewLine(1);
				if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight))
					$rep->NewPage();
			}

			$memo = get_comments_string(ST_CUSTDELIVERY, $i);
			if ($memo != "")
			{
				$rep->NewLine();
				$rep->TextColLines(1, 5, $memo, -2);
			}

   			$DisplaySubTot = number_format2($SubTotal,$dec);

    		$rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
			$doctype=ST_CUSTDELIVERY;
			if ($packing_slip == 0)
			{
				//$rep->TextCol(3, 6, _("Sub-total"), -2);
				//$rep->TextCol(5, 6,	$DisplaySubTot, -2);
				$rep->NewLine();
				if ($myrow['ov_freight'] != 0.0)
				{
//					$DisplayFreight = number_format2($sign*$myrow["ov_freight"],$dec);
//					$rep->TextCol(3, 6, _("Shipping"), -2);
//					$rep->TextCol(5, 6,	$DisplayFreight, -2);
//					$rep->NewLine();
				}	
				$tax_items = get_trans_tax_details(ST_CUSTDELIVERY, $i);
				$first = true;
    			while ($tax_item = db_fetch($tax_items))
    			{
    				if ($tax_item['amount'] == 0)
    					continue;
    				$DisplayTax = number_format2($tax_item['amount'], $dec);
 
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
								//$rep->TextCol(3, 6, _("Total Tax Excluded"), -2);
								$rep->TextCol(6, 7,	number_format2($tax_item['net_amount'], $dec), -2);
								$rep->NewLine();
    						}
							//$rep->TextCol(3, 6, $tax_type_name, -2);
							//$rep->TextCol(6, 7,	$DisplayTax, -2);
							$first = false;
    					}
//    					else
//							$rep->TextCol(3, 7, _("Included") . " " . $tax_type_name . _("Amount") . ": " . $DisplayTax, -2);
					}
    				else
    				{
						//$rep->TextCol(3, 6, $tax_type_name, -2);
						//$rep->TextCol(6, 7,	$DisplayTax, -2);
					}
					$rep->NewLine();
    			}
    			$rep->NewLine();
				$DisplayTotal = number_format2($myrow["ov_freight"] +$myrow["ov_freight_tax"] + $myrow["ov_gst"] +
					$myrow["ov_amount"],$dec);
				$DisplayQTY_total =number_format2($myrow2["quantity"],get_qty_dec($myrow2['stock_id']));
				$rep->Font('bold');
				$rep->NewLine();
			//	$rep->TextCol(1, 2, _("TOTAL QTY"), - 2);
			//	$rep->TextCol(0, 1,	$DisplayQTY_total, -2);
				$words = price_in_words($myrow['Total'], ST_CUSTDELIVERY);
				if ($words != "")
				{
					$rep->NewLine(1);
					$rep->TextCol(1, 7, $myrow['curr_code'] . ": " . $words, - 2);
				}	
				$rep->Font();
			}	
			if ($email == 1)
			{
				$rep->End($email);
			}

		//$rep->MultiCell(770, 30, "Material Return Request", 0, 'L', 0,1,300,50, true);]\
		$sales_order = get_sales_order_header($myrow['order_'], ST_SALESORDER);
		$customer_imformation =get_customer_information_report($myrow['debtor_no']);

		$rep->MultiCell(770, 30,"Phone:", 0, 'L', 0,1,50,150, true);
		$rep->MultiCell(770, 30,"". $customer_imformation['phone'], 0, 'L', 0,1,98,150, true);

		$rep->MultiCell(770, 30, "S.O No.", 0, 'L', 0,1,359,110, true);
		$rep->MultiCell(770, 30, "Location.", 0, 'L', 0,1,359,122, true);
		$rep->MultiCell(770, 30, get_location_reports($rep->formData['from_stk_loc']), 0, 'L', 0,1,406,122, true);
		$rep->MultiCell(770, 30, "P.O No.", 0, 'L', 0,1,359,134, true);
		$rep->MultiCell(770, 30, ($rep->formData['customer_ref']), 0, 'L', 0,1,406,134, true);
		//$rep->MultiCell(770, 30, "__________", 0, 'L', 0,1,400,110, true);
		$rep->MultiCell(770, 30, $sales_order['reference'], 0, 'L', 0,1,406,110, true);
       
       $rep->MultiCell(500, 30, "RECEIVE THE ABOVE NOTED MATERIAL AND ACKNOWLEDGE RECEIPT OUT BILL OF CHARGES WILL FOLLOW IN DUE COURSE.", 0, 'L', 0,1,40,700, true);
       
		$rep->MultiCell(770, 30, "Prepared By:", 0, 'L', 0,1,40,750, true);

		$rep->MultiCell(770, 30, "________________________", 0, 'L', 0,1,100,750, true);
		$rep->MultiCell(770, 30, "________________________", 0, 'L', 0,1,410,750, true);
		$rep->MultiCell(770, 30, "Recieved By:", 0, 'L', 0,1,357,750, true);
		$rep->MultiCell(50, 514, "", 1, 'L', 0,1,40,178, true);
		$rep->MultiCell(120, 514, "", 1, 'L', 0,1,330,178, true);
		$rep->MultiCell(180, 514, "", 1, 'L', 0,1,150,178, true);
		//$rep->MultiCell(55, 514, "", 1, 'L', 0,1,450,178, true);

	}
	if ($email == 0)
		$rep->End();
}

