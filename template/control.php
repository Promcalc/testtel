<form id="control-form" action="/" method="post">
<?php /* class="col-md-4 col-md-offset-4 form-signin"  */?>
<h2>Управление</h2>

<div class="row">
	<div class="col-md-4">
		<input class="form-control" placeholder="Начало интервала" name="Control[starttime]" id="Control_starttime" type="text" value="<?= date('d.m.Y', $starttime) ?>" />
	</div>
	<div class="col-md-4">
		<input class="form-control" placeholder="Окончание интервала" name="Control[finishtime]" id="Control_finishtime" type="text" value="<?= date('d.m.Y', $finishtime) ?>" />
	</div>
	<div class="col-md-4">
		<select class="form-control" placeholder="Тип графика" name="Control[plottype]" id="Control_plottype">
			<?php 
				$a = array(
					'simpletimetalk' => 'График времени разговоров',
					'simplenumtalk' => 'График количества звонков',
					'simpleweektime' => 'График времени разговоров по дням недели',
					'simplenumbertime' => 'График времени по входящим номерам',
				);
				foreach($a As $k=>$v) {
					echo "<option value=\"{$k}\">".htmlspecialchars($v)."</option>";
				}
			?>
		</select>
	</div>
</div>

<!-- div class="row">
<input class="form-control" placeholder="Логин" name="LoginForm[username]" id="LoginForm_username" type="text" />
<div class="alert alert-danger" id="LoginForm_username_em_" style="display:none"></div>
</div>

<div class="row">
<input class="form-control" placeholder="Пароль" name="LoginForm[password]" id="LoginForm_password" type="password" />
<div class="alert alert-danger" id="LoginForm_password_em_" style="display:none"></div>
</div>

<div class="row">
<input class="btn btn-lg btn-primary btn-block" type="submit" name="yt0" value="Войти" />
</div -->

</form>

<div class="clearfix"></div>
<script src="/assets/work.js"></script>