<?php

$page_security = $_POST['PARAM_0'] == $_POST['PARAM_1'] ?
	'SA_SUPPTRANSVIEW' : 'SA_SUPPBULKREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Purchase Orders
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/db/crm_contacts_db.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");

//----------------------------------------------------------------------------------------------------

print_po();

//----------------------------------------------------------------------------------------------------
//function convert_number_to_words($number) {
//
//    $hyphen      = '-';
//    $conjunction = ' and ';
//    $separator   = ', ';
//    $negative    = 'negative ';
//    $decimal     = ' point ';
//    $dictionary  = array(
//        0                   => 'Zero',
//        1                   => 'One',
//        2                   => 'Two',
//        3                   => 'Three',
//        4                   => 'Four',
//        5                   => 'Five',
//        6                   => 'Six',
//        7                   => 'Seven',
//        8                   => 'Eight',
//        9                   => 'Nine',
//        10                  => 'Ten',
//        11                  => 'Eleven',
//        12                  => 'Twelve',
//        13                  => 'Thirteen',
//        14                  => 'Fourteen',
//        15                  => 'Fifteen',
//        16                  => 'Sixteen',
//        17                  => 'Seventeen',
//        18                  => 'Eighteen',
//        19                  => 'Nineteen',
//        20                  => 'Twenty',
//        30                  => 'Thirty',
//        40                  => 'Fourty',
//        50                  => 'Fifty',
//        60                  => 'Sixty',
//        70                  => 'Seventy',
//        80                  => 'Eighty',
//        90                  => 'Ninety',
//        100                 => 'Hundred',
//        1000                => 'Thousand',
//        1000000             => 'Million',
//        1000000000          => 'Billion',
//        1000000000000       => 'Trillion',
//        1000000000000000    => 'Quadrillion',
//        1000000000000000000 => 'Quintillion'
//    );
//
//    if (!is_numeric($number)) {
//        return false;
//    }
//
//    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
//        // overflow
//        trigger_error(
//            'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
//            E_USER_WARNING
//        );
//        return false;
//    }
//
//    if ($number < 0) {
//        return $negative . convert_number_to_words(abs($number));
//    }
//
//    $string = $fraction = null;
//
//    if (strpos($number, '.') !== false) {
//        list($number, $fraction) = explode('.', $number);
//    }
//
//    switch (true) {
//        case $number < 21:
//            $string = $dictionary[$number];
//            break;
//        case $number < 100:
//            $tens   = ((int) ($number / 10)) * 10;
//            $units  = $number % 10;
//            $string = $dictionary[$tens];
//            if ($units) {
//                $string .= $hyphen . $dictionary[$units];
//            }
//            break;
//        case $number < 1000:
//            $hundreds  = $number / 100;
//            $remainder = $number % 100;
//            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
//            if ($remainder) {
//                $string .= $conjunction . convert_number_to_words($remainder);
//            }
//            break;
//        default:
//            $baseUnit = pow(1000, floor(log($number, 1000)));
//            $numBaseUnits = (int) ($number / $baseUnit);
//            $remainder = $number % $baseUnit;
//            $string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
//            if ($remainder) {
//                $string .= $remainder < 100 ? $conjunction : $separator;
//                $string .= convert_number_to_words($remainder);
//            }
//            break;
//    }
//
//    if (null !== $fraction && is_numeric($fraction)) {
//        $string .= $decimal;
//        $words = array();
//        foreach (str_split((string) $fraction) as $number) {
//            $words[] = $dictionary[$number];
//        }
//        $string .= implode(' ', $words);
//    }
//
//    return $string;
//}
function get_payment_terms_name_purchaseinvoice($id)
{
    $sql = "SELECT terms FROM ".TB_PREF."payment_terms WHERE terms_indicator =" .db_escape($id);
    $result = db_query($sql, 'error');
    $row = db_fetch_row($result);
    return $row[0];
}
function get_unit($id)
{
    $sql = "SELECT units FROM ".TB_PREF."stock_master WHERE stock_id =" .db_escape($id);
    $result = db_query($sql, 'error');
    $row = db_fetch_row($result);
    return $row[0];
}
function get_purchaseinvoice_reports($id)
{
    $sql = "SELECT  CONCAT(reference,'  ',name) FROM ".TB_PREF."dimensions
    WHERE id = ".db_escape($id)."
    ";
    $query= db_query($sql, "Cannot get dimensions");
    $fetch=db_fetch_row($query);
    return $fetch[0];
}
function get_purchaseinvoice_ntngst_reports($id)
{
    $sql = "SELECT *  FROM ".TB_PREF."dimensions
    WHERE id = ".db_escape($id)."
    ";
    $query= db_query($sql, "Cannot get dimensions");
    return db_fetch($query);

}

