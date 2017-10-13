<?php
$page_security = 'SA_CUSTPAYMREP';

// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Customer Balances
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/sales/includes/db/customers_db.inc");

//----------------------------------------------------------------------------------------------------

// trial_inquiry_controls();
print_customer_balances();
/*
function get_open_balance($debtorno, $to, $convert)
{
	if($to)
		$to = date2sql($to);

    $sql = "SELECT SUM(IF(t.type = ".ST_SALESINVOICE.",
    	(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount - t.discount1 - t.discount2)";
    if ($convert)
    	$sql .= " * rate";
    $sql .= ", 0)) AS charges,
    	SUM(IF(t.type <> ".ST_SALESINVOICE.",
    	(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount - t.discount1 - t.discount2)";
    if ($convert)
    	$sql .= " * rate";
    $sql .= " * -1, 0)) AS credits,
		SUM(t.alloc";
	if ($convert)
		$sql .= " * rate";
	$sql .= ") AS Allocated,
		SUM(IF(t.type = ".ST_SALESINVOICE.",
			(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount - t.discount1 - t.discount2 - t.alloc)";
    if ($convert)
    	$sql .= " * rate";
    $sql .= ", 
    	((t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount - t.discount1 - t.discount2) * -1 + t.alloc)";
    if ($convert)
    	$sql .= " * rate";
    $sql .= ")) AS OutStanding
		FROM ".TB_PREF."debtor_trans t
    	WHERE t.debtor_no = ".db_escape($debtorno)."
		AND t.type <> ".ST_CUSTDELIVERY;
    if ($to)
    	$sql .= " AND t.tran_date < '$to'";
	$sql .= " GROUP BY t.debtor_no";

    $result = db_query($sql,"No transactions were returned");
    return db_fetch($result);
}*/
function get_open_balance($debtorno, $to)
{
	if($to)
		$to = date2sql($to);

     $sql = "SELECT SUM(IF(t.type = ".ST_SALESINVOICE." OR (t.type = ".ST_JOURNAL." AND t.ov_amount>0),
     	-abs(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount - t.discount1 - t.discount2), 0)) AS charges,";
     $sql .= "SUM(IF(t.type != ".ST_SALESINVOICE." AND (t.type = ".ST_JOURNAL." AND t.ov_amount<0),
     	abs(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount - t.discount1 - t.discount2) * -1, 0)) AS credits,";
    $sql .= "SUM(IF(t.type != ".ST_SALESINVOICE." AND NOT(t.type = ".ST_JOURNAL." AND t.ov_amount<0), t.alloc * -1, t.alloc)) AS Allocated,";

 	$sql .=	"SUM(IF(t.type = ".ST_SALESINVOICE.", 1, -1) *
 			(-abs(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount - t.discount1 - t.discount2) - abs(t.alloc))) AS OutStanding
		FROM ".TB_PREF."debtor_trans t
    	WHERE t.debtor_no = ".db_escape($debtorno)
		." AND t.type <> ".ST_CUSTDELIVERY;
    if ($to)
    	$sql .= " AND t.tran_date < '$to'";
	$sql .= " GROUP BY debtor_no";

    $result = db_query($sql,"No transactions were returned");
    return db_fetch($result);
}
function get_transactions($debtorno, $from, $to)
{
	$from = date2sql($from);
	$to = date2sql($to);

    $sql = "SELECT ".TB_PREF."debtor_trans.*,
		(".TB_PREF."debtor_trans.ov_amount + ".TB_PREF."debtor_trans.ov_gst + ".TB_PREF."debtor_trans.ov_freight + 
		".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount - ".TB_PREF."debtor_trans.discount1 - ".TB_PREF."debtor_trans.discount2)
		AS TotalAmount, ".TB_PREF."debtor_trans.alloc AS Allocated,
		((".TB_PREF."debtor_trans.type = ".ST_SALESINVOICE.")
		AND ".TB_PREF."debtor_trans.due_date < '$to') AS OverDue
    	FROM ".TB_PREF."debtor_trans
    	WHERE ".TB_PREF."debtor_trans.tran_date >= '$from'
		AND ".TB_PREF."debtor_trans.tran_date <= '$to'
		AND ".TB_PREF."debtor_trans.debtor_no = ".db_escape($debtorno)."
		AND ".TB_PREF."debtor_trans.type <> ".ST_CUSTDELIVERY."
    	ORDER BY ".TB_PREF."debtor_trans.tran_date";

    return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_customer_balances()
{
    	global $path_to_root, $systypes_array;

    	$from = $_POST['PARAM_0'];
    	$to = $_POST['PARAM_1'];
    	$fromcust = $_POST['PARAM_2'];
    	$currency = $_POST['PARAM_3'];
    	$no_zeros = $_POST['PARAM_4'];
    	$comments = $_POST['PARAM_5'];
	$destination = $_POST['PARAM_6'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	if ($fromcust == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($fromcust);
    	$dec = user_price_dec();

	if ($currency == ALL_TEXT)
	{
		$convert = true;
		$currency = _('Balances in Home Currency');
	}
	else
		$convert = false;

	if ($no_zeros) $nozeros = _('Yes');
	else $nozeros = _('No');

	$cols = array(0, 100, 130, 190,	260, 340, 430, 515);

	$headers = array(_('Customer'), _(''), _(''), _('Opening'), _('Charges'), _('Payments'),
		_('Outstanding'));

	$aligns = array('left',	'left',	'left',	'right',	'right', 'right', 'right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
    				    3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						4 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => ''));

    $rep = new FrontReport(_('Customer Movements'), "CustomerBalances", user_pagesize());
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

	$grandtotal = array(0,0,0,0);

	$sql = "SELECT debtor_no, name, debtor_ref, curr_code FROM ".TB_PREF."debtors_master ";
	if ($fromcust != ALL_TEXT)
	{	$sql .= "WHERE debtor_no=".db_escape($fromcust); }
	else
	{	$sql .= "WHERE sales_type = 1 " ; }

	$sql .= " ORDER BY debtor_ref";
	$result = db_query($sql, "The customers could not be retrieved");
	$num_lines = 0;

	while ($myrow = db_fetch($result))
	{
		if (!$convert && $currency != $myrow['curr_code']) continue;

		$bal = get_open_balance($myrow['debtor_no'], $from, $convert);
		$init[0] = $init[1] = 0.0;
		$init[0] = round2(abs($bal['charges']), $dec);
		$init[1] = round2(Abs($bal['credits']), $dec);
		$init[2] = round2($bal['Allocated'], $dec);
		$init[3] = round2($bal['OutStanding'], $dec);;

		$res = get_transactions($myrow['debtor_no'], $from, $to);
		if ($no_zeros && db_num_rows($res) == 0) continue;

 		$num_lines++;
		$rep->fontSize += 2;
//		$rep->TextCol(0, 2, $myrow['debtor_Ref'] . "-" . $myrow['name']);
		$rep->fontSize -= 2;
		$total = array(0,0,0,0);
		$totaldr = 0;
		$totalcr = 0;

	//	if (db_num_rows($res)==0)
	//		continue;
		while ($trans = db_fetch($res))
		{
			if ($no_zeros && floatcmp($trans['TotalAmount'], $trans['Allocated']) == 0) continue;
			$item[0] = $item[1] = 0.0;
			if ($convert)
				$rate = $trans['rate'];
			else
				$rate = 1.0;
			if ($trans['type'] == ST_CUSTCREDIT || $trans['type'] == ST_CUSTPAYMENT || $trans['type'] == ST_BANKDEPOSIT || $trans['type'] == ST_CRV)
				$trans['TotalAmount'] *= -1;
			if ($trans['TotalAmount'] > 0.0)
			{
				$item[0] = round2(abs($trans['TotalAmount']) * $rate, $dec);
//				$rep->AmountCol(4, 5, $item[0], $dec);
			}
			else
			{
				$item[1] = round2(Abs($trans['TotalAmount']) * $rate, $dec);
//				$rep->AmountCol(5, 6, $item[1], $dec);
			}
			$item[2] = round2($trans['Allocated'] * $rate, $dec);
//			$rep->AmountCol(6, 7, $item[2], $dec);

			if ($trans['type'] == ST_SALESINVOICE || $trans['type'] == ST_BANKPAYMENT || $trans['type'] == ST_CPV)
				$item[3] = $item[0] + $item[1] - $item[2];
			else	
				$item[3] = $item[0] - $item[1] + $item[2];

			for ($i = 0; $i < 2; $i++)
			{
				$total[$i] += $item[$i];
				$grandtotal[$i] += $item[$i];
			}

				$totaldr += $item[0];
				$totalcr += $item[1];


				$grandtotaldr += $item[0];
				$grandtotalcr += $item[1];


		}
				$grandtotalop += $init[3];
				$cust_balance = $init[3] + $totaldr - $totalcr; //closing balance
				$total_cust_balance += $cust_balance;

		$rep->TextCol(0, 3, $myrow['debtor_ref'] . " - " . $myrow['name']);
		$rep->AmountCol(3, 4, $init[3], $dec);

		for ($i = 0; $i < 2; $i++)
			$rep->AmountCol($i + 4, $i + 5, $total[$i], $dec);
		$rep->AmountCol(6, 7, $cust_balance, $dec);

    		$rep->NewLine();
	}

	$rep->fontSize += 2;
	$rep->TextCol(0, 3, _('Grand Total'));
	$rep->fontSize -= 2;

		$rep->AmountCol(3, 4, $grandtotalop, $dec);
		$rep->AmountCol(4, 5, $grandtotaldr, $dec);
		$rep->AmountCol(5, 6, $grandtotalcr, $dec);
		$rep->AmountCol(6, 7, $total_cust_balance, $dec);


	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    	
$rep->End();
}

?>
