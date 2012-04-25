<?php if (!defined('TYPO3_MODE')) die ('Access denied.'); ?>

Page:
<?php for($currentPageOffset 	= 0; $currentPageOffset < $this->getTotalPagesCount(); $currentPageOffset++ ){  ?>
	<a href="index.php?offset=<?php echo htmlspecialchars($currentPageOffset * $this->getPerPage()); ?>">
		<?php echo	htmlspecialchars($this->getLabelForPageOffset($currentPageOffset)); ?>
	</a>
	<?php if($currentPageOffset+1 < $this->getTotalPagesCount()){ ?>
	|
	<?php } ?>

<?php } ?>
<hr />