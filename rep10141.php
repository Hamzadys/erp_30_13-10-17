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


function get_open_balance($debtorno, $to, $convert)
{
	if($to)
		$to = date2sql($to);

    $sql = "SELECT SUM(IF(t.type = ".ST_SALESINVOICE.",
    	(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount)";
    if ($convert)
    	$sql .= " * rate";
    $sql .= ", 0)) AS charges,
    	SUM(IF(t.type <> ".ST_SALESINVOICE.",
    	(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount)";
    if ($convert)
    	$sql .= " * rate";
    $sql .= " * -1, 0)) AS credits,
		SUM(t.alloc";
	if ($convert)
		$sql .= " * rate";
	$sql .= ") AS Allocated,
		SUM(IF(t.type = ".ST_SALESINVOICE.",
			(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount - t.alloc)";
    if ($convert)
    	$sql .= " * rate";
    $sql .= ", 
    	((t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount) * -1 + t.alloc)";
    if ($convert)
    	$sql .= " * rate";
    $sql .= ")) AS OutStanding
		FROM ".TB_PREF."debtor_trans t
    	WHERE t.debtor_no = ".db_escape($debtorno)
		." AND t.type <> ".ST_CUSTDELIVERY;
    if ($to)
    	$sql .= " AND t.tran_date < '$to'";
	$sql .= " GROUP BY debtor_no";

    $result = db_query($sql,"No transactions were returned");
    return db_fetch($result);
}
//    	(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount), 0)) AS sales,
// dz - check the GST amount by copying into some other db where GST is used
function get_trans($debtorno, $from, $to, $branch_code)
{
	
	$from = date2sql($from);
	$to = date2sql($to);


    $sql = " SELECT  SUM(IF(t.type = ".ST_BANKDEPOSIT." OR t.type = ".ST_CUSTPAYMENT.",
    	(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount), 0)) AS receipts,
    	SUM(IF(t.type = ".ST_SALESINVOICE.",
    	( (d.unit_price*d.quantity) + (d.unit_tax*d.quantity*d.unit_price) - (d.discount_percent*d.quantity*d.unit_price) ), 0)) AS sales,
    	SUM(IF(t.type = ".ST_CUSTCREDIT." AND s.mb_flag != 'D',
    	((d.unit_price*d.quantity) + (d.unit_tax*d.quantity*d.unit_price) - (d.discount_percent*d.quantity*d.unit_price)), 0)) AS sales_return,
    		SUM(IF(t.type = ".ST_BANKPAYMENT.",
    	(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount), 0)) AS payments,
    	SUM(IF(t.type = ".ST_CUSTCREDIT." AND s.mb_flag = 'D',
	    	((d.unit_price*d.quantity) + (d.unit_tax*d.quantity*d.unit_price) - (d.discount_percent*d.quantity*d.unit_price)) * -1, 0)) AS credits,
		SUM(t.alloc) AS Allocated,
		SUM(IF(t.type = ".ST_SALESINVOICE." OR t.type = ".ST_BANKPAYMENT.",
			(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount - t.alloc),
	    	((t.ov_amount + t.ov_gst + + t.gst_wh + t.ov_freight + t.ov_freight_tax + t.ov_discount) * -1 + t.alloc))) AS OutStanding
			FROM ".TB_PREF."debtor_trans t

		LEFT JOIN ".TB_PREF."debtor_trans_details d
			ON d.debtor_trans_type  =  t.type
			AND d.debtor_trans_no =  t.trans_no 

		LEFT JOIN ".TB_PREF."stock_master s
			ON d.stock_id  =  s.stock_id


		WHERE t.debtor_no = ".db_escape($debtorno)." 

		AND t.branch_code = '$branch_code'
		AND t.type <> ".ST_CUSTDELIVERY;


    	$sql .= " AND t.tran_date >= '$from'";
    	$sql .= " AND t.tran_date <= '$to'";

	$sql .= " GROUP BY t.debtor_no";

    $result = db_query($sql,"No transactions were returned");
    return db_fetch($result);
}

function get_transactions($debtorno, $from, $to, $branch_code)
{
	$from = date2sql($from);
	$to = date2sql($to);

    $sql = "SELECT ".TB_PREF."debtor_trans.*,
		(".TB_PREF."debtor_trans.ov_amount + ".TB_PREF."debtor_trans.ov_gst + ".TB_PREF."debtor_trans.ov_freight + 
		".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount + ".TB_PREF."debtor_trans.gst_wh)
		AS TotalAmount, ".TB_PREF."debtor_trans.alloc AS Allocated,
		((".TB_PREF."debtor_trans.type = ".ST_SALESINVOICE.")
		AND ".TB_PREF."debtor_trans.due_date < '$to') AS OverDue
    	FROM ".TB_PREF."debtor_trans
    	WHERE ".TB_PREF."debtor_trans.tran_date >= '$from'
		AND ".TB_PREF."debtor_trans.tran_date <= '$to'
		AND ".TB_PREF."debtor_trans.debtor_no = ".db_escape($debtorno);
if ($branch_code != ALL_TEXT )
$sql .= " AND ".TB_PREF."debtor_trans.branch_code = ".db_escape($branch_code);
		$sql .= " AND ".TB_PREF."debtor_trans.type <> ".ST_CUSTDELIVERY."
    	ORDER BY ".TB_PREF."debtor_trans.tran_date";

    return db_query($sql,"No transactions were returned");
}

function get_transactions2($debtorno, $from, $to, $transno)
{
	$from = date2sql($from);
	$to = date2sql($to);

    $sql = "SELECT ".TB_PREF."debtor_trans.*, ".TB_PREF."debtor_trans_details.*
		
    	FROM ".TB_PREF."debtor_trans, ".TB_PREF."debtor_trans_details
    	WHERE ".TB_PREF."debtor_trans.tran_date >= '$from'
		AND ".TB_PREF."debtor_trans.tran_date <= '$to'
		AND ".TB_PREF."debtor_trans.debtor_no = ".db_escape($debtorno)."
		AND ".TB_PREF."debtor_trans_details.debtor_trans_type  =  ".TB_PREF."debtor_trans.type
		AND ".TB_PREF."debtor_trans_details.debtor_trans_no =  ".TB_PREF."debtor_trans.trans_no 
		AND ".TB_PREF."debtor_trans_details.debtor_trans_no =  ".db_escape($transno)."
	AND ".TB_PREF."debtor_trans_details.debtor_trans_type = 10	
    	ORDER BY ".TB_PREF."debtor_trans.tran_date";

    return db_query($sql,"No transactions were returned");
}
function get_customer_name_data($fromcust, $area, $branch_code, $folk, $groups)
{
	$sql = "SELECT ".TB_PREF."debtors_master.debtor_no,
			".TB_PREF."debtors_master.name,
			".TB_PREF."debtors_master.debtor_ref,
			".TB_PREF."debtors_master.curr_code,
			".TB_PREF."cust_branch.br_name,
			".TB_PREF."cust_branch.branch_code
		FROM ".TB_PREF."debtors_master
		INNER JOIN ".TB_PREF."cust_branch
			ON ".TB_PREF."debtors_master.debtor_no=".TB_PREF."cust_branch.debtor_no
		INNER JOIN ".TB_PREF."areas
			ON ".TB_PREF."cust_branch.area = ".TB_PREF."areas.area_code			
		INNER JOIN ".TB_PREF."salesman
			ON ".TB_PREF."cust_branch.salesman=".TB_PREF."salesman.salesman_code


			WHERE ".TB_PREF."debtors_master.debtor_no != 0

";
		if ($fromcust != ALL_TEXT )
			{
			$sql .= " AND ".TB_PREF."debtors_master.debtor_no=".db_escape($fromcust);
			}

		if ($branch_code != 0)
			{
			$sql .= " AND ".TB_PREF."cust_branch.branch_code=".db_escape($branch_code);
			}

		elseif ($area != 0)
			{
				if ($folk != -1 )
				{
					$sql .= " AND ".TB_PREF."salesman.salesman_code=".db_escape($folk)."
						AND ".TB_PREF."areas.area_code=".db_escape($area);
				}

				else
				{
					$sql .= " AND ".TB_PREF."areas.area_code=".db_escape($area);
				//	$sql .= " AND ".TB_PREF."salesman.salesman_code= ".db_escape($folk);
				}

			}
		elseif ($folk != -1)
			{
				$sql .= " AND ".TB_PREF."salesman.salesman_code= ".db_escape($folk);
			}
		elseif ($groups != 0)
			{
				$sql .= " AND ".TB_PREF."cust_branch.group_no= ".db_escape($groups);
			}

		$sql .= "  ORDER BY Name";
		return db_query($sql, "The customers could not be retrieved");
}
function area_loop()
{
	$sql = "SELECT * FROM ".TB_PREF."areas";
	 return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_customer_balances()
{
    	global $path_to_root, $systypes_array;

    	$from = $_POST['PARAM_0'];
    	$to = $_POST['PARAM_1'];
    	$fromcust = $_POST['PARAM_2'];
        $branch_code = $_POST['PARAM_3'];
		$area = $_POST['PARAM_4'];
		$folk = $_POST['PARAM_5'];
		$groups = $_POST['PARAM_6'];
    	$currency = $_POST['PARAM_7'];
    	$no_zeros = $_POST['PARAM_8'];
    	$comments = $_POST['PARAM_9'];
	$orientation = $_POST['PARAM_10'];
	$destination = $_POST['PARAM_11'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ('L');

	if ($fromcust == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($fromcust);
    	$dec = user_price_dec();

	if ($folk == -1)
		$folk_name = _('All');
	else
		$folk_name = get_salesman_name($folk);

	if ($area == -1)
			$area_name = _('All');
		else
			$area_name = get_area_name($area);

	if ($groups == 0)
		$groups_name = _('All');
	else
		$groups_name = get_sales_group_name($groups);

	if ($currency == ALL_TEXT)
	{
		$convert = true;
		$currency = _('Balances in Home Currency');
	}
	else
		$convert = false;

	if ($no_zeros) $nozeros = _('Yes');
	else $nozeros = _('No');


	$cols = array(0,20, 250, 330, 420,	500, 580, 660, 720);
	$headers = array(_('S.'), _('Customer Name '), _('Opening '), _('Sales'), _('Sales'), _('Receipts'),_('Payment'),_('Cr. Notes'),_('Balance'));
	$headers2 = array(_('No'), _(''), _('Balance'), _(''), _('Ret'), _(''),_(''),_(''),_(''));
	$aligns = array('left',	'left',	'left','left', 'left', 'left', 'left', 'left', 'right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
    				    3 => array('text' => _('Area'), 'from' => $area_name, 'to' => ''),
    				    4 => array('text' => _('Salesman'), 'from' => $folk_name, 'to' => ''),
    				    5 => array('text' => _('Groups'), 'from' => $groups_name, 'to' => ''),
    				    6 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						7 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => ''));

    $rep = new FrontReport(_('Customer Outstanding Summary'), "CustomerOutstandingSummary", user_pagesize(),9, $orientation);
//	if ($orientation == 'L')
//		recalculate_cols($cols);
    $rep->Font();
   // $rep->Info($params, $cols, $headers, $aligns);
	$rep->Info($params, $cols, $headers2, $aligns, $cols, $headers, $aligns);
    $rep->NewPage();

	$grandtotal = array(0,0,0,0);
	

	/*$sql = "SELECT debtor_no, name, debtor_ref, curr_code FROM ".TB_PREF."debtors_master ";
	if ($fromcust != ALL_TEXT)
	{	$sql .= "WHERE debtor_no=".db_escape($fromcust); }

	$sql .= " ORDER BY debtor_ref";*/
	
	$num_lines = 0;
	
	/*$area_ = area_loop();
	
	while ($myrow1=db_fetch($area_))
	{

		$rep->Font('bold');
		$rep->fontSize += 3;

		$rep->fontSize -= 3;

		$rep->Font('');*/
		$result = get_customer_name_data($fromcust, $area, $branch_code, $folk, $groups);
		//$branch_code_ = '';
	while ($myrow = db_fetch($result))
	{

		if (!$convert && $currency != $myrow['curr_code']) continue;

		$bal1 = get_open_balance($myrow['debtor_no'], $from, $myrow['branch_code']);
		$ob = round2($bal1['OutStanding'], $dec);;


		$bal = get_trans($myrow['debtor_no'], $from, $to, $myrow['branch_code']);
		$num_lines++;
		$total = array(0,0,0,0);
		$init[0] = $init[1] = 0.0;
		$init[0] = round2(abs($bal['sales']), $dec);
		$init[1] = round2(Abs($bal['sales_return']), $dec);
		$init[3] = round2(Abs($bal['payments']), $dec);
		$init[4] = round2(Abs($bal['receipts']), $dec);
		$init[2] = round2(Abs($bal['credits']), $dec);
		{
			//$init[2] = $init[0] - $init[1];
			//$accumulate += $init[2];
		}
		
       for ($i = 0; $i < 3; $i++)
		{
			$total[$i] += $init[$i];
			$grandtotal[$i] += $init[$i];
		}
		$res = get_transactions($myrow['debtor_no'], $from, $to, $myrow['branch_code']);
		if ($no_zeros && db_num_rows($res) == 0) continue;
 		
		$sales = 0;
		$totalcr = 0;
		$s += 1;
	
		while ($trans = db_fetch($res))
		{
			if ($no_zeros && floatcmp($trans['TotalAmount'], $trans['Allocated']) == 0) continue;
			$item[0] = $item[1] = 0.0;
			if ($convert)
				$rate = $trans['rate'];
			else
				$rate = 1.0;
			if ($trans['type'] == ST_CUSTCREDIT || $trans['type'] == ST_CUSTPAYMENT || $trans['type'] == ST_BANKDEPOSIT)
				$trans['TotalAmount'] *= -1;
			if ($trans['TotalAmount'] > 0.0)
			{
				$item[0] = round2(abs($trans['TotalAmount']) * $rate, $dec);
			}
			else
			{
				$item[1] = round2(Abs($trans['TotalAmount']) * $rate, $dec);
			}
			$item[2] = round2($trans['Allocated'] * $rate, $dec);

			if ($trans['type'] == ST_SALESINVOICE || $trans['type'] == ST_BANKPAYMENT)
				$item[3] = $item[0] + $item[1] - $item[2];
			else	
				$item[3] = $item[0] - $item[1] + $item[2];

			for ($i = 0; $i < 2; $i++)
			{
				$total[$i] += $item[$i];
				$grandtotal[$i] += $item[$i];
			}
				$sales += $item[0];
				//$totalcr += $item[1];

				$grandtotaldr += $item[0];
				$grandtotalcr += $item[1];

		}
				//$grandtotalop += $init[2];
				//$recipts = $init[2] + $sales - $totalcr;
		        $payments = $init[3] + $sales - $totalcr;//closing balance
				$total_cust_balance += $recipts;

		$gt_op += $ob;
		$gt_sales += $init[0];
		$gt_sales_return += $init[1];
		$gt_receipts += $init[4];
		$gt_payments += $init[3];
		$gt_cr_notes += $init[2];

		$total_cust_balance += $line_total;



		if($debtor_no != $myrow['debtor_no'])
		{
			$rep->TextCol(0, 1, $s, $dec);
			$rep->Font('bold');
			$rep->TextCol(1, 2, $myrow['name']);
			$rep->Font('');
		}

		$rep->Font('bold');
		$rep->Font('');

		$line_total = $ob + $init[0] - $init[1] - $init[2] - $init[4] - $init[3];

		$rep->AmountCol(2, 3, $ob, $dec); //opening balance
		$rep->AmountCol(3, 4, $init[0] , $dec); //sales
		$rep->AmountCol(4, 5, $init[1], $dec); //sales return
		$rep->AmountCol(5, 6, $init[4], $dec); //receipts
		$rep->AmountCol(6, 7, $init[3], $dec); //payment
		$rep->AmountCol(7, 8, $init[2], $dec); //credit notes
		$rep->AmountCol(8, 9, $line_total, $dec);

    		$rep->NewLine();
		$debtor_no = $myrow['debtor_no'];
//	}
	}
	$rep->NewLine();
	$rep->Line($rep->row  - 4);
	$rep->Font('bold');	
	$rep->fontSize += 2;
	$rep->TextCol(0, 3, _('Grand Total'));
	$rep->fontSize -= 2;

		$rep->AmountCol(2, 3, $gt_op, $dec);
		$rep->AmountCol(3, 4, $gt_sales, $dec);
		$rep->AmountCol(4, 5, $gt_sales_return, $dec);
		$rep->AmountCol(5, 6, $gt_receipts, $dec);		
		$rep->AmountCol(6, 7, $gt_payments, $dec);
		$rep->AmountCol(7, 8, $gt_cr_notes, $dec);
		$rep->AmountCol(8, 9, $total_cust_balance, $dec);

$rep->Font('');	
	
	$rep->NewLine();
    	
$rep->End();
}

?>
