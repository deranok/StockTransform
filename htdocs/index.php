<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       StockTransform/htdocs/index.php
 *	\ingroup    stock
 *	\brief      index page of stocktransform plugin
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

// Load translation files required by the page
//~ $langs->loadLangs(array("mymodule@mymodule"));

$action = GETPOST('action', 'aZ09');

$now = dol_now();
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);

// Security check - Protection if external user
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//if (!isModEnabled('mymodule')) {
//	accessforbidden('Module not enabled');
//}
//if (! $user->hasRight('mymodule', 'myobject', 'read')) {
//	accessforbidden();
//}
//restrictedArea($user, 'mymodule', 0, 'mymodule_myobject', 'myobject', '', 'rowid');
//if (empty($user->admin)) {
//	accessforbidden('Must be admin');
//}

//~ var_dump($_SERVER); exit();
/*
 * Actions
 */
//init data
$listofdata = array();
//~ $_SESSION['stocktransform'] = [];
if (!empty($_SESSION['stocktransform'])) {
	$listofdata = json_decode($_SESSION['stocktransform'], true);
}

$productstatic = new Product($db);
$warehousestatics = new Entrepot($db);

if($action == 'addline') {
  $s_or_t = GETPOST('s_or_t', 'aZ');
  if($s_or_t == 's') {
    $id_sw = GETPOSTINT('id_sw');
    $id_product = GETPOSTINT('productid');
    $qty = GETPOST('qty');
  }
  
  if($s_or_t == 't') {
    $id_sw = GETPOSTINT('id_tw');
    $id_product = GETPOSTINT('productidt');
    $qty = GETPOST('qty');
  }
  
  
  $error = 0;
  if (!($id_sw > 0)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WarehouseSource")), null, 'errors');
	}
	
	if (!($id_product > 0)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Product")), null, 'errors');
	}
	if (!$qty) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Qty")), null, 'errors');
	}
  
  if(!(($s_or_t == 's') || ($s_or_t == 't'))) {
    $error++;
  }
  
  if(!$error) {
    $listofdata[$s_or_t][] = ['id_sw' => $id_sw, 'id_product' => $id_product, 'qty' => $qty];
  }
}

if ($action == 'delline') {
  $idline = (int) GETPOST('idline', 'int');
  $s_or_t = GETPOST('s_or_t', 'aZ');
	if (
    array_key_exists($s_or_t, $listofdata)
  ) {
		unset($listofdata[$s_or_t][$idline]);
	}
	if (count($listofdata) > 0) {
		$_SESSION['stocktransform'] = json_encode($listofdata);
	} else {
		unset($_SESSION['stocktransform']);
	}
}