//function get_io_($order_no)
//{
//   	$sql = "SELECT ".TB_PREF."supp_trans.*, ".TB_PREF."suppliers.supp_name, ".TB_PREF."suppliers.supplier_id,  "
//   		.TB_PREF."suppliers.supp_account_no,".TB_PREF."suppliers.tax_included,".TB_PREF."suppliers.gst_no AS tax_id,
//   		".TB_PREF."suppliers.curr_code, ".TB_PREF."suppliers.payment_terms, ".TB_PREF."locations.location_name,
//   		".TB_PREF."suppliers.address, ".TB_PREF."suppliers.contact, ".TB_PREF."suppliers.tax_group_id
//   		 ,".TB_PREF."supp_trans.dimension_id as dimensions
//		FROM ".TB_PREF."supp_trans, ".TB_PREF."suppliers,
//
//		".TB_PREF."locations
//		WHERE ".TB_PREF."supp_trans.supplier_id = ".TB_PREF."suppliers.supplier_id
//		AND ".TB_PREF."supp_trans.trans_no = ".db_escape($order_no);
//   	$result = db_query($sql, "The order cannot be retrieved");
//    return db_fetch($result);
//}
function get_grn_($order_no)
{
   	$sql = "SELECT ".TB_PREF."grn_batch.*, ".TB_PREF."suppliers.supp_name, ".TB_PREF."suppliers.supplier_id,  "
   		.TB_PREF."suppliers.supp_account_no,".TB_PREF."suppliers.tax_included,".TB_PREF."suppliers.gst_no AS tax_id,
   		".TB_PREF."suppliers.curr_code, ".TB_PREF."suppliers.payment_terms, ".TB_PREF."locations.location_name,
   		".TB_PREF."suppliers.address, ".TB_PREF."suppliers.contact, ".TB_PREF."suppliers.tax_group_id
   		 ,".TB_PREF."grn_batch.supplier_id as dimensions
		FROM ".TB_PREF."grn_batch, ".TB_PREF."suppliers, 
		
		".TB_PREF."locations
		WHERE ".TB_PREF."grn_batch.supplier_id = ".TB_PREF."suppliers.supplier_id
		AND ".TB_PREF."grn_batch.id = ".db_escape($order_no);
   	$result = db_query($sql, "The order cannot be retrieved");
    return db_fetch($result);
}

//function get_po_details_($order_no)
//{
//	$sql = "SELECT ".TB_PREF."supp_invoice_items.*, units
//		FROM ".TB_PREF."supp_invoice_items
//		LEFT JOIN ".TB_PREF."stock_master
//		ON ".TB_PREF."supp_invoice_items.stock_id=".TB_PREF."stock_master.stock_id
//		WHERE supp_trans_no =".db_escape($order_no)." ";
//	$sql .= " ORDER BY supp_trans_no";
//	return db_query($sql, "Retreive order Line Items");
//}
function get_grn_details_($order_no)
{
	$sql = "SELECT ".TB_PREF."grn_items.*, units
		FROM ".TB_PREF."grn_items
		LEFT JOIN ".TB_PREF."stock_master
		ON ".TB_PREF."grn_items.id=".TB_PREF."stock_master.stock_id
		WHERE grn_batch_id =".db_escape($order_no)." ";
	$sql .= " ORDER BY grn_batch_id";
	return db_query($sql, "Retreive order Line Items");
}

