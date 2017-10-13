<?php
$page_security = 'SA_CUSTPAYMREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Customer Balances Summary
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_aged_customer_analysis();

function get_invoices($customer_id, $to, $all=true)
{
	$todate = date2sql($to);
	$PastDueDays1 = get_company_pref('past_due_days');
	$PastDueDays2 = 2 * $PastDueDays1;

	// Revomed allocated from sql
	if ($all)
    	$value = "(".TB_PREF."debtor_trans.ov_amount + ".TB_PREF."debtor_trans.ov_gst + "
			.TB_PREF."debtor_trans.ov_freight + ".TB_PREF."debtor_trans.ov_freight_tax + "
			.TB_PREF."debtor_trans.ov_discount  + ".TB_PREF."debtor_trans.gst_wh - ".TB_PREF."debtor_trans.discount1 - ".TB_PREF."debtor_trans.discount2)";
	else		
    	$value = "(".TB_PREF."debtor_trans.ov_amount + ".TB_PREF."debtor_trans.ov_gst + "
			.TB_PREF."debtor_trans.ov_freight + ".TB_PREF."debtor_trans.ov_freight_tax + "
			.TB_PREF."debtor_trans.ov_discount + "
			.TB_PREF."debtor_trans.gst_wh - ".TB_PREF."debtor_trans.discount1 - ".TB_PREF."debtor_trans.discount2 - ".TB_PREF."debtor_trans.alloc)";
	$due = "IF (".TB_PREF."debtor_trans.type=".ST_SALESINVOICE.",".TB_PREF."debtor_trans.due_date,".TB_PREF."debtor_trans.tran_date)";
	$sql = "SELECT ".TB_PREF."debtor_trans.type, ".TB_PREF."debtor_trans.reference,
		".TB_PREF."debtor_trans.tran_date,
		$value as Balance,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= 0,$value,0) AS Due,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $PastDueDays1,$value,0) AS Overdue1,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $PastDueDays2,$value,0) AS Overdue2

		FROM ".TB_PREF."debtors_master,
			".TB_PREF."debtor_trans

		WHERE ".TB_PREF."debtor_trans.type <> ".ST_CUSTDELIVERY."
			AND ".TB_PREF."debtors_master.debtor_no = ".TB_PREF."debtor_trans.debtor_no
			AND ".TB_PREF."debtor_trans.debtor_no = $customer_id 
			AND ".TB_PREF."debtor_trans.tran_date <= '$todate'
			AND ABS(".TB_PREF."debtor_trans.ov_amount + ".TB_PREF."debtor_trans.ov_gst + ".TB_PREF."debtor_trans.ov_freight + ".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount  + ".TB_PREF."debtor_trans.gst_wh - ".TB_PREF."debtor_trans.discount1 - ".TB_PREF."debtor_trans.discount2) > ".FLOAT_COMP_DELTA." ";
	if (!$all)
		$sql .= "AND ABS(".TB_PREF."debtor_trans.ov_amount + ".TB_PREF."debtor_trans.ov_gst + ".TB_PREF."debtor_trans.ov_freight + ".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount  + ".TB_PREF."debtor_trans.gst_wh - ".TB_PREF."debtor_trans.discount1 - ".TB_PREF."debtor_trans.discount2 - ".TB_PREF."debtor_trans.alloc) > ".FLOAT_COMP_DELTA." ";  
	$sql .= "ORDER BY ".TB_PREF."debtor_trans.tran_date";

	return db_query($sql, "The customer details could not be retrieved");
}

//----------------------------------------------------------------------------------------------------

