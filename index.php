<?php
/*
 * При выполнении задания сделаны следующие допущения:
 * - не используем никаких php вспомогательных библиотек
 * - не производится никаких проверок получаемых значений
 * - не предполагается никакого дополнительного функционала, 
 *   поэтому не создаются никакие объекты для инкапсуляции данных и методов, 
 *   все даелаем в одном пространстве
 * - над таблицей данных проведен ряд операций, изложенных в sql файле
 *   и у поля type есть числовой эквивалент oper_id,
 *   нужные значения которого описаны в define
 * - никаких реакций на изменение размеров экрана и т.д.
 */

define('DB_CONNECT_CONFIG', 'dp.php'); // файл настроек базы

define('MIN_PLOT_INTERVAL', 3600 * 24);// минимальный интервал квантования результатов
define('MAX_PLOT_POINTS', 50);         // максимальное кол-во точек для графика
define('TYPE_OPERATION_ANSWER', 2);    // oper_id для поля значения type = ANSWER
$dbConnection = null;


$aOp = getArrayVal($_REQUEST, 'Control', array());
$sOp = trim(getArrayVal($aOp, 'plottype', ''));


switch ($sOp) {
	case '':
		try {
			$aDates = getTimeInterval();
			renderTemplate(
				'page',
				array(
					'title'=>'Обработка данных',
					'content'=>'',
					'starttime' => $aDates['starttime'],
					'finishtime' => $aDates['finishtime'],
				)
			);
		}
		catch (Exception $e) {
			echo "Error: " . $e->getMessage() . "\n";
		}
		break;
	
	default:
		try {
			$aData = getOperationData(
				$sOp,
				getArrayVal($aOp, 'starttime', '01.01.2000'),
				getArrayVal($aOp, 'finishtime', date('d.m.Y'))
			);
		}
		catch (Exception $e) {
			$aData = array(
				'error' => $e->getMessage(),
			);
		}
		renderJson($aData);
		break;
	
}

// *******************************************************************  вспомогательные функции

/**
 * Вывод шаблона
 * @param string $sTemplateName название шаблона в папке template
 * @param array $aData массив переменных для шаблона
 * @param boolean $bret возвращать результат работы шаблона или выводить на экран
 * @return string 
 */
function renderTemplate($sTemplateName, $aData = array(), $bret = false) {
	if( substr($sTemplateName, -4) != '.php' ) {
		$sTemplateName .= '.php';
	}
	$sf = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . $sTemplateName;
	if( $bret ) {
		ob_start();
		ob_implicit_flush(false);
	}
	if( file_exists($sf) ) {
		extract($aData);
		require($sf);
	}
	else {
		echo "Not found template {$sf}\n";
	}

	return ( $bret ) ? ob_get_clean() : '';
}

/**
 * Вывод json
 * @param array $aData массив данных
 */
function renderJson($aData = array()) {
	header('Content-Type: application/json');
	echo json_encode($aData);
}

/**
 * Получение элемента массива по его ключу
 * @param array $aData массив данных
 * @param string $sKey ключ
 * @param variant $vDefault значение по умолчанию, если в массиве нет ключа
 * @return variant 
 */
function getArrayVal($aData, $sKey, $vDefault) {
	return isset($aData[$sKey]) ? $aData[$sKey] : $vDefault;
}

/**
 * Получение timestamp по строке времени
 * @param string $sDate начальная дата
 * @return integer 
 */
function convertDate($sDate) {
	$t = 0;
	if( preg_match('|([\\d]{2}).([\\d]{2}).([\\d]{4})|', $sDate, $a) ) {
		$t = mktime(0, 0, 0, $a[2], $a[1], $a[3]);
	}
	return $t;
}


/**
 * Подключение к базе данных
 * @return PDO
 */
