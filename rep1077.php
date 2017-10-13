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
function get_bank_details()
{
    $sql = "SELECT 	bank_address,bank_account_number,bank_name FROM ".TB_PREF."bank_accounts 
	WHERE dflt_curr_act = 1";
    $result = db_query($sql, "could not retreive default customer currency code");
    $row = db_fetch($result);
    return $row;
}
function get_customer_information($debtor_no)
{
    $sql = "SELECT * FROM `0_crm_persons` WHERE `id` IN (
	SELECT person_id FROM `0_crm_contacts` WHERE `type`='cust_branch' AND `action`='general' AND entity_id IN (
	SELECT branch_code FROM `0_cust_branch` WHERE debtor_no = '$debtor_no'))";
    $result = db_query($sql,"Error");
    return db_fetch($result);
}
/*
function get_tax ($id)
{
	$sql = "SELECT rate FROM ".TB_PREF."tax_types WHERE id = ".db_escape($id)."
	";
	$result = db_query($sql, "could not retreive default customer currency code");
	$row = db_fetch_row($result);
	return $row['0'];
}
*/
//function get_invoice_date_through_dn($type,$trans_no)
//{
//	$sql = "SELECT * FROM ".TB_PREF."debtor_trans WHERE trans_no=".db_escape($trans_no);
//	$sql .= " AND type =$type ";
//	$result = db_query($sql, "could not query reference table");
//	$row = db_fetch($result);
//	return sql2date($row['tran_date']);
//
//}
function get_tax_rate($trans_no, $stock_id)
{
    $sql = "SELECT (unit_tax/unit_price)*100 FROM ".TB_PREF."debtor_trans_details
	WHERE debtor_trans_no = ".db_escape($trans_no)."
	AND stock_id= ".db_escape($stock_id)."
	AND debtor_trans_type = 10
	
	";
    $result = db_query($sql, "could not retreive default customer currency code");
    $row = db_fetch_row($result);
    return $row['0'];
}

function get_tax_amount($trans_no, $stock_id)
{
    $sql = "SELECT (unit_tax * quantity) FROM ".TB_PREF."debtor_trans_details 
	WHERE debtor_trans_no = ".db_escape($trans_no)."
	AND stock_id= ".db_escape($stock_id)."
	AND debtor_trans_type = 10
	
	";
    $result = db_query($sql, "could not retreive default customer currency code");
    $row = db_fetch_row($result);
    return $row['0'];
}

function get_delivery_date($trans_no)
{
    $sql = "SELECT tran_date FROM ".TB_PREF."debtor_trans 
	WHERE trans_no = ".db_escape($trans_no)."
	AND type= ".db_escape(13);
    $result = db_query($sql, "could not retreive default customer currency code");
    $row = db_fetch_row($result);
    return $row[0];
}

function get_do_no($order_)
{
    $sql = "SELECT reference FROM ".TB_PREF."debtor_trans 
	WHERE order_ = ".db_escape($order_)."
	AND type= ".db_escape(13);
    $result = db_query($sql, "could not retreive default customer currency code");
    $row = db_fetch_row($result);
    return $row[0];
}
print_invoices();

