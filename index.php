<?php
$liveStockLeft = ['p1','p2','p3','d1','d2','d3'];
$liveStockRight = array_fill(0, 6, 'e');
$pictures = ['p'=>'priest.png', 'd'=>'devil.png', 'e'=>'empty.png', 'g'=>'grave.png', 'a'=>'angel.png'];
$arrow = 'toRight';
$currentDirLeft = true;
$currentDirRight = false;
$grave = false;
$endGame = false;

if (!empty($_GET["reset"]) && $_GET["reset"] == 1) {
	
	if (ini_get("session.use_cookies")) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000,
			$params["path"],
			$params["domain"],
			$params["secure"],
			$params["httponly"]
		);
	}
}

// zmienia kierunek poruszania: z lewego brzegu na prawy i odwrotnie
if ((!empty($_POST['current_dir_left']) && $_POST['current_dir_left'] == 1) && $currentDirLeft == true) {
	$currentDirLeft = false;
	$currentDirRight = true;
	$arrow = 'toLeft';
} elseif ((!empty($_POST['current_dir_right']) && $_POST['current_dir_right'] == 1) && $currentDirRight == true) {
	$currentDirRight = false;
	$currentDirLeft = true;
	$arrow = 'toRight';
}

if (!empty($_POST["action"])) {
	$liveStock = prepareLiveStock($liveStockLeft, $liveStockRight);
	
	if (checkConflict($liveStock)) {
		// pokaż obrazek grave dla priests i zakończ grę
		$grave = true;
		$liveStockLeft = saveCurrentState($liveStock["leftWing"], $grave, null);
		$liveStockRight = saveCurrentState($liveStock["rightWing"], $grave, null);
	}
	$liveStockLeft = saveCurrentState($liveStock["leftWing"], null);
	$liveStockRight = saveCurrentState($liveStock["rightWing"], null);
	if (strlen(implode($liveStock["leftWing"])) == 6) {
		$endGame = true;
		$liveStockLeft = saveEndState($liveStock["leftWing"]);
		$liveStockRight = saveEndState($liveStock["rightWing"]);
	}
}

function prepareLiveStock($liveStockLeft, $liveStockRight) {
	// usuwa z jednego skrzydła elementy i przesuwa do drugiego
	session_start();
	if (empty($_SESSION)) {
		$addEmptyLeft = array_fill_keys(array_flip($_POST['liveStockLeft']), 'e');
		$_SESSION['leftWing'] = array_replace(array_diff_assoc($liveStockLeft, $_POST['liveStockLeft']), $addEmptyLeft);
		$_SESSION['rightWing'] = array_replace($liveStockRight, $_POST['liveStockLeft']);
		ksort($_SESSION['leftWing']);
	} else {
		if (!empty($_POST['liveStockLeft'])) {
			$addEmptyLeft = array_fill_keys(array_flip($_POST['liveStockLeft']), 'e');
			$_SESSION['leftWing'] = array_replace(array_diff_assoc($_SESSION['leftWing'], $_POST['liveStockLeft']), $addEmptyLeft);
			$_SESSION['rightWing'] = array_replace($_SESSION['rightWing'], $_POST['liveStockLeft']);
			ksort($_SESSION['leftWing']);
		} elseif (!empty($_POST['liveStockRight'])) {
			$addEmptyRight = array_fill_keys(array_flip($_POST['liveStockRight']), 'e');
			$_SESSION['rightWing'] = array_replace(array_diff_assoc($_SESSION['rightWing'], $_POST['liveStockRight']), $addEmptyRight);
			$_SESSION['leftWing'] = array_replace($_SESSION['leftWing'], $_POST['liveStockRight']);
			ksort($_SESSION['rightWing']);
		}
	}
	return $_SESSION;
}

function checkConflict($liveStock) {
	//jeśli po lewej albo prawej stronie jest więcej diabłów, konflikt
	$countLeft = countingDevils($liveStock['leftWing']);
	$countRight = countingDevils($liveStock['rightWing']);
	if (($countLeft['d']>$countLeft['p'] && $countLeft['p']>0) || ($countRight['d']>$countRight['p'] && $countRight['p']>0)) {
		return true;
	} else {
		return false;
	}
	return 0;
}

function countingDevils($liveStock) {
	$countDevils = 0;
	$countPriests = 0;
	foreach ($liveStock as $k=>$v) {
		if (substr($v, 0, 1) === 'd') {
			$countDevils += 1;
		} elseif (substr($v, 0, 1) === 'p') {
			$countPriests += 1;
		}
	}
	return ['p'=>$countPriests, 'd'=>$countDevils];
}