function get_tax_rate_1()
{
    $sql = "SELECT rate FROM ".TB_PREF."tax_types
	 WHERE ".TB_PREF."tax_types.id = 1";
    $result = db_query($sql, 'error');
    return $result;
}

function get_tax_rate_2()
{
    $sql = "SELECT rate FROM ".TB_PREF."tax_types
	 WHERE ".TB_PREF."tax_types.id = 2";
    $result = db_query($sql, 'error');
    return $result;
}

function get_phoneno_for_suppliers ($supplier_id)
{
    $sql = "SELECT * FROM `0_crm_persons` WHERE `id` IN ( 
            SELECT person_id FROM `0_crm_contacts`
            WHERE `type`='supplier' AND `action`='general' AND entity_id IN ( 
            SELECT supplier_id FROM `0_suppliers` WHERE supplier_id = ".db_escape($supplier_id)."))";
    $query = db_query($sql, "Error");
    return db_fetch($query);
}
////
function get_qoh_on_date_new($stock_id, $location=null, $date_=null, $exclude=0, $customer_id)
{


    if ($date_ == null)
    {
        $sql = "SELECT SUM(qty) FROM ".TB_PREF."stock_moves
            WHERE stock_id=".db_escape($stock_id);
        $date_ = Today();
        $date = date2sql($date_);


    }
    else
    {
        $date = date2sql($date_);
        $sql = "SELECT SUM(qty) FROM ".TB_PREF."stock_moves
            WHERE stock_id=".db_escape($stock_id)."
            AND tran_date <= '$date'";


    }



    if ($location != null)
        $sql .= " AND loc_code = ".db_escape($location);

    if ($customer_id != null)
        $sql .= " AND debtor_no = ".db_escape($customer_id);




    $result = db_query($sql, "QOH calulcation failed");

    $myrow = db_fetch_row($result);
    if ($exclude > 0)
    {
        $sql = "SELECT SUM(qty) FROM ".TB_PREF."stock_moves
            WHERE stock_id=".db_escape($stock_id)
            ." AND type=".db_escape($exclude)
            ." AND tran_date = '$date'";

        $result = db_query($sql, "QOH calulcation failed");
        $myrow2 = db_fetch_row($result);
        if ($myrow2 !== false)
            $myrow[0] -= $myrow2[0];
    }

    return $myrow[0];
}