function connectDb() {
	Global $dbConnection;
	if( $dbConnection === null ) {
		$sf = dirname(__FILE__) . DIRECTORY_SEPARATOR . DB_CONNECT_CONFIG;
		if( !file_exists($sf) ) {
			throw new Exception('Error not found file with DB connection parameters: ' . $sf);
		}

		$a = require($sf);
		if( !isset($a['connect']) || !isset($a['user']) || !isset($a['password']) ) {
			throw new Exception('Error DB connection parameters. Config array must have fields: connect, user, password');
		}
		$dbConnection = new PDO($a['connect'], $a['user'], $a['password']);
	}
	return $dbConnection;
}

/**
 * Выполнение запроса
 * @param string $sql запрос
 * @param array $param параметры
 * @return 
 */
function query($sql, $param = array()) {
	$conn = connectDb();
	$st = $conn->prepare($sql);
	foreach($param As $k=>$v) {
		$stype = is_int($v) ? PDO::PARAM_INT : (is_string($v) ? PDO::PARAM_STR : PDO::PARAM_BOOL);
		$st->bindValue($k, $v, $stype);
	}
	$st->execute(); // $param
	return $st;
}

/**
 * Получение диапазон дат для данных в базе
 * @return array 
 */
function getTimeInterval() {
	$sql = 'Select MAX(moment) As finishtime, MIN(moment) As starttime From systemcall';
	$st = query($sql);
	$a = $st->fetch(PDO::FETCH_ASSOC);
	return array(
		'starttime' => strtotime($a['starttime']),
		'finishtime' => strtotime($a['finishtime']),
	);
}

/**
 * Получение данных по диапазону дат для заданной опреации
 * @param string $operate операция
 * @param string $startDate начальная дата
 * @param string $finishDate конечная дата
 * @return array 
 */
function getOperationData($operate, $startDate, $finishDate) {
	$d1 = convertDate($startDate);
	$d2 = convertDate($finishDate);
	$aData = array(
		'operate' => $operate,
		'd1' => $startDate . ' -> ' . date('Y-m-d', $d1),
		'd2' => $finishDate . ' -> ' . date('Y-m-d', $d2),
		'data' => array(),
	);
	$aData = array();
	switch ($operate) {
		case 'simpletimetalk':
			$aData = getTalkTime($d1, $d2 + 24 * 3600);
			break;

		case 'simplenumtalk':
			$aData = getTalkNum($d1, $d2 + 24 * 3600);
			break;

		case 'simpleweektime':
			$aData = getDaysTime($d1, $d2 + 24 * 3600);
			break;

		case 'simplenumbertime':
			$aData = getNumbesrTime($d1, $d2 + 24 * 3600);
			break;

		default:
			break;
	}
	return $aData;
}

/**
 * Получение временного интервала для квантования дат
 * @param integer $t1 начальная дата
 * @param integer $t2 конечная дата
 * @return integer
 */
function getPlotInterval($t1, $t2) {
	$dt = ceil($t2 - $t1) / MAX_PLOT_POINTS;
	if( $dt < MIN_PLOT_INTERVAL ) {
		$dt = MIN_PLOT_INTERVAL;
	}
	return $dt;
}
/**
 * Получение данных по диапазону дат и запросу
 * @param integer $t1 начальная дата
 * @param integer $t2 конечная дата
 * @param string $sql SQL запрос
 * @param array $param параметры запроса
 * @return array 
 */
function getPlotDataArray($t1, $t2, $sql, $param) {
	$tStart = date('Y-m-d H:i:s', $t1);
	$tFinish = date('Y-m-d H:i:s', $t2);
	$st = query(
		$sql,
		$param
	);
	
	$aData = array(
		'data' => array(),
		't1' => $tStart,
		't2' => $tFinish,
		'sql' => $sql,
	);

	while( $a = $st->fetch(PDO::FETCH_ASSOC) ) {
		$a['date'] = date('Y-m-d', strtotime($a['pointtime']));
		unset($a['pointtime']);
		$aData['data'][] = $a;
	}

	return $aData;
}

