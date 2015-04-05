<!DOCTYPE html>
<html lang="ru">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="language" content="ru" />
	<script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
	<script src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
	<script src="/assets/jquery-ui-1.11.4.custom/jquery-ui.min.js"></script>
	<script src="/assets/jquery-ui-1.11.4.custom/i18n/datepicker-ru.js"></script>
	<script src="/assets/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>
	<script src="/assets/d3/d3.min.js"></script>

	<link rel="stylesheet" href="/assets/bootstrap-3.3.4-dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="/assets/jquery-ui-1.11.4.custom/jquery-ui.min.css">
	<link rel="stylesheet" href="/assets/work.css">

	<title><?php echo "{$title}"; ?></title>
</head>
<?php

?>
<body>

<div class="container">

	<div class="row">
	  <div class="col-md-8">
	  	<?php
		  	echo renderTemplate(
				'control',
				array(
					'starttime' => $starttime,
					'finishtime' => $finishtime,
				),
				true
			);
	  	?>
	  </div>
	  <div class="col-md-4">
	  	<h2>&nbsp;</h2>
	  	<div class="row" id="infoarea" style="font-size: 14px; height: 34px; line-height: 1.42857; padding: 6px 12px; font-weight: bold;">
		</div>
	  	<!-- info data -->
	  </div>
	</div>
	<div class="row">
	  <div class="col-md-12" id="plotdataarea">
		<?php echo "{$content}"; ?>
	  </div>
	</div>
	<div class="row">
	  <div class="col-md-12" id="tabledataarea">
	  	<!-- table data -->
	  </div>
	</div>

</div>
</body>
</html> 