function print_po()
{
	global $path_to_root, $show_po_item_codes;

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$currency = $_POST['PARAM_2'];
	$email = $_POST['PARAM_3'];
	$comments = $_POST['PARAM_4'];
	$orientation = $_POST['PARAM_5'];

	if (!$from || !$to) return;

	$orientation = ($orientation ? 'L' : 'P');
	$dec = user_price_dec();

    $cols = array(4, 20, 70, 130, 310,380,430,500,510);

    // $headers in doctext.inc

    $aligns = array('left',	'left',	'left', 'left', 'right', 'right', 'right', 'center');

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
		$rep = new FrontReport(_('PURCHASE ORDER'), "PurchaseOrderBulk", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

	for ($i = $from; $i <= $to; $i++)
	{
        $sign = -1;

        $myrow = get_grn_($i);
		$baccount = get_default_bank_account($myrow['curr_code']);
		$params['bankaccount'] = $baccount['id'];

		if ($email == 1)
		{
			$rep = new FrontReport("", "", user_pagesize(), 9, $orientation);
			$rep->title = _('PURCHASE ORDER');
			$rep->filename = "PurchaseOrder" . $i . ".pdf";
		}
		$rep->SetHeaderType('Header20999');
		$rep->currency = $cur;
		$rep->Font();
        $rep->Info($params, $cols, $headers, $aligns, null, $headers2);

		$contacts = get_supplier_contacts($myrow['supplier_id'], 'order');
		$rep->SetCommonData($myrow, null, $myrow, $baccount, ST_PURCHORDER1, $contacts);
		$rep->NewPage();
             //$rep->MultiCell(100, 10,"". $myrow['ref'], 0, 'L', 0, 2, 485,193, true);
             //$rep->MultiCell(100, 10,"". sql2date($myrow['tran_date']), 0, 'L', 0, 2, 485,205, true);

//        $cost_center=get_dimension_purchase_ntngst($myrow["dimension_id"]);
//        $rep->MultiCell(200, 10,"".get_dimension_purchase_report($myrow['dimension_id']) , 0, 'L', 0, 2, 410,53, true);
//
//        $rep->MultiCell(300, 10,"GST#                     ". $cost_center['gst'], 0, 'L', 0, 2, 405,115, true);
//        $rep->MultiCell(300, 10, "NTN#                     ".$cost_center['ntn'], 0, 'L', 0, 2, 405,125, true);
//        $rep->MultiCell(300, 10, $cost_center['address'], 0, 'L', 0, 2, 420,65, true);
        $items = $prices = array();
        $myrow3 = db_fetch(get_tax_rate_1());
        $myrow4 = db_fetch(get_tax_rate_2());
        $serial_no = 0;
        $TotalGrossAmount = 0;
        $TotalDisplayDiscount = 0;
        $total_sales_tax_other=0;
        $sales_tax_amount_other=0;
        $total_sales_tax_amount = 0;

        $result = get_grn_details_($i);
		$SubTotal = 0;
		$items = $prices = array();
		while ($myrow2=db_fetch($result))
		{
			$data = get_purchase_data($myrow['supplier_id'], $myrow2['item_code']);
			if ($data !== false)
			{
				if ($data['supplier_description'] != "")
					$myrow2['description'] = $data['supplier_description'];
				if ($data['suppliers_uom'] != "")
					$myrow2['units'] = $data['suppliers_uom'];
				if ($data['conversion_factor'] != 1)
				{
					$myrow2['unit_price'] = round2($myrow2['unit_price'] * $data['conversion_factor'], user_price_dec());
					$myrow2['quantity_ordered'] = round2($myrow2['quantity_ordered'] / $data['conversion_factor'], user_qty_dec());
				}
			}
            $GrossAmount = $myrow2["quantity_ordered"]*$myrow2["unit_price"];
            if($myrow['tax_group_id'] == 1 &&  $myrow2['tax_type_id'] == 2) {
                $sales_tax_amount = (($myrow3['rate'] * $GrossAmount) / 100);
                $sales_tax_amount_other = (($myrow4['rate'] * $GrossAmount) / 100);
                $total_sales_tax_amount += $sales_tax_amount;
                $total_sales_tax_other += $sales_tax_amount_other;
            }
			$Net = round2(($myrow2["unit_price"] * $myrow2["quantity"]), user_price_dec());
			$prices[] = $Net;
			$items[] = $myrow2['item_code'];
			$SubTotal += $Net;
            $qty=$myrow2['qty_recd'];
            $qty_total += $qty;
			$dec2 = 0;
            $serial_no++;
			$DisplayPrice = price_decimal_format($myrow2["unit_price"],$dec2);
			$DisplayQty = number_format2($myrow2["quantity"],get_qty_dec($myrow2['item_code']));
			$DisplayNet = number_format2($Net,$dec);
            $oldrow = $rep->row;
//            $rep->TextColLines(1, 2, $myrow2['description'], -2);
            $newrow = $rep->row;
            $rep->row = $oldrow;
            if ($Net != 0.0 || !is_service($myrow2['mb_flag']) || !isset($no_zero_lines_amount) || $no_zero_lines_amount == 0)
            {


               $rep->TextCol(0, 1,	$serial_no, -2);
               $rep->TextCol(1, 2,		get_reference(ST_PURCHORDER,$myrow['purch_order_no']), -2);
               // $serial_no++;
               
                $rep->TextCol(2, 3,	$myrow2['item_code'], -2);
                 $rep->TextCollines(3, 4,	 $myrow2['description'], -2);
                  $rep->NewLine(-1);
//				$rep->TextCol(3, 4,	$myrow2['units'], -2);
                $qoh = get_qoh_on_date_new($myrow2['item_code'], null,null );
                $rep->TextCol(4, 5,	number_format2($qoh,get_qty_dec($myrow2['item_code'])), -2);
				$rep->TextCol(5, 6,	number_format2($myrow2['qty_recd'],get_qty_dec($myrow2['item_code'])), -2);
                $rep->TextCol(6, 7,	number_format2($qoh + $qty,get_qty_dec($myrow2['item_code'])), -2);
//                $rep->TextCol(4, 5,	$DisplayNet, $dec);
                $rep->TextCol(7, 8,		get_unit($myrow2['item_code']), $dec);
               

//                if($myrow['tax_group_id'] == 1 &&  $myrow2['tax_type_id'] == 2)
//                {
//                    $rep->TextCol(5, 6, $myrow3['rate'] . ' %', -2);
//                   // $rep->TextCol(7, 8, $myrow4['rate'] . ' %', -2);
//                    $rep->AmountCol(7, 8,	$sales_tax_amount, $dec);
//                   // $rep->AmountCol(7, 8,	$sales_tax_amount_other, $dec);
//                }
//                else
//                {
//                    $rep->TextCol(5, 6,'0', -2);
//                 //   $rep->TextCol(7, 8, '0', -2);
//                    //$rep->AmountCol(6, 7,	'0', $dec);
//                    $rep->AmountCol(7, 8,	'0', $dec);
//                }



               // $rep->TextCol(8, 9,	$DisplayNet, -2);
            }

            // $rep->row = $newrow;
         $rep->NewLine(1);
            if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight))
                $rep->NewPage();
        }
        if ($myrow['comments'] != "")
        {
            $rep->NewLine();
//			$rep->TextColLines(1, 5, $myrow['comments'], -2);
        }
        $DisplaySubTot = number_format2($SubTotal,$dec);
        $DisplayFreight = number_format2($myrow["freight_cost"],$dec);

        $rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
        $doctype = ST_PURCHORDER;

//		$rep->TextCol(3, 6, _("Sub-total"), -2);
//		$rep->TextCol(6, 7,	$DisplaySubTot, -2);
//		$rep->NewLine();
//		$rep->TextCol(3, 6, _("Shipping"), -2);
		//$rep->TextCol(6, 7,	$DisplayFreight, -2);
        $rep->NewLine();

        $DisplayTotal = number_format2($myrow["freight_cost"] + $SubTotal, $dec);
       // if ($myrow['tax_included'] == 0) {
//			$rep->TextCol(3, 6, _("TOTAL ORDER EX GST"), - 2);
//			$rep->TextCol(6, 7,	$DisplayTotal, -2);
            $rep->NewLine();
//}//
        $tax_items = get_tax_for_items($items, $prices, $myrow["freight_cost"],
            $myrow['tax_group_id'], $myrow['tax_included'],  null);
        $first = true;
        foreach($tax_items as $tax_item)
        {
            if ($tax_item['Value'] == 0)
                continue;
            $DisplayTax = number_format2($tax_item['Value'], $dec);

            $tax_type_name = $tax_item['tax_type_name'];

//            if ($myrow['tax_included'])
//            {
//                if (isset($alternative_tax_include_on_docs) && $alternative_tax_include_on_docs == 1)
//                {
//                    if ($first)
//                    {
//                        $rep->TextCol(3, 6, _("Total Tax Excluded"), -2);
//                        $rep->TextCol(6, 7,	number_format2($sign*$tax_item['net_amount'], $dec), -2);
//                        $rep->NewLine();
//                    }
//                    $rep->TextCol(3, 6, $tax_type_name, -2);
//                    $rep->TextCol(6, 7,	$DisplayTax, -2);
//                    $first = false;
//                }
//                else
//                    $rep->TextCol(3, 7, _("Included") . " " . $tax_type_name . " " . _("Amount"). ": " . $DisplayTax, -2);
//            }
//            else
//            {
//                $SubTotal += $tax_item['Value'];
//                $rep->TextCol(3, 6, $tax_type_name, -2);
//                $rep->TextCol(6, 7,	$DisplayTax, -2);
//            }
//            $rep->NewLine();
        }
        $TotalGrossAmount +=$GrossAmount;

        $rep->NewLine(-1.25);
        $DisplayTotal = number_format2($myrow["freight_cost"] + $SubTotal, $dec);
        $DisplayTotalInWords = round2($myrow["freight_cost"] + $SubTotal, $dec);
        $rep->Font('bold');
        $rep->TextCol(3, 4, _("Total Quantity"), - 2);
      //  $rep->AmountCol(4, 5, $TotalGrossAmount, $dec);
       // $rep->AmountCol(7, 8, $total_sales_tax_amount, $dec);
       // $rep->AmountCol(7, 8,$total_sales_tax_other, $dec);
        $rep->TextCol(5, 6, number_format2(($qty_total),get_qty_dec($myrow2['item_code'])), $dec);
        $rep->NewLine(2);
        // $rep->TextCol(1, 2, _("Amount in Words :"), -2);
        $words = price_in_words($myrow["freight_cost"] + $SubTotal, ST_PURCHORDER);
        if ($words != "")
        {
            $rep->NewLine(1);
//            $rep->TextCol(1, 7, $myrow['curr_code'] . ": " . $words, - 2);
        }
        $rep->Font();
        if ($email == 1)
        {
            $myrow['dimension_id'] = $paylink; // helper for pmt link
            $rep->End($email);
        } // $rep->MultiCell(225, 60, "".convert_number_to_words($DisplayTotalInWords ) .' Only', 0, 'L', 0, 2, 40,685, true);

    }

    $rep->Font('bold');
    $rep->Font('bold');