function print_aged_customer_analysis()
{
    global $path_to_root, $systypes_array;

    	$to = $_POST['PARAM_0'];
    	$fromcust = $_POST['PARAM_1'];
        $area = $_POST['PARAM_2'];
    	$folk = $_POST['PARAM_3'];					
    	$currency = $_POST['PARAM_4'];
    	//$show_all = $_POST['PARAM_5'];
    	$no_zeros = $_POST['PARAM_5'];
    	$graphics = $_POST['PARAM_6'];
    	$comments = $_POST['PARAM_7'];
	$orientation = $_POST['PARAM_8'];
	$destination = $_POST['PARAM_9'];


	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");				
	else		
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
	if ($graphics)
	{
		include_once($path_to_root . "/reporting/includes/class.graphic.inc");
		$pg = new graph();
	}

	if ($fromcust == ALL_TEXT)
		$from = _('All');
	else
		$from = get_customer_name($fromcust);
    	$dec = user_price_dec();
        $summaryOnly=1;
	if ($summaryOnly == 1)
		$summary = _('Summary Only');
	else
		$summary = _('Detailed Report');
	if ($currency == ALL_TEXT)
	{
		$convert = true;
		$currency = _('Balances in Home Currency');
	}
	else
		$convert = false;

	if ($no_zeros) $nozeros = _('Yes');
	else $nozeros = _('No');

        $show_all=1;

	if ($show_all) $show = _('Yes');
	else $show = _('No');

	if ($fromcust == ALL_TEXT)
		$from = _('All');
	else
		$from = get_customer_name($fromcust);
    	$dec = user_price_dec();
		
			if ($area == ALL_NUMERIC)
		$area = 0;
		
	if ($area == 0)
		$sarea = _('All Areas');
	else
		$sarea = get_area_name($area);
		
	if ($folk == ALL_NUMERIC)
		$folk = 0;

	if ($folk == 0)
		$salesfolk = _('All Sales Man');
	else
		$salesfolk = get_salesman_name($folk);
		
	$PastDueDays1 = get_company_pref('past_due_days');
	$PastDueDays2 = 2 * $PastDueDays1;
	$nowdue = "1-" . $PastDueDays1 . " " . _('Days');
	$pastdue1 = $PastDueDays1 + 1 . "-" . $PastDueDays2 . " " . _('Days');
	$pastdue2 = _('Over') . " " . $PastDueDays2 . " " . _('Days');

	$cols = array(0, 100, 480, 520);
	$headers = array(_('Customer Name'), _('Total Balance'), _(''));

	$aligns = array('left',	'right', 'right');

    	$params =   array( 	0 => $comments,
    				1 => array('text' => _('End Date'), 'from' => $to, 'to' => ''),
    				2 => array('text' => _('Customer'),	'from' => $from, 'to' => ''),
    				3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
                    4 => array('text' => _('Show Also Allocated'), 'from' => $show, 'to' => ''),		
				    5 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => ''),
    				6 => array('text' => _('Zone'), 		'from' => $sarea, 		'to' => ''),						
    				7 => array('text' => _('Sales Man'), 		'from' => $salesfolk, 	'to' => ''),				
				);

	if ($convert)
		$headers[2] = _('');

    $rep = new FrontReport(_('Customer Balances Summary'), "CustomerBalancesSummary", user_pagesize(), 9, $orientation);

