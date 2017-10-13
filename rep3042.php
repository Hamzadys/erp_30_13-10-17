<?php
$page_security = 'SA_SALESANALYTIC';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Inventory Sales Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");

//----------------------------------------------------------------------------------------------------

print_inventory_sales();

function getTransactions($category, $location, $fromcust, $from, $to,$dist_cust,$trans_no)
{
	$from = date2sql($from);
	$to = date2sql($to);
	$sql = "SELECT ".TB_PREF."stock_master.category_id,
			".TB_PREF."stock_category.description AS cat_description,
			".TB_PREF."stock_master.stock_id,
			".TB_PREF."stock_master.description, ".TB_PREF."stock_master.inactive,
			".TB_PREF."stock_moves.loc_code,
			".TB_PREF."stock_moves.trans_no,
			".TB_PREF."stock_moves.reference,
			".TB_PREF."stock_moves.price,
			".TB_PREF."stock_moves.type,
			".TB_PREF."debtor_trans.debtor_no,
			".TB_PREF."debtor_trans.total_discount,
			".TB_PREF."debtor_trans.payment_terms,
			".TB_PREF."debtors_master.name AS debtor_name,
			".TB_PREF."stock_moves.tran_date,
			(-".TB_PREF."stock_moves.qty) AS qty,
			(-(".TB_PREF."stock_moves.qty*".TB_PREF."stock_moves.price)-(".TB_PREF."stock_moves.discount_percent)) AS amt,
			(-IF(".TB_PREF."stock_moves.standard_cost <> 0, ".TB_PREF."stock_moves.qty * ".TB_PREF."stock_moves.standard_cost, ".TB_PREF."stock_moves.qty *(".TB_PREF."stock_master.material_cost + ".TB_PREF."stock_master.labour_cost + ".TB_PREF."stock_master.overhead_cost))) AS cost
			,".TB_PREF."stock_moves.trans_no,".TB_PREF."stock_moves.type
		FROM ".TB_PREF."stock_master,
			".TB_PREF."stock_category,
			".TB_PREF."debtor_trans,
			".TB_PREF."debtors_master,
			".TB_PREF."stock_moves
		WHERE ".TB_PREF."stock_master.stock_id=".TB_PREF."stock_moves.stock_id
		AND ".TB_PREF."stock_master.category_id=".TB_PREF."stock_category.category_id
		AND ".TB_PREF."debtor_trans.debtor_no=".TB_PREF."debtors_master.debtor_no
		AND ".TB_PREF."stock_moves.type=".TB_PREF."debtor_trans.type
		AND ".TB_PREF."stock_moves.trans_no=".TB_PREF."debtor_trans.trans_no
		AND ".TB_PREF."stock_moves.trans_no = ".db_escape($trans_no)."
		AND ".TB_PREF."stock_moves.tran_date>='$from'
		AND ".TB_PREF."stock_moves.tran_date<='$to'
		AND ".TB_PREF."stock_moves.qty != 0
		AND ".TB_PREF."debtor_trans.debtor_no = ".db_escape($dist_cust)."
		AND (".TB_PREF."debtor_trans.type=".ST_CUSTDELIVERY." OR ".TB_PREF."stock_moves.type=".ST_CUSTCREDIT.")
		AND (".TB_PREF."stock_master.mb_flag='B' OR ".TB_PREF."stock_master.mb_flag='M')";
	if ($category != 0)
		$sql .= " AND ".TB_PREF."stock_master.category_id = ".db_escape($category);
	if ($location != '')
		$sql .= " AND ".TB_PREF."stock_moves.loc_code = ".db_escape($location);
	if ($fromcust != '')
		$sql .= " AND ".TB_PREF."debtors_master.debtor_no = ".db_escape($fromcust);
//    $sql .= " GROUP BY ".TB_PREF."debtor_trans.payment_terms";
	$sql .= " ORDER BY ".TB_PREF."debtor_trans.payment_terms,".TB_PREF."debtor_trans.trans_no";


	return db_query($sql,"No transactions were returned");
//GROUP BY ".TB_PREF."stock_master.stock_id, ".TB_PREF."debtors_master.name
//		$sql .= " ORDER BY ".TB_PREF."stock_master.category_id,
//			".TB_PREF."stock_master.stock_id, ".TB_PREF."debtors_master.name";

}
//////
function get_distinct_customer($category, $location, $fromcust, $from, $to)
{
	$from = date2sql($from);
	$to = date2sql($to);
	$sql = " SELECT
			".TB_PREF."debtor_trans.debtor_no , ".TB_PREF."stock_moves.trans_no
			
		FROM ".TB_PREF."stock_master,
			".TB_PREF."stock_category,
			".TB_PREF."debtor_trans,
			".TB_PREF."debtors_master,
			".TB_PREF."stock_moves
		WHERE ".TB_PREF."stock_master.stock_id=".TB_PREF."stock_moves.stock_id
		AND ".TB_PREF."stock_master.category_id=".TB_PREF."stock_category.category_id
		AND ".TB_PREF."debtor_trans.debtor_no=".TB_PREF."debtors_master.debtor_no
		AND ".TB_PREF."stock_moves.type=".TB_PREF."debtor_trans.type
		AND ".TB_PREF."stock_moves.trans_no=".TB_PREF."debtor_trans.trans_no
		AND ".TB_PREF."stock_moves.tran_date>='$from'
		AND ".TB_PREF."stock_moves.tran_date<='$to'
		AND (".TB_PREF."debtor_trans.type=".ST_CUSTDELIVERY." OR ".TB_PREF."stock_moves.type=".ST_CUSTCREDIT.")
		AND (".TB_PREF."stock_master.mb_flag='B' OR ".TB_PREF."stock_master.mb_flag='M')";
	if ($category != 0)
		$sql .= " AND ".TB_PREF."stock_master.category_id = ".db_escape($category);
	if ($location != '')
		$sql .= " AND ".TB_PREF."stock_moves.loc_code = ".db_escape($location);
	if ($fromcust != '')
		$sql .= " AND ".TB_PREF."debtors_master.debtor_no = ".db_escape($fromcust);
	$sql .= " GROUP BY ".TB_PREF."stock_moves.trans_no ";
	$sql .= " ORDER BY ".TB_PREF."debtor_trans.payment_terms,".TB_PREF."debtor_trans.trans_no";


	return db_query($sql,"No transactions were returned");
}
//---------------------------------------------------------------------------------------------------------------------