if ($action == 'createmovements' && $user->hasRight('stock', 'mouvement', 'creer')) {
	$error = 0;

	if (!GETPOST("label")) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("MovementLabel")), null, 'errors');
	}

	$db->begin();

	if (!$error) {
		$product = new Product($db);

		foreach ($listofdata as $mvmt_direction => $l) {	// Loop on each movement to do
			if($error) { break; }
      foreach($l as $val) {
        $id_product = $val['id_product'];
        $id_sw = $val['id_sw'];
        $qty = price2num($val['qty']);
        $dlc = -1; // They are loaded later from serial
        $dluo = -1; // They are loaded later from serial
        $add_or_sub = null;
        if($mvmt_direction == 's') {$add_or_sub = 1;}
        if($mvmt_direction == 't') {$add_or_sub = 0;}
        
        if(
          !is_numeric($qty) || !is_numeric($id_product) || !$id_product ||
          !is_numeric($id_sw) || !$id_sw || is_null($add_or_sub)
        ) {
          setEventMessages($langs->trans("ErrorFieldFormat", "qty, id_product, id_sw"), null, 'errors');
          ++$error; break;
        }
        
        $result = $product->fetch($id_product);
        
        if($result < 1) {
          setEventMessages($langs->trans("ErrorFieldFormat", "id_product, id_sw"), null, 'errors');
          ++$error; break;
        }

        $product->load_stock('novirtual'); // Load array product->stock_warehouse

        // Define value of products moved
        $pricesrc = 0;
        if (!empty($product->pmp)) {
          $pricesrc = $product->pmp;
        }
        $pricedest = $pricesrc;

        //print 'price src='.$pricesrc.', price dest='.$pricedest;exit;
        if($product->hasbatch()) {
          setEventMessages('Product batches not supported', null, 'errors');
          ++$error; break;
        }
        
        if (true) {	// If product does not need lot/serial
          $result1 = $product->correct_stock(
            $user,
            $id_sw,
            $qty,
            $add_or_sub,
            GETPOST("label"),
            0,
            GETPOST("codemove")
          );
          if ($result1 < 0) {
            setEventMessages($product->error, $product->errors, 'errors');
            $error++; break;
          }
        } 
        
        if(false) { // TODO handle batches
          $arraybatchinfo = $product->loadBatchInfo($batch);
          if (count($arraybatchinfo) > 0) {
            $firstrecord = array_shift($arraybatchinfo);
            $dlc = $firstrecord['eatby'];
            $dluo = $firstrecord['sellby'];
            //var_dump($batch);
            //var_dump($arraybatchinfo);
            //var_dump($firstrecord);
            //var_dump($dlc);
            //var_dump($dluo); exit;
          } else {
            $dlc = '';
            $dluo = '';
          }

          // Remove stock
          if ($id_sw > 0) {
            $result1 = $product->correct_stock_batch(
              $user,
              $id_sw,
              $qty,
              1,
              GETPOST("label"),
              $pricesrc,
              $dlc,
              $dluo,
              $batch,
              GETPOST("codemove")
            );
            if ($result1 < 0) {
              $error++;
              setEventMessages($product->error, $product->errors, 'errors');
            }
          }

          // Add stock
          $result2 = $product->correct_stock_batch(
            $user,
            $id_tw,
            $qty,
            0,
            GETPOST("label"),
            $pricedest,
            $dlc,
            $dluo,
            $batch,
            GETPOST("codemove")
          );
          if ($result2 < 0) {
            $error++;
            setEventMessages($product->error, $product->errors, 'errors');
          }
        }
      }
    }
	}
	//var_dump($_SESSION['massstockmove']);exit;

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("StockMovementRecorded"), null, 'mesgs');
    $listofdata = [];
	} 
  
  if($error) {
		$db->rollback();
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

// save data
$_SESSION['stocktransform'] = json_encode($listofdata);


//Post-Redirect-Get
if($_SERVER['REQUEST_METHOD'] == 'POST') {
  header('Location: ' . $_SERVER['PHP_SELF'], true, 303);
}

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproduct = new FormProduct($db);
?>
<?= llxHeader("", 'Stock Transform', '', '', 0, 0, '', '', '', 'StockTransform page-index'); ?>
<?= load_fiche_titre('Stock Transform', '', 'fa-exchange-alt'); ?>

<!-- Form to add a source line -->
<form action="<?= $_SERVER["PHP_SELF"] ?>" method="POST" name="formulaire">
  <input type="hidden" name="token" value="<?= newToken(); ?>">
  <input type="hidden" name="action" value="addline">
  <input type="hidden" name="s_or_t" value="s">
  <div class="div-table-responsive-no-min">
    <table class="liste centpercent">

<?php $param = ''; ?>

      <tr class="liste_titre">
        <?= getTitleFieldOfList($langs->trans('WarehouseSource'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone '); ?>
        <?= getTitleFieldOfList($langs->trans('Product'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone '); ?>
        <?= getTitleFieldOfList($langs->trans('Qty'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right tagtd maxwidthonsmartphone '); ?>
        <?= getTitleFieldOfList('', 0); ?>
      </tr>

      <tr class="oddeven">
        <td class="nowraponall">
          <?= img_picto($langs->trans("WarehouseSource"), 'stock', 'class="paddingright"').$formproduct->selectWarehouses($id_sw, 'id_sw', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'minwidth200imp maxwidth200'); ?>
        </td>
        <td class="nowraponall">
          
<?php
  $filtertype = 0;
  if (getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
    $filtertype = '';
  }
  if (getDolGlobalInt('PRODUIT_LIMIT_SIZE') <= 0) {
    $limit = '';
  } else {
    $limit = getDolGlobalString('PRODUIT_LIMIT_SIZE');
  }
?>
          <?= img_picto($langs->trans("Product"), 'product', 'class="paddingright"'); ?>
          <?= $form->select_produits($id_product, 'productid', $filtertype, $limit, 0, -1, 2, '', 1, array(), 0, '1', 0, 'minwidth200imp maxwidth300', 1, '', null, 1); ?>
        </td>
        <td class="right"><input type="text" class="flat maxwidth50 right" name="qty" value="<?= price2num((float) $qty, 'MS') ?>"></td>

        <td class="right"><input type="submit" class="button" name="addsourceline" value="Add"></td>

      </tr>
      
      
      <?php foreach($listofdata['s'] as $idx => $val): ?>
      <?php
        $error = 0;
        $productstatic->id = 0;
        $productstatic->fetch($val['id_product']);
        $warehousestatics->id = 0;
        if ($val['id_sw'] > 0) {
          $warehousestatics->fetch($val['id_sw']);
        }
        if ($productstatic->id <= 0) {
          $error++;
          setEventMessages($langs->trans("ObjectNotFound", $langs->transnoentitiesnoconv("Product").' (id='.$val['id_product'].')'), null, 'errors');
        }
        if ($warehousestatics->id <= 0) {	// We wont accept 0 for source warehouse id
          $error++;
          setEventMessages($langs->trans("ObjectNotFound", $langs->transnoentitiesnoconv("WarehouseSource").' (id='.$val['id_sw'].')'), null, 'errors');
        }
        if($error) { continue; }
      ?>
      <tr class="oddeven">
        <td><?= $warehousestatics->getNomUrl(1); ?></td>
        <td><?= $productstatic->getNomUrl(1).' - '.dol_escape_htmltag($productstatic->label); ?></td>
        <td class="right"><?= price2num((float) $val['qty'], 'MS'); ?></td>
        <td class="right"><a href="<?= $_SERVER["PHP_SELF"].'?action=delline&token='.newToken().'&idline='.$idx.'&s_or_t=s' ?>"> <?= img_delete($langs->trans("Remove")) ?></a></td>
      </tr>
      <?php endforeach; ?>
    
    </table>
  </div>
</form>

<?php if(array_key_exists('s', $listofdata) && (count($listofdata['s']) > 0)): ?>
<!-- Form to add a destination line -->
<form action="<?= $_SERVER["PHP_SELF"] ?>" method="POST" name="formulaire2">
  <input type="hidden" name="token" value="<?= newToken(); ?>">
  <input type="hidden" name="action" value="addline">
  <input type="hidden" name="s_or_t" value="t">
  <div class="div-table-responsive-no-min">
    <table class="liste centpercent">

<?php $param = ''; ?>

      <tr class="liste_titre">
        <?= getTitleFieldOfList($langs->trans('WarehouseTarget'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone '); ?>
        <?= getTitleFieldOfList($langs->trans('Product'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone '); ?>
        <?= getTitleFieldOfList($langs->trans('Qty'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right tagtd maxwidthonsmartphone '); ?>
        <?= getTitleFieldOfList('', 0); ?>
      </tr>

      <tr class="oddeven">
        <td class="nowraponall">
          <?= img_picto($langs->trans("WarehouseSource"), 'stock', 'class="paddingright"').$formproduct->selectWarehouses($id_sw, 'id_tw', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'minwidth200imp maxwidth200'); ?>
        </td>
        <td class="nowraponall">
          
<?php
  $filtertype = 0;
  if (getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
    $filtertype = '';
  }
  if (getDolGlobalInt('PRODUIT_LIMIT_SIZE') <= 0) {
    $limit = '';
  } else {
    $limit = getDolGlobalString('PRODUIT_LIMIT_SIZE');
  }
?>
          <?= img_picto($langs->trans("Product"), 'product', 'class="paddingright"'); ?>
          <?= $form->select_produits($id_product, 'productidt', $filtertype, $limit, 0, -1, 2, '', 1, array(), 0, '1', 0, 'minwidth200imp maxwidth300', 1, '', null, 1); ?>
        </td>
        <td class="right"><input type="text" class="flat maxwidth50 right" name="qty" value="<?= price2num((float) $qty, 'MS') ?>"></td>

        <td class="right"><input type="submit" class="button" name="addsourceline" value="Add"></td>

      </tr>
      
      
      <?php foreach($listofdata['t'] as $idx => $val): ?>
      <?php
        $error = 0;
        $productstatic->id = 0;
        $productstatic->fetch($val['id_product']);
        $warehousestatics->id = 0;
        if ($val['id_sw'] > 0) {
          $warehousestatics->fetch($val['id_sw']);
        }
        if ($productstatic->id <= 0) {
          $error++;
          setEventMessages($langs->trans("ObjectNotFound", $langs->transnoentitiesnoconv("Product").' (id='.$val['id_product'].')'), null, 'errors');
        }
        if ($warehousestatics->id <= 0) {	// We wont accept 0 for warehouse id
          $error++;
          setEventMessages($langs->trans("ObjectNotFound", $langs->transnoentitiesnoconv("WarehouseSource").' (id='.$val['id_sw'].')'), null, 'errors');
        }
        if($error) { continue; }
      ?>
      <tr class="oddeven">
        <td><?= $warehousestatics->getNomUrl(1); ?></td>
        <td><?= $productstatic->getNomUrl(1).' - '.dol_escape_htmltag($productstatic->label); ?></td>
        <td class="right"><?= price2num((float) $val['qty'], 'MS'); ?></td>
        <td class="right"><a href="<?= $_SERVER["PHP_SELF"].'?action=delline&token='.newToken().'&idline='.$idx.'&s_or_t=t' ?>"> <?= img_delete($langs->trans("Remove")) ?></a></td>
      </tr>
      <?php endforeach; ?>
    
    </table>
  </div>
</form>
<?php endif; ?>


<!-- Confirmation -->
<?php if(array_key_exists('t', $listofdata) && (count($listofdata['t']) > 0)): ?>
<form action="<?= $_SERVER["PHP_SELF"]; ?>" method="POST" name="formulaire3" class="formconsumeproduce">
	<input type="hidden" name="token" value="<?= newToken(); ?>">
	<input type="hidden" name="action" value="createmovements">
  
  <?php
	// Button to record mass movement
	$codemove = (GETPOSTISSET("codemove") ? GETPOST("codemove", 'alpha') : dol_print_date(dol_now(), '%Y%m%d%H%M%S'));
	$labelmovement = GETPOST("label") ? GETPOST('label') : 'Stock transform on ' . dol_print_date($now, '%Y-%m-%d %H:%M');
  $buttonrecord = $langs->trans("RecordMovements");
  ?>
	<div class="center">
    <span class="fieldrequired"><?= $langs->trans("InventoryCode"); ?>:</span> 
    <input type="text" name="codemove" class="maxwidth300" value="<?= dol_escape_htmltag($codemove); ?>"> &nbsp; 
    <br>
    <span class="fieldrequired clearbothonsmartphone"><?= $langs->trans("MovementLabel") ?>: </span>
	  <input type="text" name="label" class="minwidth300" value="<?= dol_escape_htmltag($labelmovement); ?>">
    <br>
    <div class="center">
      <input type="submit" class="button" name="valid" value="<?= dol_escape_htmltag($buttonrecord); ?>">
    </div>

    <br>
  </div>

</form>

<?php endif; ?>


<?= llxFooter(); ?>
<?php $db->close() ?>