//----------------------------------------------------------------------------------------------------
function convert_number_to_words2($number) {

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
        return $negative . convert_number_to_words2(abs($number));
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
                $string .= $conjunction . convert_number_to_words2($remainder);
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = convert_number_to_words2($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= convert_number_to_words2($remainder);
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

function buyers_phone($debtor_no)
{
    $sql="SELECT * FROM `0_crm_persons` WHERE `id` IN (
  SELECT person_id FROM `0_crm_contacts` WHERE `type`='cust_branch' AND `action`='general' AND entity_id IN (
	SELECT branch_code FROM `0_cust_branch` WHERE debtor_no='$debtor_no')) ";

    $result = db_query($sql, "Cannot retreive a wo issue");

    return db_fetch($result);
}

function print_invoices()
{
    global $path_to_root, $alternative_tax_include_on_docs, $suppress_tax_rates, $no_zero_lines_amount;

    include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $from = $_POST['PARAM_0'];
    $to = $_POST['PARAM_1'];
    $currency = $_POST['PARAM_2'];
    $email = $_POST['PARAM_3'];
    $pay_service = $_POST['PARAM_4'];
    $comments = $_POST['PARAM_5'];
    $orientation = $_POST['PARAM_6'];

    if (!$from || !$to) return;

    $orientation = ('P');
    //$dec = user_price_dec();
    $dec = 0;

    $fno = explode("-", $from);
    $tno = explode("-", $to);
    $from = min($fno[0], $tno[0]);
    $to = max($fno[0], $tno[0]);

    $cols = array(0,20, 40, 190, 250, 320, 350, 430, 550);
    $cols2 = array(0,20, 40, 190, 250, 320, 350, 430, 550);
    // $headers in doctext.inc
    $aligns = array('left','left','left','right','right','right','right','right','right');
    $aligns2 = array('left','left','left','left','left','left','left','left','left');

    $params = array('comments' => $comments);

    $cur = get_company_Pref('curr_default');

    if ($email == 0)
        $rep = new FrontReport(_('INVOICE'), "InvoiceBulk", user_pagesize(), 8, $orientation);
    if ($orientation == 'L')
        recalculate_cols($cols);
    for ($i = $from; $i <= $to; $i++)
    {
        if (!exists_customer_trans(ST_SALESINVOICE, $i))
            continue;
        $sign = 1;
        $myrow = get_customer_trans($i, ST_SALESINVOICE);
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
        $rep->SetHeaderType('Header10770');
        $rep->currency = $cur;
        $rep->Font();
        $rep->Info($params, $cols, null, $aligns, $cols2, null,$aligns2);


        $contacts = get_branch_contacts($branch['branch_code'], 'invoice', $branch['debtor_no'], true);
        $baccount['payment_service'] = $pay_service;
        $rep->SetCommonData($myrow, $branch, $sales_order, $baccount, ST_SALESINVOICE, $contacts);
        $rep->NewPage();
        $result = get_customer_trans_details(ST_SALESINVOICE, $i);
        $SubTotal = 0;
        $total_price =0;
        $total_including_tax=0;
        $total_amount=0;
        $DisplayPq =0;
        $amount_including_tax = 0;
        $s=0;
        $rep->MultiCell(90,15, get_reference(ST_SALESORDER, $myrow['order_']),0,'L', 0, 2,450,193,true);

        while ($myrow2=db_fetch($result))
        {
            if ($myrow2["quantity"] == 0)
                continue;
            $s++;
            $Net = round2($sign * ((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
                user_price_dec());
            $SubTotal += $Net;
            $DisplayPrice = number_format2($myrow2["unit_price"],(get_qty_dec($myrow2['item_code'])));
            $DisplayQty = number_format2($sign*$myrow2["quantity"], 0);
            $DisplayQty1 = ($sign*$myrow2["quantity"]);
            $DisplayQty2 = ($myrow2["unit_price"] * $DisplayQty1);
            $DisplayPq =  number_format2($DisplayQty2,(get_qty_dec($myrow2['item_code'])) );
            $DisplayPq2 =  ($DisplayQty2);
            $DisplayNet = number_format2($Net,$dec);
            if ($myrow2["discount_percent"]==0)
                $DisplayDiscount ="";
            else
                $DisplayDiscount = number_format2($myrow2["discount_percent"]*100,user_percent_dec()) . "%";
            //$rep->TextCol(0, 1,	$myrow2['stock_id'], -2);
//            $rep->TextColLines(2, 3, $myrow2['StockDescription'], -2);
            $oldrow = $rep->row;

            $newrow = $rep->row;
            $rep->row = $oldrow;
            if ($Net != 0.0 || !is_service($myrow2['mb_flag']) || !isset($no_zero_lines_amount) || $no_zero_lines_amount == 0)
            {

                $rep->setfontsize(+9);
                $rep->TextCol(0, 1,$s, -2);
                $rep->TextCol(1, 2,	$DisplayQty, -2);
//					$rep->TextCol(3, 4,	$myrow2['units'], -2);
                $rep->TextCol(3, 4,	$DisplayPrice, -2);
                $rep->TextCol(4, 5,$DisplayPq, -2);
                $rep->TextCol(5, 6,	round2(get_tax_rate($i, $myrow2['stock_id']))."%", -2);
                //$rate_ = get_tax($myrow2['tax_type_id']);
                //$amount_of_sales_taxincluding_sales_tax  = $rate_ / 100;
                //$amount_including_tax=  $DisplayPq * $amount_of_sales_tax  ;
                $amount_including_tax = get_tax_amount($i, $myrow2['stock_id']);
                //$rep->TextCol(5, 6,	price_format($amount_including_tax), -2);
                $rep->TextCol(6, 7,	number_format2($amount_including_tax,(get_qty_dec($myrow2['item_code']))),-2);

                $including_sales_tax=  $DisplayPq2 + $amount_including_tax;
                $rep->TextCol(7, 8,	number_format2($including_sales_tax,(get_qty_dec($myrow2['item_code']))), -2);
                $rep->TextColLines(2, 3, $myrow2['StockDescription'], -2);
                //$total_price += $myrow2["unit_price"];
                $total_value_excl_tax += $DisplayPq2;
                $total_amount += $amount_including_tax;
                $total_including_tax += $including_sales_tax;
                $rep->setfontsize(+8);

            }
            //$rep->row = $newrow;
            $rep->NewLine(1);
            if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight))
                $rep->NewPage();
        }
        $amount_in_words = convert_number_to_words2($total_including_tax);
        $rep->font('bold');
        $rep->setfontsize(+10);
        $rep->MultiCell(100,15,"Amount In Words:",0,'L',0,0,40,640);
        $rep->font();
        $rep->MultiCell(400,15,"Rupees ".$amount_in_words." Only",0,'L',0,0,40,650);
        $rep->setfontsize(+8);
        $memo = get_comments_string(ST_SALESINVOICE, $i);
        if ($memo != "")
        {
            //$rep->NewLine();
            //$rep->TextColLines(1, 5, $memo, -2);
        }

        $DisplaySubTot = number_format2($SubTotal,$dec);
        $DisplayFreight = number_format2($sign*$myrow["ov_freight"],$dec);

        //$rep->NewLine(14.5);
        $rep->row = $rep->bottomMargin + (5 * $rep->lineHeight);
        $doctype = ST_SALESINVOICE;
        //	$rep->NewLine();

        $rep->NewLine(-9.7);
        $rep->Font('bold');
        $rep->setfontsize(+10);
        $rep->TextCol(2, 3, "TOTAL", -2);
        //$rep->TextCol(2, 3,price_format($total_price), -2);
        $rep->TextCol(4, 5,number_format2($total_value_excl_tax ,(get_qty_dec($myrow2['item_code']))), -2);
        $rep->TextCol(6, 7,number_format2($total_amount,(get_qty_dec($myrow2['item_code']))), -2);
        $rep->TextCol(7, 8,number_format2($total_including_tax,(get_qty_dec($myrow2['item_code']))), -2);
        $rep->Font('');
        $rep->setfontsize(+8);
        //$rep->NewLine(-4);
//			$rep->TextCol(6, 7,	$total_price, -2);
//			$rep->NewLine();
//			$rep->TextCol(3, 6, _("Shipping"), -2);
//			$rep->TextCol(6, 7,	$DisplayFreight, -2);
        $rep->NewLine();
        $tax_items = get_trans_tax_details(ST_SALESINVOICE, $i);
        $first = true;
        
        while ($tax_item = db_fetch($tax_items))
        {
            if ($tax_item['amount'] == 0)
                continue;
            $DisplayTax = number_format2($sign*$tax_item['amount'], $dec);

            if (isset($suppress_tax_rates) && $suppress_tax_rates == 1)
                $tax_type_name = $tax_item['tax_type_name'];
            else
                $tax_type_name = $tax_item['tax_type_name']." (".$tax_item['rate']."%) ";

            if ($tax_item['included_in_price'])
            {
                if (isset($alternative_tax_include_on_docs) && $alternative_tax_include_on_docs == 1)
                {
                    if ($first)
                    {
                        $rep->TextCol(3, 6, _("Total Tax Excluded"), -2);
                        $rep->TextCol(6, 7,	number_format2($sign*$tax_item['net_amount'], $dec), -2);
                        $rep->NewLine();
                    }
                    $rep->TextCol(3, 6, $tax_type_name, -2);
                    $rep->TextCol(6, 7,	$DisplayTax, -2);
                    $first = false;
                }
                else
                    $rep->TextCol(3, 7, _("Included") . " " . $tax_type_name . _("Amount") . ": " . $DisplayTax, -2);
            }
            else
            {
                //$rep->TextCol(3, 6, $tax_type_name, -2);
                //$rep->TextCol(6, 7,	$DisplayTax, -2);
            }
            $rep->NewLine();
        }

        $rep->NewLine();
        $DisplayTotal = number_format2($sign*($myrow["ov_freight"] + $myrow["ov_gst"] +
                $myrow["ov_amount"]+$myrow["ov_freight_tax"]),$dec);
        $rep->Font('bold');

        $words = price_in_words($myrow['Total'], ST_SALESINVOICE);
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

    if ($email == 0)
        $rep->End();
}

?>