/**
 * Получение данных по диапазону дат для заданной опреации
 * @param integer $t1 начальная дата
 * @param integer $t2 конечная дата
 * @return array 
 */
function getTalkTime($t1, $t2) {
	$dt = getPlotInterval($t1, $t2);
//			FLOOR((UNIX_TIMESTAMP(moment) - UNIX_TIMESTAMP(:t01)) / :dt1) As npart,
	$sql = 'Select SUM(time) As val,
			FROM_UNIXTIME(UNIX_TIMESTAMP(:t02) + :dt2 * ROUND((UNIX_TIMESTAMP(moment) - UNIX_TIMESTAMP(:t03)) / :dt3)) As pointtime '
	     . 'From systemcall '
	     . 'Where moment >= :t1 And moment < :t2 And oper_id = :oper '
	     . 'Group By pointtime '
	     . 'Order by pointtime';

	$tStart = date('Y-m-d H:i:s', $t1);
	$tFinish = date('Y-m-d H:i:s', $t2);
	$aParam = array(
//			':t01' => $tStart,
			':t02' => $tStart,
			':t03' => $tStart,
//			':dt1' => $dt,
			':dt2' => $dt,
			':dt3' => $dt,
			':t1' => $tStart,
			':t2' => $tFinish,
			':oper' => TYPE_OPERATION_ANSWER,
	);

	$aData = getPlotDataArray($t1, $t2, $sql, $aParam);
	$aData = array_merge(
		$aData,
		array(
			'titles' => ['val' => 'Время разг.', 'yaxis' => 'Секунды'],
			'name' => 'getTalkTime',
			'dt' => $dt,
			'sql' => $sql,
		)
	);
	return $aData;
}

/**
 * Получение данных по диапазону дат для заданной опреации
 * @param integer $t1 начальная дата
 * @param integer $t2 конечная дата
 * @return array 
 */
function getTalkNum($t1, $t2) {
	$dt = getPlotInterval($t1, $t2);
	$sql = 'Select Count(*) As val, 
			FROM_UNIXTIME(UNIX_TIMESTAMP(:t02) + :dt2 * ROUND((UNIX_TIMESTAMP(moment) - UNIX_TIMESTAMP(:t03)) / :dt3)) As pointtime '
	     . 'From systemcall '
	     . 'Where moment >= :t1 And moment < :t2 And oper_id = :oper '
	     . 'Group By pointtime '
	     . 'Order by pointtime';
	$tStart = date('Y-m-d H:i:s', $t1);
	$tFinish = date('Y-m-d H:i:s', $t2);
	$aParam = array(
			':t02' => $tStart,
			':t03' => $tStart,
			':dt2' => $dt,
			':dt3' => $dt,
			':t1' => $tStart,
			':t2' => $tFinish,
			':oper' => TYPE_OPERATION_ANSWER,
	);

	
	$aData = getPlotDataArray($t1, $t2, $sql, $aParam);
	$aData = array_merge(
		$aData,
		array(
			'titles' => ['val' => 'Звонки', 'yaxis' => 'Кол-во'],
			'name' => 'getTalkNum',
			'dt' => $dt,
			'sql' => $sql,
		)
	);
	return $aData;
}

/**
 * Получение данных по дням недели
 * @param integer $t1 начальная дата
 * @param integer $t2 конечная дата
 * @return array 
 */
