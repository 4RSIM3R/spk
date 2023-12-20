<?php
/* ---------------------------------------------
 * SPK SAW
 * ------------------------------------------- */

/* ---------------------------------------------
 * Konek ke database & load fungsi-fungsi
 * ------------------------------------------- */
require_once('includes/init.php');

/* ---------------------------------------------
 * Load Header
 * ------------------------------------------- */
$judul_page = 'Perankingan Menggunakan Metode SAW';
require_once('template-parts/header.php');

/* ---------------------------------------------
 * Set jumlah digit di belakang koma
 * ------------------------------------------- */
$digit = 4;

/* ---------------------------------------------
 * Fetch semua kriteria
 * ------------------------------------------- */
$query = $pdo->prepare('SELECT id_kriteria, nama, type, bobot
	FROM kriteria ORDER BY urutan_order ASC');
$query->execute();
$query->setFetchMode(PDO::FETCH_ASSOC);
$kriterias = $query->fetchAll();

/* ---------------------------------------------
 * Fetch semua handphone (alternatif)
 * ------------------------------------------- */
$query2 = $pdo->prepare('SELECT id_hp, nama_hp FROM handphone');
$query2->execute();			
$query2->setFetchMode(PDO::FETCH_ASSOC);
$handphones = $query2->fetchAll();


/* >>> STEP 1 ===================================
 * Matrix Keputusan (X)
 * ------------------------------------------- */
$matriks_x = array();
$list_kriteria = array();
foreach($kriterias as $kriteria):
	$list_kriteria[$kriteria['id_kriteria']] = $kriteria;
	foreach($handphones as $handphone):
		
		$id_hp = $handphone['id_hp'];
		$id_kriteria = $kriteria['id_kriteria'];
		
		// Fetch nilai dari db
		$query3 = $pdo->prepare('SELECT nilai FROM nilai_hp
			WHERE id_hp = :id_hp AND id_kriteria = :id_kriteria');
		$query3->execute(array(
			'id_hp' => $id_hp,
			'id_kriteria' => $id_kriteria,
		));			
		$query3->setFetchMode(PDO::FETCH_ASSOC);
		if($nilai_hp = $query3->fetch()) {
			// Jika ada nilai kriterianya
			$matriks_x[$id_kriteria][$id_hp] = $nilai_hp['nilai'];
		} else {			
			$matriks_x[$id_kriteria][$id_hp] = 0;
		}

	endforeach;
endforeach;

/* >>> STEP 3 ===================================
 * Matriks Ternormalisasi (R)
 * ------------------------------------------- */
$matriks_r = array();
foreach($matriks_x as $id_kriteria => $nilai_hps):
	
	$tipe = $list_kriteria[$id_kriteria]['type'];
	foreach($nilai_hps as $id_alternatif => $nilai) {
		if($tipe == 'benefit') {
			$nilai_normal = $nilai / max($nilai_hps);
		} elseif($tipe == 'cost') {
			$nilai_normal = min($nilai_hps) / $nilai;
		}
		
		$matriks_r[$id_kriteria][$id_alternatif] = $nilai_normal;
	}
	
endforeach;

/* >>> STEP 5 ===================================
 * Nilai Preferensi (V)
 * ------------------------------------------- */
$matriks_v = array();
foreach($kriterias as $kriteria):
	foreach($handphones as $handphone):
		
		$bobot = $kriteria['bobot'];
		$id_hp = $handphone['id_hp'];
		$id_kriteria = $kriteria['id_kriteria'];
		
		$nilai_r = $matriks_r[$id_kriteria][$id_hp];
		$matriks_v[$id_kriteria][$id_hp] = $bobot * $nilai_r;

	endforeach;
endforeach;

/* >>> STEP 6 ================================
 * Perangkingan
 * ------------------------------------------- */
$ranks = array();
foreach($handphones as $handphone):

	$total_nilai = 0;
	foreach($list_kriteria as $kriteria) {
	
		$bobot = $kriteria['bobot'];
		$id_hp = $handphone['id_hp'];
		$id_kriteria = $kriteria['id_kriteria'];
		
		$nilai_r = $matriks_v[$id_kriteria][$id_hp];
		$total_nilai = $total_nilai + ($nilai_r);

	}
	
	$ranks[$handphone['id_hp']]['id_hp'] = $handphone['id_hp'];
	$ranks[$handphone['id_hp']]['nama_hp'] = $handphone['nama_hp'];
	$ranks[$handphone['id_hp']]['nilai'] = $total_nilai;
	
endforeach;
 
?>

<div class="main-content-row">
<div class="container clearfix">	

	<div class="main-content main-content-full the-content">
		
		<h1><?php echo $judul_page; ?></h1>
		
		<!-- STEP 1. Matriks Keputusan(X) ==================== -->		
		<h3>Step 1: Matriks Keputusan (X)</h3>
		<table class="pure-table pure-table-striped">
			<thead>
				<tr class="super-top">
					<th rowspan="2" class="super-top-left">Nama HP</th>
					<th colspan="<?php echo count($kriterias); ?>">Kriteria</th>
				</tr>
				<tr>
					<?php foreach($kriterias as $kriteria ): ?>
						<th><?php echo $kriteria['nama']; ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach($handphones as $handphone): ?>
					<tr>
						<td><?php echo $handphone['nama_hp']; ?></td>
						<?php						
						foreach($kriterias as $kriteria):
							$id_hp = $handphone['id_hp'];
							$id_kriteria = $kriteria['id_kriteria'];
							echo '<td>';
							echo $matriks_x[$id_kriteria][$id_hp];
							echo '</td>';
						endforeach;
						?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<!-- STEP 2. Bobot Preferensi (W) ==================== -->
		<h3>Step 2: Bobot Preferensi (W)</h3>			
		<table class="pure-table pure-table-striped">
			<thead>
				<tr>
					<th>Nama Kriteria</th>
					<th>Type</th>
					<th>Bobot (W)</th>						
				</tr>
			</thead>
			<tbody>
				<?php foreach($kriterias as $hasil): ?>
					<tr>
						<td><?php echo $hasil['nama']; ?></td>
						<td>
						<?php
						if($hasil['type'] == 'benefit') {
							echo 'Benefit';
						} elseif($hasil['type'] == 'cost') {
							echo 'Cost';
						}							
						?>
						</td>
						<td><?php echo $hasil['bobot']; ?></td>							
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<!-- Step 3: Matriks Ternormalisasi (R) ==================== -->
		<h3>Step 3: Matriks Ternormalisasi (R)</h3>			
		<table class="pure-table pure-table-striped">
			<thead>
				<tr class="super-top">
					<th rowspan="2" class="super-top-left">Nama HP</th>
					<th colspan="<?php echo count($kriterias); ?>">Kriteria</th>
				</tr>
				<tr>
					<?php foreach($kriterias as $kriteria ): ?>
						<th><?php echo $kriteria['nama']; ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach($handphones as $handphone): ?>
					<tr>
						<td><?php echo $handphone['nama_hp']; ?></td>
						<?php						
						foreach($kriterias as $kriteria):
							$id_hp = $handphone['id_hp'];
							$id_kriteria = $kriteria['id_kriteria'];
							echo '<td>';
							echo round($matriks_r[$id_kriteria][$id_hp], $digit);
							echo '</td>';
						endforeach;
						?>
					</tr>
				<?php endforeach; ?>				
			</tbody>
		</table>		
		
		<!-- Step 4: Matriks Ternormalisasi (R) ==================== -->
		<h3>Step 4: Menghitung Nilai Preferensi (R)</h3>			
		<table class="pure-table pure-table-striped">
			<thead>
				<tr class="super-top">
					<th rowspan="2" class="super-top-left">Nama HP</th>
					<th colspan="<?php echo count($kriterias); ?>">Kriteria</th>
				</tr>
				<tr>
					<?php foreach($kriterias as $kriteria ): ?>
						<th><?php echo $kriteria['nama']; ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach($handphones as $handphone): ?>
					<tr>
						<td><?php echo $handphone['nama_hp']; ?></td>
						<?php						
						foreach($kriterias as $kriteria):
							$id_hp = $handphone['id_hp'];
							$id_kriteria = $kriteria['id_kriteria'];
							echo '<td>';
							echo round($matriks_v[$id_kriteria][$id_hp], $digit);
							echo '</td>';
						endforeach;
						?>
					</tr>
				<?php endforeach; ?>				
			</tbody>
		</table>	
		
		<!-- Step 5: Perangkingan ==================== -->
		<?php		
		$sorted_ranks = $ranks;		
		// Sorting
		if(function_exists('array_multisort')):
			$nama_hp = array();
			$nilai = array();
			foreach ($sorted_ranks as $key => $row) {
				$nama_hp[$key]  = $row['nama_hp'];
				$nilai[$key] = $row['nilai'];
			}
			array_multisort($nilai, SORT_DESC, $nama_hp, SORT_ASC, $sorted_ranks);
		endif;
		?>		
		<h3>Step 5: Perangkingan (V)</h3>			
		<table class="pure-table pure-table-striped">
			<thead>					
				<tr>
					<th class="super-top-left">Nama HP</th>
					<th>Ranking</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($sorted_ranks as $handphone ): ?>
					<tr>
						<td><?php echo $handphone['nama_hp']; ?></td>
						<td><?php echo round($handphone['nilai'], $digit); ?></td>											
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>			
		
	</div>

</div><!-- .container -->
</div><!-- .main-content-row -->

<?php
require_once('template-parts/footer.php');