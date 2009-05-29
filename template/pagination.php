Page: 
<?php for($currentPageOffset 	= 0; $currentPageOffset < $this->getTotalPagesCount(); $currentPageOffset++ ){  ?>
	<a href="index.php?offset=<?php echo $currentPageOffset * $this->getPerPage(); ?>">
		<?php echo	$this->getLabelForPageOffset($currentPageOffset); ?>
	</a> 
	<?php if($currentPageOffset+1 < $this->getTotalPagesCount()){ ?>
	| 
	<?php } ?>

<?php } ?>
<hr />