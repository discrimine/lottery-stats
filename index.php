<?php
include_once 'constants/grid_headers.php';

// db connection
$con = mysqli_connect('zechemod.mysql.tools', 'zechemod_db', 'sprntr!38976d','zechemod_db');
$con->set_charset("utf8");
// lottery data
$sql = mysqli_query($con, "SELECT * FROM `lottery` ORDER BY Date DESC LIMIT 25");
$lottery_data = [];
while ($sql_arr = mysqli_fetch_assoc($sql)) {
	$lottery_data[] = $sql_arr;
}

// is even function
function is_even($number) {
	return $number % 2 == 0 ? 1 : 0;
}

// custm sort: ['words', 0, 1, ..., n]
function sort_func($a, $b) {
	return ($a < $b || is_string($a)) ? -1 : 1;
}

// TODO: optimize auto calculate $count, calculate regularity function
function calculate_regularity($regularity, $el_count, $reverse_flag = false, $empty_auto_fill = true) {
	$global_count_even = 0;
	// kinds of statistics of even
	for ($i = 0; $i < $el_count; $i++) {
		$count = 0;
		// total count of this number default value
		$regularity_intervals[$i]['all'] = 0;
		// skip first regularity flag
		$skip_first_flag = true;
		// days
		for ($j = 0; $j <= count($regularity); $j++) {
			if ( $regularity[$j][$i] === ($reverse_flag ? 0 : 1)) {
				if ($skip_first_flag === false) {
					// new interval initialization 
					if (gettype($regularity_intervals[$i][$count]) == 'NULL') {
						$regularity_intervals[$i][$count] = 0;
					}
					// add +1 to the stats[kind of statistic][days interval]
					$regularity_intervals[$i][$count]++;
					// add +1 to the total count of this number
					$regularity_intervals[$i]['all']++;

					// max interval checking
					if ($count > $global_count_even) {
						$global_count_even = $count;
					}

					// reset count
					$count = 0;
				}
			
				// skip first regularity
				$skip_first_flag = false;
				$count = 0;
			} else {
				// increment count interval
				$count++;
			}
		}
	}

	if ($empty_auto_fill) {
		// sort + fill empty regularity values
		for ($i = 0; $i < count($regularity_intervals); $i++) {
			for ($j = 0; $j <= $global_count_even; $j++) {
				if (gettype($regularity_intervals[$i][$j]) == 'NULL') {
					$regularity_intervals[$i][$j] = 0;
				}
			}
			uksort($regularity_intervals[$i], "sort_func");
		}
	}

	return empty($regularity_intervals) ? [] : $regularity_intervals;
}

/** fourth table, even regularity
	* even_regularity table init
	* daily_stats table init
	* 2 same balls in 1 day
 */
$even_regularity = [];
$daily_stats = [];
$daily_stats_per_balls = [
	'fn' => [
		'balls' => [],
		'regularity' => [],
	],
	'sn' => [
		'balls' => [],
		'regularity' => [],
	],
	'tn' => [
		'balls' => [],
		'regularity' => [],
	],
	'info' => [],
];

foreach ($lottery_data as $index => $day) {
	
	$daily_stats[$index]['number'] = $lottery_data[$index]['number'];
	$daily_stats[$index]['date'] = $lottery_data[$index]['date'];
	$daily_stats_per_balls['info'][$index]['number'] = $lottery_data[$index]['number'];
	$daily_stats_per_balls['info'][$index]['date'] = $lottery_data[$index]['date'];
	for ($i = 0; $i < 10; $i++) {
		$tmp_numbers_array = [$day['fn'], $day['sn'], $day['tn']];
		$daily_stats[$index][] = (in_array($i, $tmp_numbers_array)) ? 1 : 0;

		$daily_stats_per_balls['fn']['balls'][$index][] = $i == $day['fn'] ? 1 : 0;
		$daily_stats_per_balls['sn']['balls'][$index][] = $i == $day['sn'] ? 1 : 0;
		$daily_stats_per_balls['tn']['balls'][$index][] = $i == $day['tn'] ? 1 : 0;
	
		$even_regularity[$index]['number'] = $day['number'];
		$even_regularity[$index]['date'] = $day['date'];
		$even_regularity[$index][0] = is_even($day['fn']);
		$even_regularity[$index][1] = is_even($day['sn']);
		$even_regularity[$index][2] = is_even($day['tn']);
		$even_regularity[$index][3] = is_even(min($tmp_numbers_array));
		$even_regularity[$index][4] = is_even(max($tmp_numbers_array));
		$even_regularity[$index][5] = (int)(is_even($day['fn']) && is_even($day['sn']) && is_even($day['tn']));
		$even_regularity[$index][6] = (int)!(!is_even($day['fn']) && !is_even($day['sn']) && !is_even($day['tn']));
	}

	$daily_stats[$index][] = (int)(
		($day['fn'] + 1) == $day['sn'] || ($day['fn'] - 1) == $day['sn']
		|| ($day['tn'] + 1) == $day['sn'] || ($day['tn'] - 1) == $day['sn']
		|| ($day['fn'] + 1) == $day['tn'] || ($day['fn'] - 1) == $day['tn']
	);
	$daily_stats[$index][] = (int)(
		($day['fn'] === $day['tn'] || $day['sn'] === $day['tn'] || $day['fn'] === $day['sn'])
		&& !($day['fn'] === $day['sn'] && $day['fn'] === $day['tn'])
	);
}

