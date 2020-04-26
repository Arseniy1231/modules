<?php

include 'db.php';

function getRegions()
{
	global $db;
	$query = "SELECT ter_id , ter_name  FROM t_koatuu_tree WHERE (ter_level = 1) AND ( reg_id <> 85 ) AND ( reg_id <> 80 )";
	$res = mysqli_query($db, $query);

	return mysqli_fetch_all($res, MYSQLI_ASSOC);
}

function getCities()
{
	global $db;
	$ter_id = mysqli_real_escape_string($db, $_POST['ter_id']);
	$query = "SELECT ter_id ,ter_name FROM t_koatuu_tree WHERE (ter_pid = '$ter_id') AND (ter_level = 2) AND (ter_type_id = 1)";
	$res = mysqli_query($db, $query);
	$data = '<option disabled selected>Выберите город</option>';
	if ($ter_id == 3200000000) {
		$data .=  '<option value="8000000000">м.Київ</option>';
	} else
    if ($ter_id == 0100000000) {
		$data .= '<option value="8500000000">м.Севастополь</option>';
	}
	while ($row = mysqli_fetch_assoc($res)) {
		$data .= "<option value='{$row['ter_id']}'>{$row['ter_name']}</option>";
	}

	return $data;
}
function getDistrict()
{
	global $db;
	$ter_id = mysqli_real_escape_string($db, $_POST['ter_id_second']);

	$query = "SELECT ter_id , ter_name FROM t_koatuu_tree WHERE ter_pid = ('$ter_id') AND (ter_level > 1) ";
	$res = mysqli_query($db, $query);
	$data = '<option disabled selected>Выберите район</option>';
	while ($row = mysqli_fetch_assoc($res)) {
		$data .= "<option value='{$row['ter_id']}'>{$row['ter_name']}</option>";
	}

	if ($data == '<option disabled selected>Выберите район</option>') {
		$data .=   '<option selected value="">В вашем городе нету районов(</option>';
	}



	return $data;
}
if (!empty($_POST['ter_id'])) {
	echo getCities();
	exit;
}
if (!empty($_POST['ter_id_second'])) {
	echo getDistrict();
	exit;
}

$regions = getRegions();

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Регестрация</title>
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="style.css">

	<link href="chosen/chosen.css" type="text/css" rel="stylesheet" />

</head>

<body>

	<div class="container content">
		<form class="form-horizontal" action="send.php" method="post" id="form">
			<div class="form-group">

				<div class="col-sm-6">
					<input type="text" name="name" placeholder="ФИО" required>
					
				</div>
			</div>
			<div class="form-group">

				<div class="col-sm-6">
					<input class="email" type="text" name="email" placeholder="E-mail" required>
					<span class="error"></span>
				</div>
			</div>
			<div class="form-group">
				<label for="name" class="col-sm-2 control-label">Список областей</label>
				<div class="col-sm-6">
					<select class="form-control" name="region" id="region" required>
						<option disabled selected>Выберите область</option>
						<?php foreach ($regions as $region) : ?>
							<option value="<?= $region['ter_id'] ?>"><?= $region['ter_name'] ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="form-group city-select display-none">
				<label for="name" class="col-sm-2 control-label">Список городов</label>
				<div class="col-sm-6">
					<select class="form-control" name="city" id="city" required>

					</select>
				</div>
			</div>
			<div class="form-group district-select display-none">
				<label for="name" class="col-sm-2 control-label">Список районов</label>
				<div class="col-sm-6">
					<select class="form-control" name="district" id="district" required>
					</select>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-6">
					<button type="submit" id="submit" class="btn btn-primary">Отправить</button>
					<div></div>
				</div>
			</div>
		</form>
	</div>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="chosen/chosen.jquery.js"></script>

	<script>
		$(function() {

			$('#region').chosen().change(function() {

				var ter_id = $(this).val();

				$('#city').load('index.php', {
					ter_id: ter_id
				}, function() {

					$('.city-select').fadeIn('slow');
					$('.district-select').fadeOut('slow');
					$('.form-control').trigger('chosen:updated');
					$('#form').validate({
						ignore: ":hidden:not(select)"
					});
					$('#submit').click(function() {
						if ($('#form').valid())
							alert('Valid');
						else
							alert('Поле не заполненно');
					});
				});
			});

		});
	</script>
	<script>
		$(function() {

			$('#city').chosen().change(function() {
				var ter_id_second = $(this).val();
				$('#district').load('index.php', {
					ter_id_second: ter_id_second
				}, function() {
					$('.district-select').fadeIn('slow');
					$('.form-control').trigger('chosen:updated');
					$('#form').validate({
						ignore: ":hidden:not(select)"
					});
					$('#submit').click(function() {
						if ($('#form').valid())
							alert('Valid');
						else
							alert('Поле не заполненно');
					});

				});

			});
		});
	</script>

	<script type="text/javascript">
		$('#district').chosen();
	</script>
	<script type="text/javascript">
$(function() {
  $("#submit").on("click", validate);

  // Validate email
  function validateEmail(email) {
    var re = /[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/;
    return re.test(String(email).toLowerCase());
  }
  
  // send form
//   function sendForm() {
//     $(".error").text("Form sending").fadeIn();
//   }

  // validate email and send form after success validation
  function validate() {
    var email = $(".email").val();
    var $error = $(".error");
    $error.text("");

    if (validateEmail(email)) {
      $error.fadeOut();
      sendForm();
    } else {
      $error.fadeIn();
      $error.text(email + " is not valid");
    }
    return false;
  }
});	</script>
</body>

</html>