function saveCurrentState($liveStock, $grave=null) {
	if (empty($grave)) {
		foreach ($liveStock as $k=>$v) {
			if (substr($v, 0, 1) === 'p') {
				$countWing = countingDevils($liveStock);
				if ($countWing['d']>$countWing['p']) {
					$liveStock[$k] = 'g';
				}
			}
		}
		return $liveStock;
	} else {
			return $liveStock;
	}
	return 0;
}

function saveEndState($liveStock) {
	foreach ($liveStock as $k=>$v) {
		if (substr($v, 0, 1) === 'd') {
			$liveStock[$k] = 'a';
		}
	}
	return $liveStock;
}

function drawBoard($liveStockLeft, $liveStockRight, $pictures, $grave, $endGame, $currentDirLeft, $currentDirRight, $arrow) { ?>
	<div class="caption">
		<h1>There are 3 devils and 3 Priests.</h1>
		<p>They all have to cross a river in a boat. Boat can only carry two people at a time.As long as there are equal number of devils and priests, then devils will not eat Priest. If the number of devils are greater than the number of priests on the same side of the river then devils will eat the priests. So how can we make all the 6 peoples to arrive to the other side safely?</p>
		<div class="left-side">
			<h3>Transfer of people from one country</h3>
		</div>
		<div class="right-side">
			<h3>to another.</h3>
		</div>
	</div>
	<form action="index.php" method="post" name="boardAction">
		<table class="drawBoard">
			<tbody>
				<tr>
					<?php foreach ($liveStockLeft as $k=>$v) { ?>
						<td>
							<input type="hidden" value="<?php echo $currentDirLeft; ?>" name="current_dir_left">
							<div class="picture"><img src="<?php echo $pictures[substr($v, 0, 1)]; ?>"></div>
							<div id="leftWing_<?php echo $k; ?>" class="box"><input type="checkbox" value="<?php echo $v; ?>" name="liveStockLeft[<?php echo $k; ?>]" class="<?php if ($arrow === 'toLeft' || ($arrow === 'toRight' && ($v === 'e' || $v === 'g'))) { echo 'to-left-off'; } ?>"></div>
						</td>
					<?php } ?>
						<td>
							<input type="submit" name="action" value="<?php if ($arrow === 'toLeft') { echo '←'; } elseif ($arrow === 'toRight') { echo '→'; } ?>" <?php echo ($grave || $endGame) ? 'disabled' : null; ?>>
						</td>
					<?php foreach ($liveStockRight as $k=>$v) { ?>
						<td>
							<input type="hidden" value="<?php echo $currentDirRight; ?>" name="current_dir_right">
							<div class="picture"><img src="<?php echo $pictures[substr($v, 0, 1)]; ?>"></div>
							<div id="rightWing_<?php echo $k; ?>" class="box"><input type="checkbox" value="<?php echo $v; ?>" name="liveStockRight[<?php echo $k; ?>]" class="<?php if ($arrow === 'toRight' || ($arrow === 'toLeft' && ($v === 'e' || $v === 'g'))) { echo 'to-right-off'; } ?>"></div>
						</td>
					<?php } ?>
				</tr>
			</tbody>
		</table>
		<div class="reset-game">
			<a href="?reset=1">Let's play again</a>
		</div>
	</form><?php
}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>3 priests and 3 devils</title>
		<style type="text/css">
			.box, .picture { margin: 0 10px; }
			.box { text-align: center; }
			.drawBoard {
				width: 100%;
			}
			.to-left-off {
				display: none;
			}
			.to-right-off {
				display: none;
			}
			.caption {
				width: 100%;
				float: left;
			}
			.left-side {
				width: 50%;
				float: left;
			}
			.right-side {
				width: 50%;
				float: left;
			}
		</style>
		<script src="node_modules/jquery/dist/jquery.min.js"></script>
	</head>
	<body>
		<?php drawBoard($liveStockLeft, $liveStockRight, $pictures, $grave, $endGame, $currentDirLeft, $currentDirRight, $arrow); ?>
		<script type="text/javascript">
			$(document).ready(function(){
				$( "div[id^='<?php if ($currentDirLeft) { echo "leftWing_"; } elseif ($currentDirRight) { echo "rightWing_"; } ?>']" ).click(function(){
					var count = $( "input:checked" ).length, max = 2;
					
					if (count > max) {
						alert('Please select only ' + max + ' checkboxes.');
						$( "div[id^='" + this.id + "'] input" ).prop('checked', false);
					}
				});
				$("form").submit(function(){
					if ($( "input:checked" ).length === 0) {
						alert('Please select at least 1 checkbox.');
						return false;
					}
				});
			});
		</script>
	</body>
</html>