/** fifth table, regularity of even intervals
 * 0 - first number is even counts of intervals
 * 1 - last number is even counts of intervals
 * 2 - lowest number is even counts of intervals
 * 3 - biggest number is even counts of intervals
 */

$even_regularity_intervals = calculate_regularity($even_regularity, 6);
$odd_regularity_intervals = calculate_regularity($even_regularity, 7, true);

// third table, numbers_stats
$numbers_stats = calculate_regularity($daily_stats, 12);

function render_single_ball_stats_and_regularity($ball) {
	global $NUMBERS_REGULARITY_HEADERS;
	global $daily_stats_per_balls;
	$daily_stats_per_ball = $daily_stats_per_balls[$ball];
	$daily_stats_per_ball['regularity'] = calculate_regularity($daily_stats_per_balls[$ball]['balls'], 10);
	?>
	<h1> Регулярність Кульок </h1>
	<div class="table-container">
		<table width="100" class="table table-striped">
			<thead>
				<th>кулька</th>
				<th>всього</th>
				<?php
					// count of first element childs
					for ($i = 0; $i <= count($daily_stats_per_ball['regularity'][0]) - 2; $i++) {
						echo '<th>'.($i).'</th>';
					}
				?>
			</thead>
			<tbody>
				<?php
				foreach ($daily_stats_per_ball['regularity'] as $number => $number_stat) {
					echo '<tr>';
						echo '<td>'.$NUMBERS_REGULARITY_HEADERS[$number].'</td>';
						foreach ($number_stat as $index => $count) {
							echo '<td>'.$count.'</td>';
						}
					echo '</tr>';
				}
				?>
			</tbody>
		</table>
	</div>

	<h1> Кульки </h1>
	<div class="table-container for-fixed">
		<table class="table table-fixed balls_single">
			<thead>
				<tr>
					<th>№</th>
					<th>дата</th>
					<?php
						for ($i = 0; $i <= 9; $i++) {
							echo '<th>'.($i).'</th>';
						}
					?>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($daily_stats_per_ball['balls'] as $index => $day_stats) {
					echo '<tr>';
					echo '<td>'.$daily_stats_per_balls['info'][$index]['number'].'</td>';
					echo '<td>'.$daily_stats_per_balls['info'][$index]['date'].'</td>';
					foreach ($day_stats as $key => $number) {
						echo '<td class="'.($number === 1 ? 'bg-danger' : '').'"></td>';
					}
					echo '</tr>';
				}
				?>
			</tbody>
		</table>
	</div>
<?php
}
?>