if ($orientation == 'L')
    	recalculate_cols($cols);
 
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

	$total = array(0,0,0,0, 0);

	$sql = "SELECT ".TB_PREF."debtors_master.debtor_no,
			".TB_PREF."debtors_master.name 
		FROM ".TB_PREF."debtors_master
		INNER JOIN ".TB_PREF."cust_branch
			ON ".TB_PREF."debtors_master.debtor_no=".TB_PREF."cust_branch.debtor_no
		INNER JOIN ".TB_PREF."areas
			ON ".TB_PREF."cust_branch.area = ".TB_PREF."areas.area_code			
		INNER JOIN ".TB_PREF."salesman
			ON ".TB_PREF."cust_branch.salesman=".TB_PREF."salesman.salesman_code";

		if ($fromcust != ALL_TEXT )
			{
				if ($area != 0 || $folk != 0);
				$sql .= " WHERE ".TB_PREF."debtors_master.debtor_no=".db_escape($fromcust);
			}
	
		elseif ($area != 0)
			{
				if ($folk != 0)
					$sql .= " WHERE ".TB_PREF."salesman.salesman_code=".db_escape($folk)."
						AND ".TB_PREF."areas.area_code=".db_escape($area);
				else
					$sql .= " WHERE ".TB_PREF."areas.area_code=".db_escape($area);
			}			
		elseif ($folk != 0 )
			{
				$sql .= " WHERE ".TB_PREF."salesman.salesman_code=".db_escape($folk);
			}			
		

	$sql .= " ORDER BY name";	
	$result = db_query($sql, "The customers could not be retrieved");
	


	while ($myrow=db_fetch($result))
	{
		if (!$convert && $currency != $myrow['curr_code'])
			continue;

		if ($convert) $rate = get_exchange_rate_from_home_currency($myrow['curr_code'], $to);
		else $rate = 1.0;
		$custrec = get_customer_details($myrow['debtor_no'], $to, $show_all); 
		if (!$custrec)
			continue;
		$custrec['Balance'] *= $rate;
		$custrec['Due'] *= $rate;
		$custrec['Overdue1'] *= $rate;
		$custrec['Overdue2'] *= $rate;
		$str = array(
			$custrec["Balance"]);
		if ($no_zeros && floatcmp(array_sum($str), 0) == 0) continue;

		$rep->fontSize += 2;
		$rep->TextCol(0, 4, $myrow['name']);
		if ($convert) //$rep->TextCol(2, 3,	$myrow['curr_code']);
		$rep->fontSize -= 2;
	
		$total[4] += $custrec["Balance"];
		for ($i = 0; $i < count($str); $i++)
			$rep->AmountCol(1, 2, $str[$i], $dec);
		$rep->Line($rep->row - 2);
		
		$rep->NewLine(1.2);
		
		if (!$summaryOnly)
		{
		//	$res = get_invoices($myrow['debtor_no'], $to, $show_all);
    		if (db_num_rows($res)==0)
				continue;
    		$rep->Line($rep->row + 4);
			while ($trans=db_fetch($res))
			{
				$rep->NewLine(1, 2);
        		$rep->TextCol(0, 1, $systypes_array[$trans['type']], -2);
				$rep->TextCol(1, 2,	$trans['reference'], -2);
				$rep->DateCol(2, 3, $trans['tran_date'], true, -2);
				if ($trans['type'] == ST_CUSTCREDIT || $trans['type'] == ST_CUSTPAYMENT || $trans['type'] == ST_BANKDEPOSIT || $trans['type'] == ST_CRV)
				{
					$trans['Balance'] *= -1;
					$trans['Due'] *= -1;
					$trans['Overdue1'] *= -1;
					$trans['Overdue2'] *= -1;
				}
				foreach ($trans as $i => $value)
					$trans[$i] *= $rate;
				$str = array(
					$trans["Balance"]);
				//for ($i = 0; $i < count($str); $i++)
				//	$rep->AmountCol($i + 3, $i + 8, $str[$i], $dec);
				
			}
			$rep->Line($rep->row - 8);
			$rep->NewLine(2);
		}
	}
	if ($summaryOnly)
	{
    	$rep->Line($rep->row  + 4);
    	$rep->NewLine();
	}
	$rep->fontSize += 2;
	$rep->TextCol(0, 3, _('Grand Total'));
	$rep->fontSize -= 2;
	for ($i = 4; $i < count($total); $i++)
	{
		$rep->AmountCol(1, 2, $total[$i], $dec);
		if ($graphics && $i < count($total) - 1)
		{
			$pg->y[$i] = abs($total[$i]);
		}
	}
   	$rep->Line($rep->row - 8);
   	if ($graphics)
   	{
   		global $decseps, $graph_skin;
		$pg->x = array(_('Current'), $nowdue, $pastdue1, $pastdue2);
		$pg->title     = $rep->title;
		$pg->axis_x    = _("Days");
		$pg->axis_y    = _("Amount");
		$pg->graphic_1 = $to;
		$pg->type      = $graphics;
		$pg->skin      = $graph_skin;
		$pg->built_in  = false;
		$pg->latin_notation = ($decseps[$_SESSION["wa_current_user"]->prefs->dec_sep()] != ".");
		$filename = company_path(). "/pdf_files/". uniqid("").".png";
		$pg->display($filename, true);
		$w = $pg->width / 1.5;
		$h = $pg->height / 1.5;
		$x = ($rep->pageWidth - $w) / 2;
		$rep->NewLine(2);
		if ($rep->row - $h < $rep->bottomMargin)
			$rep->NewPage();
		$rep->AddImage($filename, $x, $rep->row - $h, $w, $h);
	}


	$rep->NewLine();
    $rep->End();
}

?>