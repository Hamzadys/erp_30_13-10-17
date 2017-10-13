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
print_customer_balances_();

/*
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
    	(t.ov_amount + t.ov_gst + t.gst_wh + t.ov_freight + t.ov_freight_tax + t.ov_discount)";
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
    	((t.ov_amount + t.ov_gst + t.gst_wh + t.ov_freight +  t.ov_freight_tax + t.ov_discount) * -1 + t.alloc)";
//    	((t.ov_amount + t.ov_gst + t.ov_freight +  t.ov_freight_tax + t.ov_discount) * -1 + t.alloc)";
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
*/
function get_sql_for_sales_orders_report($trans_type,$from, $to,$fromcust,$area,$folk,$currency)
{

    $sql = "SELECT 
			sorder.order_no,
			sorder.reference,
			debtor.name,
			branch.br_name,"
        .($filter=='InvoiceTemplates'
        || $filter=='DeliveryTemplates' ?
            "sorder.comments, " : "sorder.customer_ref, ")
        ."sorder.ord_date,
			sorder.delivery_date,
			sorder.deliver_to,
			Sum(line.unit_price*line.quantity*(1-line.discount_percent))+freight_cost AS OrderValue,
			sorder.type,
			debtor.curr_code,
			Sum(line.qty_sent) AS TotDelivered,
			Sum(line.quantity) AS TotQuantity
		FROM ".TB_PREF."sales_orders as sorder, "
        .TB_PREF."sales_order_details as line, "
        .TB_PREF."debtors_master as debtor, "
        .TB_PREF."cust_branch as branch
			WHERE sorder.order_no = line.order_no
			AND sorder.trans_type = line.trans_type
			AND sorder.trans_type = ".db_escape($trans_type)."
			AND sorder.debtor_no = debtor.debtor_no
			AND sorder.branch_code = branch.branch_code
			AND debtor.debtor_no = branch.debtor_no";

//    if (isset($trans_no) && $trans_no != "")
//    {
//        // search orders with number like
//        $number_like = "%".$trans_no;
//        $sql .= " AND sorder.order_no LIKE ".db_escape($number_like);
////				." GROUP BY sorder.order_no";
//    }
//    elseif ($ref != "")
//    {
//        // search orders with reference like
//        $number_like = "%".$ref."%";
//        $sql .= " AND sorder.reference LIKE ".db_escape($number_like);
////				." GROUP BY sorder.order_no";
//    }
//    else	// ... or select inquiry constraints
//    {
//        if ($filter!='DeliveryTemplates' && $filter!='InvoiceTemplates' && $filter!='OutstandingOnly')
//        {
            $date_after = date2sql($from);
            $date_before = date2sql($to);

            $sql .=  " AND sorder.ord_date >= '$date_after'"
                ." AND sorder.ord_date <= '$date_before'";
//        }
//    }
//    if ($trans_type == ST_SALESQUOTE && !check_value('show_all'))
//        $sql .= " AND sorder.delivery_date >= '".date2sql(Today())."' AND line.qty_sent=0"; // show only outstanding, not realized quotes

    if ($fromcust != '')
        $sql .= " AND sorder.debtor_no=".db_escape($fromcust);
//
//    if (isset($stock_item))
//        $sql .= " AND line.stk_code=".db_escape($stock_item);
    if ($area != '')
        $sql .= " AND branch.area=".db_escape($area);

    if ($folk != '')
        $sql .= " AND branch.salesman=".db_escape($folk);

//    if ($location)
//        $sql .= " AND sorder.from_stk_loc = ".db_escape($location);
//
//    if ($filter=='OutstandingOnly')
//        $sql .= " AND line.qty_sent < line.quantity";
//
//    elseif ($filter=='InvoiceTemplates' || $filter=='DeliveryTemplates')
//        $sql .= " AND sorder.type=1";
//
//    //Chaiatanya : New Filter
//    if ($customer_id != ALL_TEXT)
//        $sql .= " AND sorder.debtor_no = ".db_escape($customer_id);

    $sql .= " GROUP BY sorder.order_no,
					sorder.debtor_no,
					sorder.branch_code,
					sorder.customer_ref,
					sorder.ord_date,
					sorder.deliver_to";
    return db_query($sql, "Error");
}
function get_open_balance($debtorno, $to)
{
	if($to)
		$to = date2sql($to);

    $sql = "SELECT SUM(IF(t.type = ".ST_SALESINVOICE." OR t.type = ".ST_BANKPAYMENT.",
    	(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount), 0)) AS charges,
    	SUM(IF(t.type <> ".ST_SALESINVOICE." AND t.type <> ".ST_BANKPAYMENT.",
	    	(t.ov_amount + t.ov_gst + t.gst_wh + t.ov_freight + t.ov_freight_tax + t.ov_discount) * -1, 0)) AS credits,
		SUM(t.alloc) AS Allocated,
		SUM(IF(t.type = ".ST_SALESINVOICE." OR t.type = ".ST_BANKPAYMENT.",
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

function get_transactions($debtorno, $from, $to,$salesgroup)
{
	$from = date2sql($from);
	$to = date2sql($to);

    $sql = " SELECT ".TB_PREF."debtor_trans.*,".TB_PREF."cust_branch.group_no,
		(".TB_PREF."debtor_trans.ov_amount + ".TB_PREF."debtor_trans.ov_gst + ".TB_PREF."debtor_trans.ov_freight + 
		".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount + ".TB_PREF."debtor_trans.gst_wh)
		AS TotalAmount, ".TB_PREF."debtor_trans.alloc AS Allocated,
		((".TB_PREF."debtor_trans.type = ".ST_SALESINVOICE.")
		AND ".TB_PREF."debtor_trans.due_date < '$to') AS OverDue
    	FROM ".TB_PREF."debtor_trans,".TB_PREF."cust_branch
    	WHERE ".TB_PREF."debtor_trans.tran_date >= '$from'
		AND ".TB_PREF."debtor_trans.tran_date <= '$to'
		AND ".TB_PREF."debtor_trans.debtor_no = ".db_escape($debtorno)."
		AND ".TB_PREF."debtor_trans.branch_code = ".TB_PREF."cust_branch.branch_code
		
		AND ".TB_PREF."debtor_trans.type <> ".ST_CUSTDELIVERY." ";

	if ($salesgroup)
		$sql .= " AND  ".TB_PREF."cust_branch.group_no = '$salesgroup'";

    	$sql .= " ORDER BY ".TB_PREF."debtor_trans.tran_date";

    return db_query($sql,"No transactions were returned");
}


/*
function get_transactions($debtorno, $from, $to,$salesgroup)
{
	$from = date2sql($from);
	$to = date2sql($to);

	$sql = " ".TB_PREF."debtor_trans.*,
		(".TB_PREF."debtor_trans.ov_amount + ".TB_PREF."debtor_trans.ov_gst + ".TB_PREF."debtor_trans.ov_freight +
		".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount + ".TB_PREF."debtor_trans.gst_wh)
		AS TotalAmount, ".TB_PREF."debtor_trans.alloc AS Allocated,
		((".TB_PREF."debtor_trans.type = ".ST_SALESINVOICE.")
		AND ".TB_PREF."debtor_trans.due_date < '$to') AS OverDue
    	FROM ".TB_PREF."debtor_trans
    	WHERE ".TB_PREF."debtor_trans.tran_date >= '$from'
		AND ".TB_PREF."debtor_trans.tran_date <= '$to'
		AND ".TB_PREF."debtor_trans.debtor_no = ".db_escape($debtorno)."


		AND ".TB_PREF."debtor_trans.type <> ".ST_CUSTDELIVERY." ";


	$sql .= " ORDER BY ".TB_PREF."debtor_trans.tran_date";

	return db_query($sql,"No transactions were returned");
}*/


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

function print_customer_balances_()
{
    	global $path_to_root, $systypes_array;

    	$from = $_POST['PARAM_0'];
    	$to = $_POST['PARAM_1'];
    	$fromcust = $_POST['PARAM_2'];
	    $area = $_POST['PARAM_3'];
    	$folk = $_POST['PARAM_4'];		
    	$currency = $_POST['PARAM_5'];
    	$no_zeros = $_POST['PARAM_6'];
    	$comments = $_POST['PARAM_7'];
    	$orientation= $_POST['PARAM_8'];
        $destination = $_POST['PARAM_9'];
	    $salesgroup = $_POST['PARAM_10'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = 'L' ;

	if ($fromcust == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($fromcust);
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
		
	if ($no_zeros) $nozeros = _('Yes');
	else $nozeros = _('No');

	$cols = array(0, 20, 50,  170, 290, 325, 360, 395,  500);

	$headers = array(_('Ord.No.'), _('Reference'), _('Customer'), _('Branch'), _('Cust.Ord.Ref.'), _('Order Date'),
		_('Required By'), _('Delivery To'), _('Order Total'));

	//	$headers[7] = _('Balance');

	$aligns = array('left',	'left',	'left',	'left',	'left', 'left', 'left', 'left', 'left' );

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
    				    3 => array('text' => _('Zone'), 		'from' => $sarea, 		'to' => ''),						
    				    4 => array('text' => _('Sales Man'), 		'from' => $salesfolk, 	'to' => ''),
    				    5 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						6 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => ''));

    $rep = new FrontReport(_('Sale Order - Detailed'), "CustomerBalancesDetailed", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

	$grandtotal = array(0,0,0,0);
	$deb_sum_total_grandtotal = array(0,0,0,0);
	$a = 0;
	
//
//	$sql = "SELECT ".TB_PREF."debtors_master.debtor_no AS DebtorNo,
//			".TB_PREF."debtors_master.name AS Name
//		FROM ".TB_PREF."debtors_master
//		INNER JOIN ".TB_PREF."cust_branch
//			ON ".TB_PREF."debtors_master.debtor_no=".TB_PREF."cust_branch.debtor_no
//		INNER JOIN ".TB_PREF."areas
//			ON ".TB_PREF."cust_branch.area = ".TB_PREF."areas.area_code
//		INNER JOIN ".TB_PREF."salesman
//			ON ".TB_PREF."cust_branch.salesman=".TB_PREF."salesman.salesman_code";
//
//		if ($fromcust != ALL_TEXT )
//			{
//				//if ($area != 0 || $folk != 0) continue;
//				$sql .= " WHERE ".TB_PREF."debtors_master.debtor_no=".db_escape($fromcust);
//			}
//
//		elseif ($area != 0)
//			{
//				if ($folk != 0)
//					$sql .= " WHERE ".TB_PREF."salesman.salesman_code=".db_escape($folk)."
//						AND ".TB_PREF."areas.area_code=".db_escape($area);
//				else
//					$sql .= " WHERE ".TB_PREF."areas.area_code=".db_escape($area);
//			}
//		elseif ($folk != 0 )
//			{
//				$sql .= " WHERE ".TB_PREF."salesman.salesman_code=".db_escape($folk);
//			}
//
//	$sql .= " ORDER BY Name";
//	$result = db_query($sql, "The customers could not be retrieved");
	$num_lines = 0;

    $GetRecord = get_sql_for_sales_orders_report(ST_SALESORDER, $from, $to,$fromcust,$area,$folk,$currency);
	while ($myrow = db_fetch($GetRecord))
	{
        if($myrow['reference'] == 'auto')
            continue;
        if ($no_zeros && $myrow['OrderValue'] == 0)
            continue;

        $rep->TextCol(0, 1, $myrow['order_no']);
        $rep->TextCol(1, 2,	$myrow['reference']);
        $rep->TextCol(2, 3,	$myrow['name']);
        $rep->TextCol(3, 4,	$myrow['br_name']);
        $rep->TextCol(4, 5,	$myrow['customer_ref']);
        $rep->TextCol(5, 6,	$myrow['ord_date']);
        $rep->TextCol(6, 7,	$myrow['delivery_date']);
        $rep->TextCol(7, 8,	$myrow['deliver_to']);
        $rep->TextCol(8, 9,	$myrow['OrderValue']);
        $rep->NewLine();

	}
    	$rep->End();
}

?>