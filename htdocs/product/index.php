<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/product/index.php
 *  \ingroup    product
 *  \brief      Homepage products and services
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$type=isset($_GET["type"])?$_GET["type"]:(isset($_POST["type"])?$_POST["type"]:'');
if ($type =='' && !$user->rights->produit->lire) $type='1';	// Force global page on service page only
if ($type =='' && !$user->rights->service->lire) $type='0';	// Force global page on product page only

// Security check
if ($type=='0') $result=restrictedArea($user,'produit');
else if ($type=='1') $result=restrictedArea($user,'service');
else $result=restrictedArea($user,'produit|service');

$langs->load("products");

$product_static = new Product($db);


/*
 * View
 */

$transAreaType = $langs->trans("ProductsAndServicesArea");
$helpurl='';
if (! isset($_GET["type"]))
{
	$transAreaType = $langs->trans("ProductsAndServicesArea");
	$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if ((isset($_GET["type"]) && $_GET["type"] == 0) || empty($conf->service->enabled))
{
	$transAreaType = $langs->trans("ProductsArea");
	$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if ((isset($_GET["type"]) && $_GET["type"] == 1) || empty($conf->product->enabled))
{
	$transAreaType = $langs->trans("ServicesArea");
	$helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}

llxHeader("",$langs->trans("ProductsAndServices"),$helpurl);

print_fiche_titre($transAreaType);


//print '<table border="0" width="100%" class="notopnoleftnoright">';
//print '<tr><td valign="top" width="30%" class="notopnoleft">';
print '<div class="fichecenter"><div class="fichethirdleft">';


/*
 * Search Area of product/service
 */
$rowspan=2;
if (! empty($conf->barcode->enabled)) $rowspan++;
print '<form method="post" action="'.DOL_URL_ROOT.'/product/liste.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder nohover" width="100%">';
print "<tr class=\"liste_titre\">";
print '<td colspan="3">'.$langs->trans("Search").'</td></tr>';
print "<tr ".$bc[false]."><td>";
print $langs->trans("Ref").':</td><td><input class="flat" type="text" size="14" name="sref"></td>';
print '<td rowspan="'.$rowspan.'"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
if (! empty($conf->barcode->enabled))
{
	print "<tr ".$bc[false]."><td>";
	print $langs->trans("BarCode").':</td><td><input class="flat" type="text" size="14" name="sbarcode"></td>';
	//print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';
	print '</tr>';
}
print "<tr ".$bc[false]."><td>";
print $langs->trans("Other").':</td><td><input class="flat" type="text" size="14" name="sall"></td>';
//print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';
print '</tr>';
print "</table></form><br>";


/*
 * Number of products and/or services
 */
$prodser = array();
$prodser[0][0]=$prodser[0][1]=$prodser[1][0]=$prodser[1][1]=0;

$sql = "SELECT COUNT(p.rowid) as total, p.fk_product_type, p.tosell, p.tobuy";
$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
$sql.= ' WHERE p.entity IN ('.getEntity($product_static->element, 1).')';
$sql.= " GROUP BY p.fk_product_type, p.tosell, p.tobuy";
$result = $db->query($sql);
while ($objp = $db->fetch_object($result))
{
	$status=1;
	if (! $objp->tosell && ! $objp->tobuy) $status=0;	// To sell OR to buy
	$prodser[$objp->fk_product_type][$status]+=$objp->total;
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").'</td></tr>';
if (! empty($conf->product->enabled))
{
	$statProducts = "<tr $bc[0]>";
	$statProducts.= '<td><a href="liste.php?type=0&amp;tosell=0&amp;tobuy=0">'.$langs->trans("ProductsNotOnSell").'</a></td><td align="right">'.round($prodser[0][0]).'</td>';
	$statProducts.= "</tr>";
	$statProducts.= "<tr $bc[1]>";
	$statProducts.= '<td><a href="liste.php?type=0&amp;tosell=1">'.$langs->trans("ProductsOnSell").'</a></td><td align="right">'.round($prodser[0][1]).'</td>';
	$statProducts.= "</tr>";
}
if (! empty($conf->service->enabled))
{
	$statServices = "<tr $bc[0]>";
	$statServices.= '<td><a href="liste.php?type=1&amp;tosell=0&amp;tobuy=0">'.$langs->trans("ServicesNotOnSell").'</a></td><td align="right">'.round($prodser[1][0]).'</td>';
	$statServices.= "</tr>";
	$statServices.= "<tr $bc[1]>";
	$statServices.= '<td><a href="liste.php?type=1&amp;tosell=1">'.$langs->trans("ServicesOnSell").'</a></td><td align="right">'.round($prodser[1][1]).'</td>';
	$statServices.= "</tr>";
}
$total=0;
if ($type == '0')
{
	print $statProducts;
	$total=round($prodser[0][0])+round($prodser[0][1]);
}
else if ($type == '1')
{
	print $statServices;
	$total=round($prodser[1][0])+round($prodser[1][1]);
}
else
{
	print $statProducts.$statServices;
	$total=round($prodser[1][0])+round($prodser[1][1])+round($prodser[0][0])+round($prodser[0][1]);
}
print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">';
print $total;
print '</td></tr>';
print '</table>';


//print '</td><td valign="top" width="70%" class="notopnoleftnoright">';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Last modified products
 */
$max=15;
$sql = "SELECT p.rowid, p.label, p.price, p.ref, p.fk_product_type, p.tosell, p.tobuy,";
$sql.= " p.tms as datem";
$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
$sql.= " WHERE p.entity IN (".getEntity($product_static->element, 1).")";
if ($type != '') $sql.= " AND p.fk_product_type = ".$type;
$sql.= $db->order("p.tms","DESC");
$sql.= $db->plimit($max,0);

//print $sql;
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$i = 0;

	if ($num > 0)
	{
		$transRecordedType = $langs->trans("LastModifiedProductsAndServices",$max);
		if (isset($_GET["type"]) && $_GET["type"] == 0) $transRecordedType = $langs->trans("LastRecordedProducts",$max);
		if (isset($_GET["type"]) && $_GET["type"] == 1) $transRecordedType = $langs->trans("LastRecordedServices",$max);

		print '<table class="noborder" width="100%">';

		$colnb=5;
		if (empty($conf->global->PRODUIT_MULTIPRICES)) $colnb++;

		print '<tr class="liste_titre"><td colspan="'.$colnb.'">'.$transRecordedType.'</td></tr>';

		$var=True;

		while ($i < $num)
		{
			$objp = $db->fetch_object($result);

			//Multilangs
			if (! empty($conf->global->MAIN_MULTILANGS))
			{
				$sql = "SELECT label";
				$sql.= " FROM ".MAIN_DB_PREFIX."product_lang";
				$sql.= " WHERE fk_product=".$objp->rowid;
				$sql.= " AND lang='". $langs->getDefaultLang() ."'";

				$resultd = $db->query($sql);
				if ($resultd)
				{
					$objtp = $db->fetch_object($resultd);
					if ($objtp && $objtp->label != '') $objp->label = $objtp->label;
				}
			}

			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td class="nowrap">';
			$product_static->id=$objp->rowid;
			$product_static->ref=$objp->ref;
			$product_static->type=$objp->fk_product_type;
			print $product_static->getNomUrl(1,'',16);
			print "</td>\n";
			print '<td>'.dol_trunc($objp->label,32).'</td>';
			print "<td>";
			print dol_print_date($db->jdate($objp->datem),'day');
			print "</td>";
			// Sell price
			if (empty($conf->global->PRODUIT_MULTIPRICES))
			{
				print '<td align="right">';
    			if ($objp->price_base_type == 'TTC') print price($objp->price_ttc).' '.$langs->trans("TTC");
    			else print price($objp->price).' '.$langs->trans("HT");
    			print '</td>';
			}
			print '<td align="right" class="nowrap">';
			print $product_static->LibStatut($objp->tosell,5,0);
			print "</td>";
            print '<td align="right" class="nowrap">';
            print $product_static->LibStatut($objp->tobuy,5,1);
            print "</td>";
			print "</tr>\n";
			$i++;
		}

		$db->free();

		print "</table>";
	}
}
else
{
	dol_print_error($db);
}


// TODO Move this into a page that should be available into menu "accountancy - report - turnover - per quarter"
// Also method used for counting must provide the 2 possible methods like done by all other reports into menu "accountancy - report - turnover": 
// "commitment engagment" method and "cash accounting" method
if ($conf->global->MAIN_FEATURES_LEVEL)
{
	if (! empty($conf->product->enabled)) activitytrim(0);
	if (! empty($conf->service->enabled)) activitytrim(1);
}


//print '</td></tr></table>';
print '</div></div></div>';

llxFooter();

$db->close();




function activitytrim($product_type)
{
	global $conf,$langs,$db;
	
	// We display the last 3 years 
	$yearofbegindate=date('Y',dol_time_plus_duree(time(), -3, "y"));

	// breakdown by quarter
	$sql = "SELECT DATE_FORMAT(p.datep,'%Y') as annee, DATE_FORMAT(p.datep,'%m') as mois, SUM(fd.total_ht) as Mnttot";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."facturedet as fd";
	$sql.= " , ".MAIN_DB_PREFIX."paiement as p,".MAIN_DB_PREFIX."paiement_facture as pf";
	$sql.= " WHERE f.fk_soc = s.rowid";
	$sql.= " AND f.rowid = fd.fk_facture";
	$sql.= " AND pf.fk_facture = f.rowid";
	$sql.= " AND pf.fk_paiement= p.rowid";
	$sql.= " AND fd.product_type=".$product_type;
	$sql.= " AND s.entity = ".$conf->entity;
	$sql.= " AND p.datep >= '".$db->idate(dol_get_first_day($yearofbegindate),1)."'";
	$sql.= " GROUP BY annee, mois ";
	$sql.= " ORDER BY annee, mois ";

	$result = $db->query($sql);
	if ($result)
	{
		$tmpyear=$beginyear;
		$trim1=0;
		$trim2=0;
		$trim3=0;
		$trim4=0;
		$lgn = 0;
		$num = $db->num_rows($result);
		
		if ($num > 0 )
		{
			print '<br>';
			print '<table class="noborder" width="75%">';

			if ($product_type==0)
				print '<tr class="liste_titre"><td  align=left>'.$langs->trans("ProductSellByQuarterHT").'</td>';
			else
				print '<tr class="liste_titre"><td  align=left>'.$langs->trans("ServiceSellByQuarterHT").'</td>';
			print '<td align=right>'.$langs->trans("Quarter1").'</td>';
			print '<td align=right>'.$langs->trans("Quarter2").'</td>';
			print '<td align=right>'.$langs->trans("Quarter3").'</td>';
			print '<td align=right>'.$langs->trans("Quarter4").'</td>';
			print '<td align=right>'.$langs->trans("Total").'</td>';
			print '</tr>';
		}
		$i = 0;

		while ($i < $num)
		{
			$objp = $db->fetch_object($result);
			if ($tmpyear != $objp->annee)
			{
				if ($trim1+$trim2+$trim3+$trim4 > 0)
				{
					print '<tr ><td align=left>'.$tmpyear.'</td>';
					print '<td align=right>'.price($trim1).'</td>';
					print '<td align=right>'.price($trim2).'</td>';
					print '<td align=right>'.price($trim3).'</td>';
					print '<td align=right>'.price($trim4).'</td>';
					print '<td align=right>'.price($trim1+$trim2+$trim3+$trim4).'</td>';
					print '</tr>';
					$lgn++;
				}
				// We go to the following year
				$tmpyear = $objp->annee;
				$trim1=0;
				$trim2=0;
				$trim3=0;
				$trim4=0;
			}
			
			if ($objp->mois == "01" || $objp->mois == "02" || $objp->mois == "03")
				$trim1 += $objp->Mnttot;

			if ($objp->mois == "04" || $objp->mois == "05" || $objp->mois == "06")
				$trim2 += $objp->Mnttot;

			if ($objp->mois == "07" || $objp->mois == "08" || $objp->mois == "09")
				$trim3 += $objp->Mnttot;

			if ($objp->mois == "10" || $objp->mois == "11" || $objp->mois == "12")
				$trim4 += $objp->Mnttot;

			$i++;
		}
		if ($trim1+$trim2+$trim3+$trim4 > 0)
		{
			print '<tr ><td align=left>'.$tmpyear.'</td>';
			print '<td align=right>'.price($trim1).'</td>';
			print '<td align=right>'.price($trim2).'</td>';
			print '<td align=right>'.price($trim3).'</td>';
			print '<td align=right>'.price($trim4).'</td>';
			print '<td align=right>'.price($trim1+$trim2+$trim3+$trim4).'</td>';
			print '</tr>';
		}
		if ($num > 0 )
			print '</table>';
	}
}

?>