//    $rep->MultiCell(230, 75, "Carriage & Freight Rs." , 0, 'L', 0, 2, 360,680, true);
//    $rep->MultiCell(230, 75, "".$DisplayFreight , 0, 'L', 0, 2, 470,680, true);

//    $rep->MultiCell(250, 25, "Net Total Rs." , 0, 'L', 0, 2, 380,700, true);
//    $rep->MultiCell(250, 25, "".price_format($SubTotal) , 0, 'L', 0, 2, 470,700, true);
//    $rep->MultiCell(225, 60, "" , 1, 'L', 0, 2, 340,670, true);

//    $rep->MultiCell(225, 60, "Amount In Words" , 0, 'L', 0, 2, 40,670, true);
//    $rep->MultiCell(225, 60, "Authorized Signatures:" , 0, 'L', 0, 2, 40,750, true);
    $rep->Font('');
    $rep->MultiCell(225, 60, "_______________________________" , 0, 'L', 0, 2, 45,750, true);
    $rep->MultiCell(225, 60, "Prepared by"  , 0, 'L', 0, 2, 100,770, true);
    $rep->MultiCell(225, 60, "_______________________________" , 0, 'L', 0, 2, 410,750, true);
    $rep->MultiCell(225, 60, "Checked by"  , 0, 'L', 0, 2, 460,770, true);

    if ($email == 0)
		$rep->End();
}


?>