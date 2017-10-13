<?php
$page_security = 'SA_GLREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Chart of GL Accounts
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------



print_Chart_of_Accounts();

//----------------------------------------------------------------------------------------------------
function convert_number_to_words($number) {

    $hyphen      = '-';
    $conjunction = ' and ';
    $separator   = ', ';
    $negative    = 'negative ';
    $decimal     = ' point ';
    $dictionary  = array(
        0                   => 'Zero',
        1                   => 'One',
        2                   => 'Two',
        3                   => 'Three',
        4                   => 'Four',
        5                   => 'Five',
        6                   => 'Six',
        7                   => 'Seven',
        8                   => 'Eight',
        9                   => 'Nine',
        10                  => 'Ten',
        11                  => 'Eleven',
        12                  => 'Twelve',
        13                  => 'Thirteen',
        14                  => 'Fourteen',
        15                  => 'Fifteen',
        16                  => 'Sixteen',
        17                  => 'Seventeen',
        18                  => 'Eighteen',
        19                  => 'Nineteen',
        20                  => 'Twenty',
        30                  => 'Thirty',
        40                  => 'Fourty',
        50                  => 'Fifty',
        60                  => 'Sixty',
        70                  => 'Seventy',
        80                  => 'Eighty',
        90                  => 'Ninety',
        100                 => 'Hundred',
        1000                => 'Thousand',
        1000000             => 'Million',
        1000000000          => 'Billion',
        1000000000000       => 'Trillion',
        1000000000000000    => 'Quadrillion',
        1000000000000000000 => 'Quintillion'
    );

    if (!is_numeric($number)) {
        return false;
    }

    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        // overflow
        trigger_error(
            'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
            E_USER_WARNING
        );
        return false;
    }

    if ($number < 0) {
        return convert_number_to_words(abs($number));
    }

    $string = $fraction = null;

    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }

    switch (true) {
        case $number < 21:
            $string = $dictionary[$number];
            break;
        case $number < 100:
            $tens   = ((int) ($number / 10)) * 10;
            $units  = $number % 10;
            $string = $dictionary[$tens];
            if ($units) {
                $string .= $hyphen . $dictionary[$units];
            }
            break;
        case $number < 1000:
            $hundreds  = $number / 100;
            $remainder = $number % 100;
            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
            if ($remainder) {
                $string .= $conjunction . convert_number_to_words($remainder);
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= convert_number_to_words($remainder);
            }
            break;
    }

    if (null !== $fraction && is_numeric($fraction)) {
        $string .= $decimal;
        $words = array();
        foreach (str_split((string) $fraction) as $number) {
            $words[] = $dictionary[$number];
        }
        $string .= implode(' ', $words);
    }

    return $string;
}
function get_gl_trans11($type, $trans_id)
{
    $sql = "SELECT gl.*, cm.account_name, IFNULL(refs.reference, '') AS reference, user.real_name, 
			COALESCE(st.tran_date, dt.tran_date, bt.trans_date, grn.delivery_date, gl.tran_date) as doc_date,bt.cheque,st.cheque,
			IF(ISNULL(st.supp_reference), '', st.supp_reference) AS supp_reference
	FROM ".TB_PREF."gl_trans as gl
		LEFT JOIN ".TB_PREF."chart_master as cm ON gl.account = cm.account_code
		LEFT JOIN ".TB_PREF."refs as refs ON (gl.type=refs.type AND gl.type_no=refs.id)
		LEFT JOIN ".TB_PREF."audit_trail as audit ON (gl.type=audit.type AND gl.type_no=audit.trans_no AND NOT ISNULL(gl_seq))
		LEFT JOIN ".TB_PREF."users as user ON (audit.user=user.id)
	# all this below just to retrieve doc_date :>
		LEFT JOIN ".TB_PREF."supp_trans st ON gl.type_no=st.trans_no AND st.type=gl.type AND (gl.type!=".ST_JOURNAL." OR gl.person_id=st.supplier_id)
		LEFT JOIN ".TB_PREF."grn_batch grn ON grn.id=gl.type_no AND gl.type=".ST_SUPPRECEIVE." AND gl.person_id=grn.supplier_id
		LEFT JOIN ".TB_PREF."debtor_trans dt ON gl.type_no=dt.trans_no AND dt.type=gl.type AND (gl.type!=".ST_JOURNAL." OR gl.person_id=dt.debtor_no)
		LEFT JOIN ".TB_PREF."bank_trans bt ON bt.type=gl.type AND bt.trans_no=gl.type_no AND bt.amount!=0
			 AND bt.person_type_id=gl.person_type_id AND bt.person_id=gl.person_id
		LEFT JOIN ".TB_PREF."journal j ON j.type=gl.type AND j.trans_no=gl.type_no"

        ." WHERE gl.type= ".db_escape($type)
        ." AND gl.type_no = ".db_escape($trans_id)
        ." AND gl.amount <> 0"

        ." ORDER BY tran_date, counter";

    return db_query($sql, "The gl transactions could not be retrieved");
}

