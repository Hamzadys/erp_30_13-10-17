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

function getTransactions($category, $location, $fromcust, $from, $to)
{
	/*$from = date2sql($from);
	$to = date2sql($to);*/
	/*$sql = " SELECT
			SUM(-".TB_PREF."stock_moves.qty*".TB_PREF."stock_moves.price*(1-".TB_PREF."stock_moves.discount_percent)) AS amt
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
		$sql .= " GROUP BY ".TB_PREF."debtors_master.name ORDER BY ".TB_PREF."debtors_master.name";*/

    $sql = "SELECT SUM((line.unit_price + line.unit_tax ) * line.quantity  )+ trans.ov_freight   as amnt

		FROM ".TB_PREF."stock_master item,
			".TB_PREF."stock_category category,
			".TB_PREF."debtor_trans trans,
			".TB_PREF."debtor_trans_details line
		WHERE line.stock_id = item.stock_id
		AND item.category_id=category.category_id
		AND line.debtor_trans_type=trans.type
		AND line.debtor_trans_no=trans.trans_no
		AND trans.tran_date>='$from'
		AND trans.tran_date<='$to'
		AND line.quantity <>0
		AND line.debtor_trans_type = ".ST_SALESINVOICE;
    if ($category != 0)
        $sql .= " AND item.category_id = ".db_escape($category);
    $sql .= "
		ORDER BY item.category_id, item.stock_id, line.unit_price";

      $db =  db_query($sql,"No transactions were returned");
       $ft = db_fetch($db);
    return $ft[0];

}



//----------------------------------------------------------------------------------------------------

function get_total_num_fiscals_year()
{
    $sql ="SELECT COUNT(*) FROM `".TB_PREF."fiscal_year` WHERE `closed`=0";
    $result =  db_query($sql,'could not get Fiscal year');
    $myrow = db_fetch($result);
    return $myrow[0];
}


function get_fiscals_year()
{
    $sql ="SELECT  `end` FROM 0_fiscal_year";
    return  db_query($sql,'could not get Fiscal year');
    //$ft = db_fetch($db);
   // return $ft[0];
}
function print_inventory_sales()
{
    global $path_to_root;

    $category = $_POST['PARAM_0'];
    $comments = $_POST['PARAM_1'];
    $orientation = $_POST['PARAM_2'];
    $destination = $_POST['PARAM_3'];

/*	$to = $_POST['PARAM_1'];

    $location = $_POST['PARAM_3'];
    $fromcust = $_POST['PARAM_4'];

	*/

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
    $dec = user_price_dec();

	if ($category == ALL_NUMERIC)
		$category = 0;
	if ($category == 0)
		$cat = _('All');
	else
		$cat = get_category_name($category);

	/*if ($location == '')
		$loc = _('All');
	else
		$loc = get_location_name($location);*/

	/*if ($fromcust == '')
		$fromc = _('All');
	else
		$fromc = get_customer_name($fromcust);*/



   // $cols    = array();
   /* $headers = array();

    $headers[0] = _("Month");
    $ft = get_fiscals_year();
    while($mayrow = db_fetch($ft))
    {
        $var = $mayrow['end'];

        $fiscal_year = $var;
        $headers[] = _(".$fiscal_year.");
    }


    $headers[] = _("Grand Total");


    $aligns  = array();*/

    $cols    = array();
    $headers = array();
    $aligns  = array();

     $ft = get_total_num_fiscals_year();
     $myrow = get_current_fiscalyear();

    $cols[0]    = 0;
    $headers[0] = _("Month");
    $aligns[0]  = 'left';
    $year2 = date("Y", strtotime($myrow["end"]));

        for($i=0; $i <= 5; $i++)
        {
            $year = date("Y-m-d", strtotime($myrow["end"]));
            $lastyear = strtotime("-".$i." year", strtotime($year));
            $var = date("Y", $lastyear);
            $stock_id[$i] = $var;

        }

    $cols    = array(0, 50,150,200, 270, 340,  420,510);
    $headers = array(_('Month'), _("$year2"), _("$stock_id[1]"),  _("$stock_id[2]"),  _("$stock_id[3]"), _("$stock_id[4]"),  _('Grand Total'));
    $aligns = array('left',	'center', 'right','right','right', 'right',  'right');

	if ($fromcust != '')
		$headers[2] = '';



    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
    				    2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
    				    3 => array('text' => _('Location'), 'from' => $loc, 'to' => ''),
    				    4 => array('text' => _('Customer'), 'from' => $fromc, 'to' => ''));

    $rep = new FrontReport(_('Month Wise Sales Comparison'), "InventorySalesReport", user_pagesize(), 9, $orientation);
   	if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