<!DOCTYPE html>
<html lang="en"></html>
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="X-UA-Compatible" content="ie=edge">
		<link rel="stylesheet" href="libs/bootstrap.css">
		<link rel="stylesheet" href="libs/main.css">
		<script src="libs/jquery.js"></script>
		<script src="libs/popper.js"></script>
		<script src="libs/bootstrap.js"></script>
		<script src="libs/main.js"></script>
		<title>Lottery Statistics</title>
	</head>
	<body>

		<header>
			<div class="navigation">
				<ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
					<li class="nav-item">
						<a class="nav-link active" id="lottery-table-tab" data-toggle="pill" href="#lottery-table" role="tab" aria-controls="lottery-table" aria-selected="true">Лотерея</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" id="numbers-table-tab" data-toggle="pill" href="#numbers-table" role="tab" aria-controls="numbers-table" aria-selected="false">Регулярність номерів</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" id="even-table-tab" data-toggle="pill" href="#even-table" role="tab" aria-controls="even-table" aria-selected="false">Регулярність парності</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" id="odd-table-tab" data-toggle="pill" href="#odd-table" role="tab" aria-controls="odd-table" aria-selected="false">Регулярність НЕпарності</a>
					</li>

					<li class="nav-item">
						<a class="nav-link" id="numbers-table-1-tab" data-toggle="pill" href="#numbers-table-1" role="tab" aria-controls="numbers-table-1" aria-selected="false">Регулярність 1 кульки</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" id="numbers-table-2-tab" data-toggle="pill" href="#numbers-table-2" role="tab" aria-controls="numbers-table-1" aria-selected="false">Регулярність 2 кульки</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" id="numbers-table-3-tab" data-toggle="pill" href="#numbers-table-3" role="tab" aria-controls="numbers-table-1" aria-selected="false">Регулярність 3 кульки</a>
					</li>
				</ul>
			</div>
		</header>

		<div id="preloader">
			<div class="spinner-border text-light" role="status">
				<span class="sr-only">Завантаження...</span>
			</div>
		</div>

		<div id="add-day-container">
			<div class="add-day-container_inner">
				<h2>Додати день</h2>
				<div class="add-day-container_inner__top">
					<input type="number" id="add-day-number" class="form-control" placeholder="№">
					<input type="date" id="add-day-date" class="form-control" placeholder="дата">
					<input type="number" id="add-day-fn" class="form-control" placeholder="1 кулька">
					<input type="number" id="add-day-sn" class="form-control" placeholder="2 кулька">
					<input type="number" id="add-day-tn" class="form-control" placeholder="3 кулька">
				</div>
				<div class="add-day-container_inner__bottom">
					<button type="button" id="add-day-save" class="btn btn-success">Зберегти</button>
					<button type="button" id="add-day-cancel" class="btn btn-danger">Відміна</button>
				</div>
			</div>	
		</div>

		<!-- first table -->
		<div class="tab-content" id="pills-tabContent">
			<div class="tab-pane fade show active" id="lottery-table" role="tabpanel" aria-labelledby="lottery-table-tab">
				<h1> Лотерея </h1>
				<div class="table-container for-fixed">
					<table id="lottery-table" class="table table-striped table-fixed">
						<thead>
							<th>№</th>
							<th>дата</th>
							<th>1 кулька</th>
							<th>2 кулька</th>
							<th>3 кулька</th>
							<th>дії</th>
						</thead>
						<tbody>
							<?php
							foreach ($lottery_data as $day_index => $day_lottery) {
								echo '<tr>';
									echo '<td>'.($day_lottery['number']).'</td>';
									foreach ($day_lottery as $number_index => $number) {
										if ($number_index != 'id' && $number_index != 'number') {
											echo '<td>'.$number.'</td>';
										}
									}
									echo '<td> <span action="remove-field" day-id="'.$day_lottery['id'].'">X</span> </td>';
								echo '</tr>';
							}
							?>
						</tbody>
					</table>
				</div>
				<footer>
					<div class="config">
						<div class="config-el">
							<button type="button" id="add-day" class="btn btn-primary">Додати день</button>
						</div>
						<div class="config-el">
							<label> Імпорт з csv </label>
							<input type="file" class="form-control-file" id="import-from-csv">
						</div>
					</div>
				</footer>
			</div>
			<!-- second and third table -->
			<div class="tab-pane fade" id="numbers-table" role="tabpanel" aria-labelledby="numbers-table-tab">
				<h1> Регулярність Кульок </h1>
				<div class="table-container">
					<table width="100" class="table table-striped">
						<thead>
							<th>кулька</th>
							<th>всього</th>
							<?php
								// count of first element childs
								for ($i = 0; $i <= count($numbers_stats[0]) - 2; $i++) {
									echo '<th>'.($i).'</th>';
								}
							?>
						</thead>
						<tbody>
							<?php
							foreach ($numbers_stats as $number => $number_stat) {
								echo '<tr>';
									echo '<td>'.$NUMBERS_REGULARITY_HEADERS[$number].'</td>';
									foreach ($number_stat as $index => $count) {
										echo '<td>'.$count.'</td>';
									}
								echo '</tr>';
							}
							?>
						</tbody>
					</table>
				</div>

				<h1> Кульки </h1>
				<div class="table-container for-fixed">
					<table class="table table-fixed balls">
						<thead>
							<tr>
								<th>№</th>
								<th>дата</th>
								<?php
									foreach ($NUMBERS_REGULARITY_HEADERS as $key => $value) {
										echo '<th>'.$value.'</th>';
									}
								?>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($daily_stats as $index => $day_stats) {
								echo '<tr>';
								foreach ($day_stats as $key => $number) {
									if ($key === 'date' | $key === 'number') {
										echo '<td>'.$number.'</td>';
									} else {
										echo '<td class="'.($number === 1 ? 'bg-danger' : '').'"></td>';
									}
								}
								echo '</tr>';
							}
							?>
						</tbody>
					</table>
				</div>	
			</div>
			<!-- fourth and fifth table -->
			<div class="tab-pane fade" id="even-table" role="tabpanel" aria-labelledby="even-table-tab">
				<h1> Регулярність Парних </h1>
				<div class="table-container">
					<table class="table table-striped">
						<thead>
							<th>№</th>
							<th>всього</th>
							<?php
								// count of first element childs
								for ($i = 0; $i <= count($even_regularity_intervals[0]) - 2; $i++) {
									echo '<th>'.$i.'</th>';
								}
							?>
						</thead>
						<tbody>
							<?php
								foreach ($even_regularity_intervals as $index => $daily_even_regularity_intervals) {
									echo '<tr>';
										echo '<td>'.$EVEN_REGULARITY_HEADERS[$index].'</td>';
										foreach ($daily_even_regularity_intervals as $value_even_regularity_intervals) {
											echo '<td>'.$value_even_regularity_intervals.'</td>';
										}	
									echo '</tr>';
								}
							?>
						</tbody>
					</table>
				</div>

				<h1> Парні </h1>
				<div class="table-container for-fixed">
					<table class="table table-fixed even-regularity">
						<thead>
							<th>№</th>
							<th>дата</th>
							<?php
								foreach ($EVEN_REGULARITY_HEADERS as $key => $value) {
									echo '<th>'.$value.'</th>';
								}
							?>
						</thead>
						<tbody>
							<?php
								foreach ($even_regularity as $index => $daily_even_regularity) {
									echo '<tr>';
									foreach ($daily_even_regularity as $key => $number) {
										if ($key === 'date' | $key === 'number') {
											echo '<td>'.$number.'</td>';
										} else {
											$checking_index = $key === 6 ? '0' : '1';
											echo '<td class="'.($number == $checking_index ? 'bg-danger' : '').'"></td>';
										}
									}
									echo '</tr>';
								}
							?>
						</tbody>
					</table>
				</div>	
			</div>
			<!-- odd tables -->
			<div class="tab-pane fade" id="odd-table" role="tabpanel" aria-labelledby="odd-table-tab">
				<h1> Регулярність непарних </h1>
				<div class="table-container">
					<table class="table table-striped">
						<thead>
							<th>№</th>
							<th>всього</th>
							<?php
								// count of first element childs
								for ($i = 0; $i <= count($odd_regularity_intervals[0]) - 2; $i++) {
									echo '<th>'.$i.'</th>';
								}
							?>
						</thead>
						<tbody>
							<?php
								foreach ($odd_regularity_intervals as $index => $daily_even_regularity_intervals) {
									if ($index != 5) {
										echo '<tr>';
										echo '<td>'.$ODD_REGULARITY_HEADERS[$index].'</td>';
										foreach ($daily_even_regularity_intervals as $value_even_regularity_intervals) {
											echo '<td>'.$value_even_regularity_intervals.'</td>';
										}	
										echo '</tr>';
									}
								}
							?>
						</tbody>
					</table>
				</div>

				<h1> Непарні </h1>
				<div class="table-container for-fixed">
					<table class="table table-fixed even-regularity">
						<thead>
							<th>№</th>
							<th>дата</th>
							<?php
								foreach ($ODD_REGULARITY_HEADERS as $key => $value) {
									echo '<th>'.$value.'</th>';
								}
							?>
						</thead>
						<tbody>
							<?php
								foreach ($even_regularity as $index => $daily_even_regularity) {
									echo '<tr>';
									foreach ($daily_even_regularity as $key => $number) {
										if ($key === 'date' | $key === 'number') {
											echo '<td>'.$number.'</td>';
										} else {
											$checking_index = $key === 5 ? '1' : '0';
											echo '<td class="'.($number == $checking_index ? 'bg-danger' : '').'"></td>';
										}
									}
									echo '</tr>';
								}
							?>
						</tbody>
					</table>
				</div>	
			</div>
			
			<!-- regularity but only first ball -->
			<div class="tab-pane fade show" id="numbers-table-1" role="tabpanel" aria-labelledby="numbers-table-1-tab">
				<?php render_single_ball_stats_and_regularity('fn'); ?>
			</div>

			<!-- regularity but only second ball -->
			<div class="tab-pane fade show" id="numbers-table-2" role="tabpanel" aria-labelledby="numbers-table-2-tab">
				<?php render_single_ball_stats_and_regularity('sn'); ?>
			</div>

			<!-- regularity but only third ball -->
			<div class="tab-pane fade show" id="numbers-table-3" role="tabpanel" aria-labelledby="numbers-table-3-tab">
				<?php render_single_ball_stats_and_regularity('tn'); ?>
			</div>
		</div>
	</body>
</html>