function getDaysTime($t1, $t2) {
	$dt = getPlotInterval($t1, $t2);
	$sql = 'Select 
			SUM(IF(DAYOFWEEK(moment) = 1, time, 0)) As sun,
			SUM(IF(DAYOFWEEK(moment) = 2, time, 0)) As mon,
			SUM(IF(DAYOFWEEK(moment) = 3, time, 0)) As tue,
			SUM(IF(DAYOFWEEK(moment) = 4, time, 0)) As wed,
			SUM(IF(DAYOFWEEK(moment) = 5, time, 0)) As thu,
			SUM(IF(DAYOFWEEK(moment) = 6, time, 0)) As fri,
			SUM(IF(DAYOFWEEK(moment) = 7, time, 0)) As sut,
			FROM_UNIXTIME(UNIX_TIMESTAMP(:t02) + :dt2 * ROUND((UNIX_TIMESTAMP(moment) - UNIX_TIMESTAMP(:t03)) / :dt3)) As pointtime '
	     . 'From systemcall '
	     . 'Where moment >= :t1 And moment < :t2 And oper_id = :oper '
	     . 'Group By pointtime '
	     . 'Order by pointtime';
	$tStart = date('Y-m-d H:i:s', $t1);
	$tFinish = date('Y-m-d H:i:s', $t2);
	$aParam = array(
			':t02' => $tStart,
			':t03' => $tStart,
			':dt2' => $dt,
			':dt3' => $dt,
			':t1' => $tStart,
			':t2' => $tFinish,
			':oper' => TYPE_OPERATION_ANSWER,
	);

	
	$aData = getPlotDataArray($t1, $t2, $sql, $aParam);
	$aData = array_merge(
		$aData,
		array(
			'titles' => array(
				'sun' => 'Воскрес.',
				'mon' => 'Понед.',
				'tue' => 'Вторник',
				'wed' => 'Среда',
				'thu' => 'Четверг',
				'fri' => 'Пятница',
				'sut' => 'Суббота',
				'yaxis' => 'Секунды'
			),
			'name' => 'getDaysTime',
			'dt' => $dt,
			'sql' => $sql,
		)
	);
	return $aData;
}

/**
 * Получение данных по входящим номерам
 * @param integer $t1 начальная дата
 * @param integer $t2 конечная дата
 * @return array 
 */
function getNumbesrTime($t1, $t2) {
	$sql = 'Select * From tel';
	$st = query($sql);
	$aFields = array();
	$aTitles = array();
	while( $a = $st->fetch(PDO::FETCH_ASSOC) ) {
		$sFields[] = 'SUM(IF(tel_id = '.$a['t_id'].', time, 0)) As val_' . $a['t_id'];
		$aTitles['val_' . $a['t_id']] = formatPhoneNum($a['t_num']);
	}
	$aTitles['yaxis'] = 'Секунды';

	$dt = getPlotInterval($t1, $t2);
	$sql = 'Select ' . implode(', ', $sFields) . ',
			FROM_UNIXTIME(UNIX_TIMESTAMP(:t02) + :dt2 * ROUND((UNIX_TIMESTAMP(moment) - UNIX_TIMESTAMP(:t03)) / :dt3)) As pointtime '
	     . 'From systemcall '
	     . 'Where moment >= :t1 And moment < :t2 And oper_id = :oper '
	     . 'Group By pointtime '
	     . 'Order by pointtime';
	$tStart = date('Y-m-d H:i:s', $t1);
	$tFinish = date('Y-m-d H:i:s', $t2);
	$aParam = array(
			':t02' => $tStart,
			':t03' => $tStart,
			':dt2' => $dt,
			':dt3' => $dt,
			':t1' => $tStart,
			':t2' => $tFinish,
			':oper' => TYPE_OPERATION_ANSWER,
	);

	
	$aData = getPlotDataArray($t1, $t2, $sql, $aParam);
	$aData = array_merge(
		$aData,
		array(
			'titles' => $aTitles,
			'name' => 'getDaysTime',
			'dt' => $dt,
			'sql' => $sql,
		)
	);
	return $aData;
}

/**
 * Форматирование телефона
 * @param string $tel
 * @return string 
 */
function formatPhoneNum($tel) {
	if( preg_match('|^\\d{10,}$|', $tel) ) {
		$tel = preg_replace('|^(\\d+)(\\d{3})(\\d{3})(\\d{4})$|', '${2} ${3}-${4}', $tel);
	}
	return $tel;
}
