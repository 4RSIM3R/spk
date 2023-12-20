<?php require_once('includes/init.php'); ?>
<?php cek_login($role = array(1, 2)); ?>

<?php
$ada_error = false;
$result = '';

$id_hp = (isset($_GET['id'])) ? trim($_GET['id']) : '';

if(!$id_hp) {
	$ada_error = 'Maaf, data tidak dapat diproses.';
} else {
	$query = $pdo->prepare('SELECT id_hp FROM handphone WHERE id_hp = :id_hp');
	$query->execute(array('id_hp' => $id_hp));
	$result = $query->fetch();
	
	if(empty($result)) {
		$ada_error = 'Maaf, data tidak dapat diproses.';
	} else {
		
		$handle = $pdo->prepare('DELETE FROM nilai_hp WHERE id_hp = :id_hp');				
		$handle->execute(array(
			'id_hp' => $result['id_hp']
		));
		$handle = $pdo->prepare('DELETE FROM handphone WHERE id_hp = :id_hp');				
		$handle->execute(array(
			'id_hp' => $result['id_hp']
		));
		redirect_to('list-hp.php?status=sukses-hapus');
		
	}
}
?>

<?php
$judul_page = 'Hapus handphone';
require_once('template-parts/header.php');
?>

	<div class="main-content-row">
	<div class="container clearfix">
	
		<?php include_once('template-parts/sidebar-hp.php'); ?>
	
		<div class="main-content the-content">
			<h1><?php echo $judul_page; ?></h1>
			
			<?php if($ada_error): ?>
			
				<?php echo '<p>'.$ada_error.'</p>'; ?>	
			
			<?php endif; ?>
			
		</div>
	
	</div><!-- .container -->
	</div><!-- .main-content-row -->


<?php
require_once('template-parts/footer.php');