//	$res = getTransactions($category, $location, $fromcust, $from, $to);
	$total = $grandtotal = 0.0;
	$total1 = $grandtotal1 = 0.0;
	$total2 = $grandtotal2 = 0.0; 
	$total_qty = 0.0;
	$catt = '';

    $month = array();
    $month[7] = 'July';
    $month[8] = 'August';
    $month[9] = 'September';
    $month[10] = 'October';
    $month[11] = 'November';
    $month[12] = 'December';
    $month[1] = 'January';
    $month[2] = 'Febuary';
    $month[3] = 'March';
    $month[4] = 'April';
    $month[5] = 'May';
    $month[6] = 'June';


    $grandtotal_qty = 0;
    $grand_grand_total = 0;
    $arrlength = count($month);
    $new = 0;

    $end_total = array(0, 0, 0, 0);

    for($x = 1; $x <= $arrlength; $x++)
    {
        $total = 0 ;
        $rep->fontSize += 2;
        $rep->TextCol(0, 1,$month[$x], $dec);
        $rep->fontSize -= 2;

        for($i=0; $i <= 4; $i++)
        {
            $fiscal_year_end = date("Y-m-d", strtotime($myrow["end"]));
            $fiscal_year_end = strtotime("-".$i." year", strtotime($fiscal_year_end));
            $fiscal_year_end = date("Y-".$x."-01", $fiscal_year_end);

            $fiscal_year_start = date("Y-m-d", strtotime($myrow["end"]));
            $fiscal_year_start = strtotime("-".$i." year", strtotime($fiscal_year_start));
            $fiscal_year_start = date("Y-".$x."-31", $fiscal_year_start);


          if($x > 6)
            {

                $fiscal_year_end = strtotime("-1 year", strtotime($fiscal_year_end));
                $fiscal_year_end = date("Y-".$x."-01", $fiscal_year_end);

                $fiscal_year_start = strtotime("-1 year", strtotime($fiscal_year_start));
                $fiscal_year_start = date("Y-".$x."-31", $fiscal_year_start );

            }

            $from123 = $fiscal_year_end ;
            $to123   = $fiscal_year_start ;


            $res = getTransactions($category, $location, $fromcust, $from123, $to123);

            if($res == '' || $res == 0)
                $res =0;

            $rep->Line($rep->row - 2);
                $rep->fontSize -= 2;
                $rep->TextCol(1+$i, 2+$i, round2($res ), $dec);
                $rep->fontSize += 2;

            $total += $res;
            $end_total[$i] += $res;


            //
        }

        $new += $res;

        $rep->fontSize -= 2;
        $rep->TextCol(6, 7, round2($total), $dec);
        $rep->fontSize += 2;

       $grand_grand_total += $total ;

        $rep->NewLine(2);
        echo "<br>";



    }

	$rep->Line($rep->row - 2);
	$rep->NewLine();
//	$rep->NewLine(2, 1);

    $rep->TextCol(0, 1, 'Grand Total' , $dec);
    for($i=0; $i <= 4; $i++)
    {
         if($end_total[$i] == '' || $end_total[$i] == 0)
           $end_total[$i] =0;
        $rep->fontSize -= 2;
        $rep->TextCol(1+$i, 2+$i, round2($end_total[$i]) , $dec);
        $rep->fontSize += 2;
    }

    $rep->fontSize -= 2;
    $rep->TextCol(6, 7, round2($grand_grand_total), $dec);
    $rep->fontSize += 2;


        $rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();
}

?>