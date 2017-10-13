<?php
$page_security = 'SA_CUSTPAYMREP';

// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Salesman Balances
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

function get_open_balance($debtorno, $to)
{
	if($to)
		$to = date2sql($to);

    $sql = "SELECT SUM(IF(t.type = ".ST_SALESINVOICE." OR t.type = ".ST_BANKPAYMENT.",
    	(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount), 0)) AS charges, 
    	SUM(IF(t.type <> ".ST_SALESINVOICE." AND t.type <> ".ST_BANKPAYMENT." OR t.type = ".ST_CPV.",
	    	(t.ov_amount + t.ov_gst + t.gst_wh + t.ov_freight + t.ov_freight_tax + t.ov_discount) * -1, 0)) AS credits,
		SUM(t.alloc) AS Allocated,
		SUM(IF(t.type = ".ST_SALESINVOICE." OR t.type = ".ST_BANKPAYMENT." OR t.type = ".ST_CPV.",
			(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount - t.alloc),
	    	((t.ov_amount + t.ov_gst + t.gst_wh + t.ov_freight + t.ov_freight_tax + t.ov_discount) * -1 + t.alloc))) AS OutStanding
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
		(".TB_PREF."debtor_trans.ov_amount + ".TB_PREF."debtor_trans.ov_gst +  
		".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount  )
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


//----------------------------------------------------------------------------------------------------

function print_customer_balances()
{
    	global $path_to_root, $systypes_array;

    	$from = $_POST['PARAM_0'];
    	$to = $_POST['PARAM_1'];	
    	$comments = $_POST['PARAM_2'];
	$orientation = $_POST['PARAM_3'];
	
	$orientation = ($orientation ? 'L' : 'P');

    	$dec = user_price_dec();


	$cols = array(0, 140, 150, 180,  200, 240, 280, 335, 400, 460, 530, 600);

	$headers = array(_('Salesman Name.'), _(''), _(''), _(''), _(''), _(''),
		_(''), 	_(''), _(''), _('Balance'));

	$aligns = array('left',	'left',	'left',	'left',	'right', 'right', 'right', 'right', 'right', 'right', 'right');

    $params =   array( 	0 => '',
);


    $rep = new FrontReport(_('Salesman Balances Report'), "SalesmanBalances", user_pagesize());
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

	$sql1 = "SELECT ".TB_PREF."salesman.salesman_code ,
			".TB_PREF."salesman.salesman_name 
		FROM ".TB_PREF."salesman";
			$sql1 .= " WHERE ".TB_PREF."salesman.inactive !=1";
	$sql1 .= " ORDER BY ".TB_PREF."salesman.salesman_name";	
	$supresult = db_query($sql1, "The customers could not be retrieved");
while ($supmyrow = db_fetch($supresult))
	{	
	
	
	
	$sql = "SELECT ".TB_PREF."debtors_master.debtor_no AS DebtorNo,
			".TB_PREF."debtors_master.name AS Name
		FROM ".TB_PREF."debtors_master
		INNER JOIN ".TB_PREF."cust_branch
			ON ".TB_PREF."debtors_master.debtor_no=".TB_PREF."cust_branch.debtor_no
		INNER JOIN ".TB_PREF."areas
			ON ".TB_PREF."cust_branch.area = ".TB_PREF."areas.area_code			
		INNER JOIN ".TB_PREF."salesman
			ON ".TB_PREF."cust_branch.salesman=".TB_PREF."salesman.salesman_code";

if ($supmyrow['salesman_code'] != 0 )
			{
				$sql .= " WHERE ".TB_PREF."salesman.salesman_code=".$supmyrow['salesman_code'];
			}			
		

	$sql .= " ORDER BY Name";	
	$result = db_query($sql, "The customers could not be retrieved");
	$num_lines = 0;			
   $rep->TextCol(0, 5, $supmyrow['salesman_name']);
	while ($myrow = db_fetch($result))
	{
       	$deb_sum_total = array(0,0,0,0);	
		$deb_sum = array(0,0,0,0);	
		$bal = get_open_balance($myrow['DebtorNo'], $from, $convert);
		$init[0] = $init[1] = 0.0;
		$init[0] = round2(abs($bal['charges']), $dec);
		$init[1] = round2(Abs($bal['credits']), $dec);
		$init[2] = round2($bal['OutStanding'], $dec);;
		$res = get_transactions($myrow['DebtorNo'], $from, $to);
		if ($no_zeros && db_num_rows($res) == 0) continue;
 		$num_lines++;
		$total = array(0,0,0,0);
		for ($i = 0; $i < 4; $i++)
		{
			$total[$i] += $init[$i];
			$grandtotal[$i] += $init[$i];
		}
		
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
			    $foo = true;
				$a = 1;
				$item[0] = round2(abs($trans['TotalAmount']) * $rate, $dec);							
				$res2 = get_transactions2($myrow['DebtorNo'], $from, $to, $trans['trans_no']);								
				while ($trans2 = db_fetch($res2))
		
 				{				

				$DiscountAmount= (($trans2['unit_price'] * $trans2['quantity']) * $trans2['discount_percent']);			
				$TotalAmount = (($trans2['unit_price'] * $trans2['quantity']) - $DiscountAmount);
				
				$deb_sum['0'] = $trans2['unit_price'];
				$deb_sum['1'] = $trans2['quantity'];
				$deb_sum['2'] = $DiscountAmount;
				$deb_sum['3'] = $TotalAmount;
				
					for ($i = 0; $i < 4; $i++)
		    		{
			 		$deb_sum_total[$i] += $deb_sum[$i];
					$deb_sum_total_grandtotal[$i] += $deb_sum[$i];
					}
				
				
              }//while	
			 
			}//if
			else
			{
				$item[1] = round2(Abs($trans['TotalAmount']) * $rate, $dec);						
			} //else
            if ($trans['type'] == ST_SALESINVOICE || $trans['type'] == ST_BANKPAYMENT || $trans['type'] == ST_CPV)
				$item[2] = $item[0] - $item[1] ; // previously $item[0] + $item[1] dz
			else	
				$item[2] = $item[0] - $item[1] ;
		
		
		for ($i = 0; $i < 3; $i++)
			{
				$total[$i] += $item[$i];
				$grandtotal[$i] += $item[$i];
			}			
		}	
	}
	$rep->AmountCol( 9,  10, $grandtotal[2], $dec);
	
		$rep->NewLine();
          $grandtotal[0]=0;
	  $grandtotal[1]=0;
	  $grandtotal[2]=0;
	}
  

	$rep->Font();				
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    	$rep->End();
}
?>