function get_dimension_from_location($location)
{
	$sql = "SELECT dim1 FROM ".TB_PREF."locations WHERE loc_code = ".db_escape($location);
	$result = db_query($sql, "error");
	$row = db_fetch_row($result);
	return $row[0];
}
function get_sql_for_journal_inquiry_new($filter, $from, $to, $ref='', $memo='', $alsoclosed=false)
{

	$sql = "SELECT IF(ISNULL(a.gl_seq),0,a.gl_seq) as gl_seq,
		gl.tran_date,
		gl.type,
		gl.type_no,
		refs.reference,
		SUM(IF(gl.amount>0, gl.amount,0)) as amount,
		com.memo_,gl.memo_ As Comm,
		IF(ISNULL(u.user_id),'',u.user_id) as user_id
		FROM ".TB_PREF."gl_trans as gl
		 LEFT JOIN ".TB_PREF."audit_trail as a ON
			(gl.type=a.type AND gl.type_no=a.trans_no)
		 LEFT JOIN ".TB_PREF."comments as com ON
			(gl.type=com.type AND gl.type_no=com.id)
		 LEFT JOIN ".TB_PREF."refs as refs ON
			(gl.type=refs.type AND gl.type_no=refs.id)
		 LEFT JOIN ".TB_PREF."users as u ON
			a.user=u.id
		WHERE gl.tran_date >= '" . date2sql($from) . "'
		AND gl.tran_date <= '" . date2sql($to) . "'
		AND gl.amount!=0
AND gl.type=$filter";
$sql .= " GROUP BY gl.tran_date, a.gl_seq, gl.type, gl.type_no";
  return db_query($sql,"No transactions were returned");
}
function get_banks_closing_balance($from)
{

	$sql = "SELECT SUM(amount) as balance, ".TB_PREF."bank_accounts.* 
                    FROM ".TB_PREF."gl_trans, ".TB_PREF."bank_accounts 
                    WHERE ".TB_PREF."gl_trans.account=".TB_PREF."bank_accounts.account_code 
                    AND tran_date >= '" . date2sql($from) . "'
		    AND tran_date <= '" . date2sql($to) . "'
                    AND `account_type`=3
		    GROUP BY ".TB_PREF."bank_accounts.account_code
		    ORDER BY balance DESC LIMIT 10";
  return db_query($sql,"No transactions were returned");
}
//////
function get_gl_trans_from_to_new($from_date, $to_date,$dim_id)
{
    $from = date2sql($from_date);
    $to = date2sql($to_date);

    $sql = "SELECT SUM(".TB_PREF."gl_trans.amount), ".TB_PREF."gl_trans.dimension_id FROM ".TB_PREF."gl_trans,".TB_PREF."chart_master WHERE
".TB_PREF."gl_trans.account=".TB_PREF."chart_master.account_code
AND ".TB_PREF."chart_master.account_type=12 ";
    if ($from_date != "")
        $sql .= " AND ".TB_PREF."gl_trans.tran_date >= '$from'";
    if ($to_date != "")
        $sql .= " AND ".TB_PREF."gl_trans.tran_date <= '$to'";
	if ($dim_id != "")
		$sql .= " AND ".TB_PREF."gl_trans.dimension_id = '$dim_id'";
    $result = db_query($sql, "Transactions for account  could not be calculated");

    $row = db_fetch_row($result);
    return (float)$row[0];
}
function get_sales_as_per_payment_terms($category, $location, $fromcust, $from, $to, $payment_terms)
{
	$from = date2sql($from);
	$to = date2sql($to);
	$sql = "SELECT (-(".TB_PREF."stock_moves.qty*".TB_PREF."stock_moves.price)-(".TB_PREF."stock_moves.discount_percent)) AS amt,
			(-IF(".TB_PREF."stock_moves.standard_cost <> 0, ".TB_PREF."stock_moves.qty * ".TB_PREF."stock_moves.standard_cost, ".TB_PREF."stock_moves.qty *(".TB_PREF."stock_master.material_cost + ".TB_PREF."stock_master.labour_cost + ".TB_PREF."stock_master.overhead_cost))) AS cost
			,".TB_PREF."stock_moves.trans_no,".TB_PREF."stock_moves.type
		FROM ".TB_PREF."stock_master,
			".TB_PREF."stock_category,
			".TB_PREF."debtor_trans,
			".TB_PREF."debtors_master,
			".TB_PREF."stock_moves
		WHERE ".TB_PREF."stock_master.stock_id=".TB_PREF."stock_moves.stock_id
		AND ".TB_PREF."stock_master.category_id=".TB_PREF."stock_category.category_id
		AND ".TB_PREF."debtor_trans.debtor_no=".TB_PREF."debtors_master.debtor_no
		AND ".TB_PREF."stock_moves.type=".TB_PREF."debtor_trans.type
		AND ".TB_PREF."stock_moves.trans_no=".TB_PREF."debtor_trans.trans_no
		AND ".TB_PREF."stock_moves.tran_date>='$from'
		AND ".TB_PREF."stock_moves.tran_date<='$to'
		AND (".TB_PREF."debtor_trans.type=".ST_CUSTDELIVERY." OR ".TB_PREF."stock_moves.type=".ST_CUSTCREDIT.")
		AND (".TB_PREF."stock_master.mb_flag='B' OR ".TB_PREF."stock_master.mb_flag='M')";

		$sql .= " AND ".TB_PREF."debtor_trans.payment_terms = ".db_escape($payment_terms);


		if ($category != 0)
			$sql .= " AND ".TB_PREF."stock_master.category_id = ".db_escape($category);
		if ($location != '')
			$sql .= " AND ".TB_PREF."stock_moves.loc_code = ".db_escape($location);
		if ($fromcust != '')
			$sql .= " AND ".TB_PREF."debtors_master.debtor_no = ".db_escape($fromcust);
		$sql .= " ORDER BY ".TB_PREF."stock_moves.trans_no,
			".TB_PREF."stock_master.stock_id, ".TB_PREF."debtors_master.name";
    return db_query($sql,"No transactions were returned");
//GROUP BY ".TB_PREF."stock_master.stock_id, ".TB_PREF."debtors_master.name 
//		$sql .= " ORDER BY ".TB_PREF."stock_master.category_id,
//			".TB_PREF."stock_master.stock_id, ".TB_PREF."debtors_master.name";

}


