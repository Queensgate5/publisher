<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * @copyright       The XUUPS Project http://sourceforge.net/projects/xuups/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         Publisher
 * @subpackage      Action
 * @since           1.0
 * @author          trabis <lusopoemas@gmail.com>
 * @author            Sina Asghari (AKA stranger) <stranger@impresscms.ir>
 * @version         $Id: makepdf.php 335 2011-12-05 20:24:01Z lusopoemas@gmail.com $
 */

error_reporting(0);
include_once dirname(__FILE__) . '/header.php';
if (!is_file(XOOPS_PATH.'/vendor/tcpdf/tcpdf.php')) {
	redirect_header(XOOPS_URL.'/modules/'.$xoopsModule->getVar('dirname').'/viewtopic.php?topic_id='.$topic_id,3,'TCPDF for Xoops not installed');
}
$itemid = PublisherRequest::getInt('itemid');
$item_page_id = PublisherRequest::getInt('page', -1);

if ($itemid == 0) {
    redirect_header("javascript:history.go(-1)", 1, _MD_PUBLISHER_NOITEMSELECTED);
    exit();
}

// Creating the item object for the selected item
$itemObj = $publisher->getHandler('item')->get($itemid);

// if the selected item was not found, exit
if (!$itemObj) {
    redirect_header("javascript:history.go(-1)", 1, _MD_PUBLISHER_NOITEMSELECTED);
    exit();
}

// Creating the category object that holds the selected item
$categoryObj = $publisher->getHandler('category')->get($itemObj->categoryid());

// Check user permissions to access that category of the selected item
if (!$itemObj->accessGranted()) {
    redirect_header("javascript:history.go(-1)", 1, _NOPERM);
    exit();
}

xoops_loadLanguage('main', PUBLISHER_DIRNAME);

$dateformat = $itemObj->datesub();
$sender_inform = sprintf(_MD_PUBLISHER_WHO_WHEN, $itemObj->posterName(), $itemObj->datesub());
$mainImage = $itemObj->getMainImage();
$pdf_data['author'] = $itemObj->posterName();
$pdf_data['title'] = $myts->undoHtmlSpecialChars($categoryObj->name());
$content = '';
if ($mainImage['image_path'] != '') {
    $content .= '<img src="' . $mainImage['image_path'] . '" alt="' . $myts->undoHtmlSpecialChars($mainImage['image_name']) . '"/>';
}
$content .= '<strong><i><u><a href="' . PUBLISHER_URL . '/item.php?itemid=' . $itemid . '" title="' . $myts->undoHtmlSpecialChars($itemObj->title()) . '">' . $myts->undoHtmlSpecialChars($itemObj->title()) . '</a></u></i></strong>';
$content .= '<strong>' . _CO_PUBLISHER_CATEGORY . ' : <a href="' . PUBLISHER_URL . '/category.php?categoryid=' . $itemObj->categoryid() . '" title="' . $myts->undoHtmlSpecialChars($categoryObj->name()) . '">' . $myts->undoHtmlSpecialChars($categoryObj->name()) . '</a></strong>';
$content .= '<br /><strong>' . $sender_inform . '</strong>';
$content .= $itemObj->plain_maintext();

require_once (XOOPS_PATH.'/vendor/tcpdf/tcpdf.php');
if (is_file(XOOPS_PATH.'/vendor/tcpdf/config/lang/'.$xoopsConfig['language'].'.php')) {
	require_once( XOOPS_PATH.'/vendor/tcpdf/config/lang/'.$xoopsConfig['language'].'.php');
} else {
	require_once( XOOPS_PATH.'/vendor/tcpdf/config/lang/english.php');
}
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, _CHARSET, false);

$doc_title = publisher_convertCharset($myts->undoHtmlSpecialChars($itemObj->title()));
$doc_keywords = 'XOOPS';

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(PDF_AUTHOR);
$pdf->SetTitle($doc_title);
$pdf->SetSubject($doc_title);
$pdf->SetKeywords(XOOPS_URL . ', '.' by tcpdf_for_xoops (chg-web.org), '.$doc_title);

$firstLine = XOOPS_URL.' - '.publisher_convertCharset($xoopsConfig['sitename']);
$secondLine = publisher_convertCharset($xoopsConfig['slogan']);

//$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $firstLine, $secondLine);
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $firstLine, $secondLine, array(0, 64, 255), array(0, 64, 128));

//set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP , PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
//set auto page breaks
$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);


$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); //set image scale factor

$pdf->setHeaderFont(Array(PDF_FONT_NAME_SUB, '', PDF_FONT_SIZE_SUB));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->setFooterData($tc=array(0,64,0), $lc=array(0,64,128));

//initialize document
$pdf->Open();
$pdf->AddPage();
$pdf->SetFont(PDF_FONT_NAME_MAIN,PDF_FONT_STYLE_MAIN, PDF_FONT_SIZE_MAIN);
$pdf->writeHTML($content, true, 0, true, 0);
$pdf->Output();