function get_bank_trans_new($type, $trans_no=null, $person_type_id=null, $person_id=null)
{
    $sql = "SELECT bt.*, act.*,
		IFNULL(abs(dt.ov_amount), IFNULL(ABS(st.ov_amount), bt.amount)) settled_amount,
		IFNULL(abs(dt.ov_amount/bt.amount), IFNULL(ABS(st.ov_amount/bt.amount), 1)) settle_rate,
		IFNULL(debtor.curr_code, IFNULL(supplier.curr_code, act.bank_curr_code)) settle_curr

		FROM ".TB_PREF."bank_trans bt
				 LEFT JOIN ".TB_PREF."debtor_trans dt ON dt.type=bt.type AND dt.trans_no=bt.trans_no
				 LEFT JOIN ".TB_PREF."debtors_master debtor ON debtor.debtor_no = dt.debtor_no
				 LEFT JOIN ".TB_PREF."supp_trans st ON st.type=bt.type AND st.trans_no=bt.trans_no
				 LEFT JOIN ".TB_PREF."suppliers supplier ON supplier.supplier_id = st.supplier_id,
			 ".TB_PREF."bank_accounts act
		WHERE act.id=bt.bank_act ";
    if ($type != null)
        $sql .= " AND bt.type=".db_escape($type);
    if ($trans_no != null)
        $sql .= " AND bt.trans_no = ".db_escape($trans_no);
    if ($person_type_id != null)
        $sql .= " AND bt.person_type_id = ".db_escape($person_type_id);
    if ($person_id != null)
        $sql .= " AND bt.person_id = ".db_escape($person_id);
    $sql .= " ORDER BY trans_date, bt.id";

    return db_query($sql, "query for bank transaction");
}
function get_account_chart_master_($account_code)
{
    $sql = "SELECT account_name
			FROM 0_chart_master masters, 0_chart_types chart
			WHERE masters.account_type = chart.id
			AND masters.account_code = '$account_code'";
    $result = db_query($sql, "could not process Requisition to Purchase Order");
    $row = db_fetch($result);
    return $row[0] ;

}
function get_dimension_new($id, $allow_null=false)
{
    $sql = "SELECT name FROM " . TB_PREF . "dimensions	WHERE id=" . db_escape($id);

    $result = db_query($sql, "The dimension could not be retrieved");

    $row = db_fetch_row($result);
    return $row[0];
}
function get_account_chart_master_reciept($account_code)
{
    $sql = "SELECT account_name
			FROM 0_chart_master masters, 0_chart_types chart
			WHERE masters.account_type = chart.id
			AND masters.account_code = '$account_code'";
    $result = db_query($sql, "could not process Requisition to Purchase Order");
    $row = db_fetch($result);
    return $row[0] ;

}
function print_Chart_of_Accounts()
{
	global $path_to_root;

	$trans_no = $_POST['PARAM_0'];
    $type = $_POST['PARAM_1'];
	$comments = $_POST['PARAM_2'];
	$orientation = $_POST['PARAM_3'];
	$destination = $_POST['PARAM_4'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
	$dec = 0;

    $cols = array(0, 67, 280, 355, 430);

    $headers = array(_(' Account Code'), _(' Account Name'), _('Dimention 1'),_('Dimention 2'), _('Amount'));

    $aligns = array('left',	'left',	'right','right','right');
    $params = array();

	$rep = new FrontReport(_(''), "ChartOfAccounts", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$rep->Font();
    $rep->SetHeaderType('Header222');
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->NewPage();

    $rep->Font();

    $result1 = get_bank_trans_new($type,$trans_no);
    $from_trans = db_fetch($result1);
    global $systypes_array;
    $trans_name = $systypes_array[$from_trans['type']];
    $rep->font('b');
    $rep->setfontsize(19);
    $rep->MultiCell(190, 20, "".$trans_name ,0, 'C', 0, 2, 210,70, true);
    $rep->setfontsize(8);
    $rep->font('');
    $rep->MultiCell(190, 20, "".$from_trans['cheque'] ,0, 'L', 0, 2, 100,140, true);
    $rep->MultiCell(210, 30, "".$from_trans['account_code']." - ". $from_trans['bank_account_name'] ,0, 'L', 0, 2, 100,125, true);
    $rep->MultiCell(160, 20, "". $from_trans["ref"] ,0, 'L', 0, 2, 110,110, true);
    function get_branch_name_od($branch_id)
    {
        $sql = "SELECT br_name FROM ".TB_PREF."cust_branch 
		WHERE branch_code = ".db_escape($branch_id);

        $result = db_query($sql,"could not retreive name for branch" . $branch_id);

        $myrow = db_fetch_row($result);
        return $myrow[0];
    }
  // $rep->MultiCell(240, 30, "".get_branch_name_od($from_trans['person_id']) ,0, 'L', 0, 2, 110,155, true);
    //$words = price_in_words($reciept['amount'], ST_BANKDEPOSIT);

     $rep->MultiCell(60, 20, sql2date($from_trans['trans_date']),0, 'C', 0, 2, 500,100, true);
    $rep->MultiCell(350, 20,"Amount in Words  " . convert_number_to_words($from_trans['amount']),1, 'L', 0, 2, 40,340, true);
//    $rep->MultiCell(60, 20, $from_trans['ref'],0, 'C', 0, 2, 90,100, true);
//    $rep->MultiCell(100, 20, $from_trans['name'],0, 'C', 0, 2, 160,150, true);
//    $rep->MultiCell(150, 20, $from_trans['address1'],0, 'C', 0, 2, 150,180, true);
//    $rep->MultiCell(150, 20, $from_trans['phone'],0, 'C', 0, 2, 150,205, true);
//
//    $rep->MultiCell(300, 20,get_comments_string($from_trans['type'], $from_trans['trans_no']),0, 'C', 0, 2, 125,288, true);
//
   $rep->MultiCell(120, 20, number_format2($from_trans['amount']),1, 'C', 0, 2, 420,340, true);

    
    $rep->font('b');
    $rep->MultiCell(160, 20, "Date:" ,0, 'L', 0, 2, 480,100, true);

    $rep->MultiCell(160, 20, "Voucher No :" ,0, 'L', 0, 2, 50,110, true);

    $rep->MultiCell(160, 20, "Credit Code:" ,0, 'L', 0, 2, 50,125, true); //  $from_trans['person_id']$from_trans['account_code']." - ". $from_trans['bank_account_name']
 ///   $rep->MultiCell(160, 20, "Cheque No:" ,0, 'L', 0, 2, 50,140, true);


    $rep->MultiCell(200, 20, get_account_chart_master_reciept($from_trans),0, 'C', 0, 2, 180,630, true);
   // $rep->MultiCell(170, 30, "Particulars To:" ,0, 'L', 0, 2, 50,155, true);
    

    $k = 0;
 
     $res=get_gl_trans11($from_trans['type'],$from_trans['trans_no']);
        while ($myrow1 = db_fetch($res)) {
            $rep->TextCol(0, 1,	   $myrow1['account'], -2);

            $rep->TextCol(1, 2,	get_account_chart_master_($myrow1['account']), -2);
            $rep->NewLine();
            $as_dimen = get_dimension_new($myrow1['dimension_id']);
            $rep->TextCol(2, 3,$as_dimen, -2);
            $as_dimen = get_dimension_new($myrow1['dimension2_id']);
            $rep->TextCol(3, 4,	$as_dimen, -2);
            $rep->TextCol(4, 5,	$myrow1['amount'], -2);

        $rep->NewLine();




       // $rep->TextCol(0, 1,	$myrow['amount'], -2);
        alt_table_row_color($k);
    }

    $rep->MultiCell(400, 25, "_________________" ,0, 'L', 0, 2, 80,380, true);
    $rep->MultiCell(400, 25, "_________________" ,0, 'L', 0, 2, 250,380, true);
    $rep->MultiCell(400, 25, "_________________" ,0, 'L', 0, 2, 400,380, true);
    $rep->MultiCell(100, 25, "Prepered By" ,0, 'C', 0, 2, 70,400, true);
    $rep->MultiCell(100, 25, "Approved  By" ,0, 'C', 0, 2, 240,400, true);
    $rep->MultiCell(100, 25, "Recieved  By" ,0, 'C', 0, 2, 390,400, true);


    //   $rep->MultiCell(200, 25, "".$employee['emp_code'] ,0, 'C', 0, 2, 560,100, true);
  //  $rep->MultiCell(225, 70, "" ,1, 'L', 0, 2, 610,90, true);
   // $rep->MultiCell(235, 80, "______________" ,0, 'L', 0, 2, 450,100, true);

   // $rep->MultiCell(200, 25, "No:" ,0, 'L', 0, 2, 82,100, true);
   // $rep->MultiCell(170, 25, "".$employee['emp_code'] ,0, 'C', 0, 2, 150,100, true);
   // $rep->MultiCell(100, 25, "_____________" ,0, 'L', 0, 2, 100,100, true);



    //$rep->MultiCell(170, 25, "Name" ,0, 'L', 0, 2, 82,150, true);
    //$rep->MultiCell(400, 25, "_______________________________________________________________________________" ,0, 'L', 0, 2, 160,150, true);


    //$rep->MultiCell(170, 25, "Address" ,0, 'L', 0, 2, 82,180, true);
    //$rep->MultiCell(402, 25, "________________________________________________________________________________" ,0, 'L', 0, 2, 160,180, true);

//
//
//
    //$rep->MultiCell(170, 25, "Phone" ,0, 'L', 0, 2, 82.5,205, true);
    //$rep->MultiCell(402, 25, "________________________________________________________________________________" ,0, 'L', 0, 2,160,205, true);
//

   // $rep->MultiCell(170, 25, "Recieved Sum Of Rupees: " ,0, 'L', 0, 2,80,245, true);

  //  $rep->MultiCell(170, 25, "Total Leaves Due" ,0, 'L', 0, 2, 80,270, true);
    //$rep->MultiCell(400, 25, "_________________________________________________________________________" ,0, 'L', 0, 2, 200,245, true);

    //$rep->MultiCell(370, 25, "Particular:" ,0, 'L', 0, 2, 80,290, true);
    //$rep->MultiCell(402, 25, "______________________________________________________________________________" ,0, 'L', 0, 2,170 ,290, true);

    //$rep->MultiCell(370, 25, "On Account Of" ,0, 'L', 0, 2, 80,330, true);
    ///$rep->MultiCell(402, 25, "________________________________________________________________________________" ,0, 'L', 0, 2, 155,330, true);





    //$rep->MultiCell(370, 25, "RS:" ,0, 'L', 0, 2, 80,365, true);
    //$rep->MultiCell(402, 25, "_______________________________________________________________________________" ,0, 'L', 0, 2, 150,365, true);


     //$rep->MultiCell(370, 25, "General Secretary/Treasurer" ,0, 'L', 0, 2, 400,420, true);
//    $rep->MultiCell(370, 25, "Total Rs" ,0, 'L', 0, 2, 80,480, true);
//    //$rep->MultiCell(370, 25, "_________________________________________" ,0, 'L', 0, 2, 82,340, true);
//    $rep->Line($rep->row -385, 1);

     $rep->font('');

//    $rep->MultiCell(170, 25, "Leaves Availed" ,0, 'L', 0, 2, 80,295, true);
//    $rep->MultiCell(370, 25, "______________" ,0, 'L', 0, 2, 210,295, true);
//
//
//    $rep->MultiCell(170, 25, "Leaves Enchased" ,0, 'L', 0, 2, 80,320, true);
//
//    $rep->MultiCell(370, 25, "______________" ,0, 'L', 0, 2, 210,320, true);
//
//
//    $rep->MultiCell(170, 25, "Less Income Tax" ,0, 'L', 0, 2, 220,500, true);
//    $rep->MultiCell(370, 25, "___________________      " ,0, 'L', 0, 2, 400,500, true);
//
//    $rep->MultiCell(170, 25, "Less Advance Against Salary" ,0, 'L', 0, 2,220,520, true);
//    $rep->MultiCell(370, 25, "____________________ " ,0, 'L', 0, 2, 400,520, true);
//    $rep->MultiCell(170, 25, "Less Advance Against Gratuity" ,0, 'L', 0, 2,220,540, true);
//    $rep->MultiCell(370, 25, "____________________" ,0, 'L', 0, 2, 400,540, true);
//    $rep->font('');
//    $rep->MultiCell(170, 25, "Add Field Expense" ,0, 'L', 0, 2,220,600, true);
//    $rep->MultiCell(370, 25, "___________________________      " ,0, 'L', 0, 2, 400,600, true);

    $rep->NewLine(2);
    $rep->Font(b);
    $rep->fontSize += 2;

    $rep->fontSize -= 2;
    $rep->Font('b');
    //$rep->TextCol(0, 1,"Grand Total");
    //$rep->TextCol(3, 4,$sum);
    $rep->NewLine();
    $rep->End();
}
?>