function get_audit_stamp ($transtype, $trans)
{
	$sql = "SELECT  stamp  FROM ".TB_PREF."audit_trail"
		." WHERE type=".db_escape($transtype)." AND trans_no="
		.db_escape($trans);

	$query= db_query($sql, "Cannot get all audit info for transaction");
	$fetch=db_fetch_row($query);
	return $fetch[0];
}

function get_discount_amount($transtype, $trans)
{
	$sql = "SELECT unit_price FROM ".TB_PREF."debtor_trans_details"."
	WHERE debtor_trans_type=".db_escape($transtype)." AND debtor_trans_no="
		.db_escape($trans)." AND stock_id = 'DC'";

	$query= db_query($sql, "Cannot get all audit info for transaction");
	$fetch=db_fetch_row($query);
	return $fetch[0];
}
function get_payment_terms_cod($selected_id)
{
    $sql = "SELECT *,(t.days_before_due=0) AND (t.day_in_following_month=0) 
as cash_sale
	 FROM ".TB_PREF."payment_terms t WHERE terms_indicator= '$selected_id'" ;

    $result = db_query($sql,"could not get payment term");

    return db_fetch($result);
}


function get_payment_total($payment_id, $from, $to)
{
    $from = date2sql($from);
    $to = date2sql($to);
    $sql = " ".TB_PREF."stock_master.category_id,
			".TB_PREF."stock_category.description AS cat_description,
			".TB_PREF."stock_master.stock_id,
			".TB_PREF."stock_master.description, ".TB_PREF."stock_master.inactive,
			".TB_PREF."stock_moves.loc_code,
			".TB_PREF."stock_moves.trans_no,
			".TB_PREF."stock_moves.reference,
			".TB_PREF."stock_moves.price,
			".TB_PREF."stock_moves.type,
			".TB_PREF."debtor_trans.debtor_no,
			".TB_PREF."debtor_trans.payment_terms,
			".TB_PREF."debtors_master.name AS debtor_name,
			".TB_PREF."stock_moves.tran_date,
			(-".TB_PREF."stock_moves.qty) AS qty,
			(-(".TB_PREF."stock_moves.qty*".TB_PREF."stock_moves.price)-(".TB_PREF."stock_moves.discount_percent)) AS amt,
			(-IF(".TB_PREF."stock_moves.standard_cost <> 0, ".TB_PREF."stock_moves.qty * ".TB_PREF."stock_moves.standard_cost, ".TB_PREF."stock_moves.qty *(".TB_PREF."stock_master.material_cost + ".TB_PREF."stock_master.labour_cost + ".TB_PREF."stock_master.overhead_cost))) AS cost
			,".TB_PREF."stock_moves.trans_no,".TB_PREF."stock_moves.type
		FROM ".TB_PREF."stock_master,
			".TB_PREF."stock_category,
			".TB_PREF."debtor_trans,
			".TB_PREF."debtors_master,
			".TB_PREF."stock_moves
		WHERE ".TB_PREF."stock_master.stock_id=".TB_PREF."stock_moves.stock_id
		AND ".TB_PREF."stock_master.category_id=".TB_PREF."stock_category.category_id
		AND ".TB_PREF."debtor_trans.debtor_no=".TB_PREF."debtors_master.debtor_no
		AND ".TB_PREF."stock_moves.type=".TB_PREF."debtor_trans.type
		AND ".TB_PREF."stock_moves.trans_no=".TB_PREF."debtor_trans.trans_no
		AND ".TB_PREF."stock_moves.tran_date>='$from'
		AND ".TB_PREF."stock_moves.tran_date<='$to'
		AND (".TB_PREF."debtor_trans.type=".ST_CUSTDELIVERY." OR ".TB_PREF."stock_moves.type=".ST_CUSTCREDIT.")
		AND (".TB_PREF."stock_master.mb_flag='B' OR ".TB_PREF."stock_master.mb_flag='M')
		AND ".TB_PREF."debtor_trans.payment_terms='$payment_id'";


   $sql .= " GROUP BY ".TB_PREF."debtor_trans.payment_terms";

    $sql .= " ORDER BY ".TB_PREF."debtor_trans.payment_terms";


    return db_query($sql,"No transactions were returned");
//GROUP BY ".TB_PREF."stock_master.stock_id, ".TB_PREF."debtors_master.name
//		$sql .= " ORDER BY ".TB_PREF."stock_master.category_id,
//			".TB_PREF."stock_master.stock_id, ".TB_PREF."debtors_master.name";

}

//----------------------------------------------------------------------------------------------------

function print_inventory_sales()
{
    global $path_to_root;

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
    $category = $_POST['PARAM_2'];
    $location = $_POST['PARAM_3'];
    $fromcust = $_POST['PARAM_4'];
	$comments = $_POST['PARAM_5'];
	$orientation = $_POST['PARAM_6'];
	$destination = $_POST['PARAM_7'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
   // $dec = user_price_dec();
    $dec = 0;

	if ($category == ALL_NUMERIC)
		$category = 0;
	if ($category == 0)
		$cat = _('All');
	else
		$cat = get_category_name($category);

	if ($location == '')
		$loc = _('All');
	else
		$loc = get_location_name($location);

	if ($fromcust == '')
		$fromc = _('All');
	else
		$fromc = get_customer_name($fromcust);

	$cols = array(0, 25, 60, 280, 320, 350, 390, 440, 480, 515, 540);

	$headers = array(_('S.NO'), _(M.O.P), _('Item Description'), _('Price'), _('Qty'), _('Discount'), _('Remaining'), _('Time'), _('Total'), _(''));
	if ($fromcust != '')
		$headers[2] = '';

	$aligns = array('left',	'left',	'left','right', 'right', 'right', 'right', 'right', 'right', 'right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
    				    2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
    				    3 => array('text' => _('Location'), 'from' => $loc, 'to' => ''),
    				    4 => array('text' => _('Customer'), 'from' => $fromc, 'to' => ''));

    $rep = new FrontReport(_('Daily Sales Report'), "DailySalesReport", user_pagesize(), 9, $orientation);
   	if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();


	$total = $grandtotal = 0.0;
	$total1 = $grandtotal1 = 0.0;
	$total2 = $grandtotal2 = 0.0;
	$qtytotal = 0.0;
	$disctotal = 0.0;
	$discount_amount = $pay_total = 0.0;
	$catt = $payment = '';
	$a=1;
    $total_cod=0;

	$Total_neg_amount = 0;
	$PayTrmOthrThnCash = 0;
	$TotalPayTrmOthrThnCash = 0;

	//$res = getTransactions($category, $location, $fromcust, $from, $to);
	$distinct_cust = get_distinct_customer($category, $location, $fromcust, $from, $to);
	while($dist_cust = db_fetch($distinct_cust)) {


		$total_invoice_price = 0;
		$total_invoice_qty = 0;
		$invoice_total = 0;
		$discount_amount = 0.0; //dz 28.2.17

		$res = getTransactions($category, $location, $fromcust, $from, $to, $dist_cust['debtor_no'], $dist_cust['trans_no']);

		while ($trans = db_fetch($res)) {

			$curr = get_customer_currency($trans['debtor_no']);
			$rate = get_exchange_rate_from_home_currency($curr, sql2date($trans['tran_date']));
			$payment_terms = get_payment_terms($trans['payment_terms']);

			$discount_amount = $trans['total_discount'];

			$trans['amt'] *= $rate;
			$cb = $trans['amt'] - $trans['cost'];
			$rep->NewLine();
			//$rep->fontSize -= 2;

			$rep->TextCol(0, 1, $a);
			$rep->TextCol(1, 2, $payment_terms['terms']);


			$rep->TextCol(2, 3, $trans['stock_id'] . " - " . $trans['description'] . ($trans['inactive'] == 1 ? " (" . _("Inactive") . ")" : "" . " - " . $trans['reference']), -1);

			$rep->AmountCol(3, 4, $trans['price'], $dec);
			$rep->AmountCol(4, 5, $trans['qty'], get_qty_dec($trans['stock_id']));

			$trans_no = $trans['trans_no'];
			$rep->AmountCol(6, 7, get_qoh_on_date($trans['stock_id'], $location, $to), $dec);


			$time = get_audit_stamp($trans['type'], $trans['trans_no']);
			$time1 = date("h:i:s", strtotime($time));
			$rep->TextCol(7, 8, $time1);

			$net_amount = ($trans['amt'] );
			$net_amount_neg = ($trans['amt'] - $discount_amount);

			$rep->AmountCol(8, 9, $net_amount, $dec);

			if ($payment_terms['terms'] != 'Cash')
				$PayTrmOthrThnCash = $net_amount;
//		$rep->AmountCol(9, 10, $PayTrmOthrThnCash , $dec);
			$TotalPayTrmOthrThnCash += $PayTrmOthrThnCash;


			if ($net_amount_neg < 0)
				$Total_neg_amount += $net_amount_neg;

			$total += $trans['amt'];
			$total1 += $trans['cost'];
			$total2 += $cb;

			$disctotal += $discount_amount;
			$qtytotal += $trans['qty'];
			$grandtotal += $net_amount;
			$grandtotal1 += $trans['cost'];
			$grandtotal2 += $cb;
			$rep->Line($rep->row - 2);

			$a++;


			$total_invoice_price +=  $trans['price'];
			$total_invoice_qty +=  $trans['qty'];
			$invoice_total +=  $net_amount;
			$type = $trans['payment_terms'];

			$type_ref = $trans['reference'];
			$qty = $trans['qty'];
			$p += $trans['price'] ;
			if ($qty < 0) {
				$disct += $trans['total_discount'];
			}
			$disct = $disct;
			$aa = $trans['total_discount'];
			$pp= $p ;
		}



		$rep->NewLine();
		$rep->Font('bold');
		$rep->TextCol(0, 1, _("Total") );
		$rep->AmountCol(3, 4, $invoice_total, $dec);
		//	$rep->AmountCol(4, 5, $total_invoice_qty, $dec);


		if ($type_ref == 'Return') //for line values
		{
			$tot = $invoice_total  + $discount_amount ; 
			$discount_amount = -$discount_amount ; 
		}
		else
		{	$tot = $invoice_total  - $discount_amount ;
			$discount_amount = $discount_amount ; 
		}

		$rep->AmountCol(5, 6, $discount_amount, $dec);
		$rep->AmountCol(8, 9, $tot , $dec);
		$rep->Font();
		$rep->Line($rep->row  - 4);
		$rep->NewLine();

		$grand_disctotal += $discount_amount;
		$grandtotal_new += $tot;
		$grandtotal_gross += $invoice_total;

		if ($type_ref == 'Return')
		{
			$amnt +=  $invoice_total  - $discount_amount ;
			$total_return_amount = -1* $amnt  ;
		}

		//COD
		if ($type == 2 && $type_ref != 'Return')
		{
			$pay_total += $tot;
		}
		//Cash
		elseif ($type == 1 && $type_ref != 'Return')
		{
			$pay_cash  += $tot;
		}
		//CC
		elseif ($type == 5 && $type_ref != 'Return')
		{
			$pay_cc  += $tot;
		}
		//Credit
		elseif ($type == 3 && $type_ref != 'Return')
		{
			$pay_credit  += $tot;
		}
	}

	$rep->NewLine();
	$rep->Line($rep->row - 2);
	//$rep->fontSize += 2;

	$net_amount_neg =0;
    $rep->Font('bold');
    $rep->NewLine();
	$rep->TextCol(0, 6, _(' Grand Total'));
	$rep->AmountCol(5, 6, $grand_disctotal, $dec);
	$rep->AmountCol(4, 5, $qtytotal, $dec);
	$rep->AmountCol(8, 9, $grandtotal_new  , $dec);
	$rep->Line($rep->row - 2);
	$rep->NewLine(3);
	$rep->Line($rep->row + 10);
	$rep->TextCol(0, 6, _('Gross Sale'));
	$rep->AmountCol(8, 9, ($grandtotal_gross) , $dec);
	$rep->Line($rep->row - 2);


	$rep->NewLine();
	$rep->TextCol(0, 6, _('Discount'));
	$rep->AmountCol(8, 9, -$grand_disctotal, $dec);
	$rep->Line($rep->row - 2);

	$rep->NewLine();
	$rep->TextCol(0, 6, _('Net Sale'));
	$net_sale = $grandtotal_gross - $grand_disctotal;
	$rep->AmountCol(8, 9, ($net_sale) , $dec);
	$rep->Line($rep->row - 2);

   $rep->NewLine(3);
	$rep->Line($rep->row - 2);
	$rep->Line($rep->row + 10);
    $rep->TextCol(0, 6, _('Total COD/Online sales'));
    $rep->AmountCol(8, 9, $pay_total, $dec);
	$rep->Line($rep->row - 2);

    $rep->NewLine();
    $rep->TextCol(0, 6, _('Total Cash sales'));
	$rep->AmountCol(8, 9, $pay_cash, $dec);
	$rep->Line($rep->row - 2);
/*	$rep->NewLine();
	$rep->TextCol(0, 6, _('Total Cash With Sales Return'));

	//$rep->AmountCol(8, 9, (($grandtotal - $Total_neg_amount) - $TotalPayTrmOthrThnCash), $dec);
	$rep->AmountCol(8, 9, $total_return_amount , $dec);
	$rep->Line($rep->row - 2);
*/ 
$rep->NewLine();
    $rep->TextCol(0, 6, _('Total CC Sale'));
    $rep->AmountCol(8, 9, $pay_cc, $dec);
	$rep->Line($rep->row - 2);

    $rep->NewLine();
    $rep->TextCol(0, 6, _('Total Credit Sale'));
    $rep->AmountCol(8, 9, $pay_credit, $dec);
	$rep->Line($rep->row - 2);


	$dim_id=get_dimension_from_location($location);
    $per_balance = get_gl_trans_from_to_new($from, $to,$dim_id);

	$rep->NewLine();
	$rep->TextCol(0, 6, _('Total of Return Items'));
	$rep->AmountCol(8, 9, (-$total_return_amount) , $dec);
	$rep->Line($rep->row - 2);

    $rep->NewLine();
    $rep->TextCol(0, 6, _('Total Expenses'));
    $rep->AmountCol(8, 9, $per_balance, $dec);
    $rep->Line($rep->row - 2);
    $rep->NewLine();
    $rep->TextCol(0, 6, _('Today Fund Transfer'));
    $rep->Font('');
    $result = get_sql_for_journal_inquiry_new(4, $from,
	$to);
	$rep->Line($rep->row - 2);
    $rep->NewLine();
    while ($trans2=db_fetch($result))
	{
$rep->TextCol(0, 5, $trans2['Comm']);
//$rep->TextCol(4, 5, $trans2['reference']);
$rep->TextCol(8, 9, $trans2['amount']);
$rep->NewLine();
}
$rep->Font('bold');
$rep->TextCol(0, 6, _('Banks Closing Balance'));
$rep->Font('');
$result2 = get_banks_closing_balance($from);
$rep->NewLine();
while ($trans3=db_fetch($result2))
	{
$rep->TextCol(0, 4, $trans3['bank_name']);
$rep->TextCol(7, 9, round2($trans3['balance']));
$rep->NewLine();
}
	$rep->Line($rep->row - 2);

/*
    $rep->Line($rep->row - 2);
	$rep->NewLine(1);
	$rep->TextCol(5, 6, _("Today's Pretty Cash"));



    $rep->Line($rep->row - 2);
	$rep->NewLine(1);
	$rep->TextCol(5, 6, _("Expenses"));




	$rep->Line($rep->row - 2);
	$rep->NewLine(1);
	$rep->TextCol(5, 6, _("Membership Payment"),+3);




	$rep->Line($rep->row - 2);
	$rep->NewLine(1);
	$rep->TextCol(5, 6, _("Card Payments"));





	$rep->Line($rep->row - 2);
	$rep->NewLine(1);
	$rep->TextCol(5, 6, _("Credit"));




	$rep->Line($rep->row - 2);
	$rep->NewLine(1);
	$rep->TextCol(5, 6, _("Cod/Online Sales"));





	$rep->Line($rep->row - 2);
	$rep->NewLine(1);
	$rep->TextCol(5, 6, _("Credit Recieved From Client"),+25);





	$rep->Line($rep->row - 2);
	$rep->NewLine(1);
	$rep->TextCol(5, 6, _("Checque Cash"));



	$rep->Line($rep->row - 2);
	$rep->NewLine(1);
	$rep->TextCol(5, 6, _("Next Day Pretty Cash"),+5);




	$rep->Line($rep->row - 2);
	$rep->NewLine(1);
	$rep->TextCol(5, 6, _("Cash In Hand"));

*/

	$rep->NewLine();

    $rep->End();
}

?>