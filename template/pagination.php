<?php if (!defined('TYPO3_MODE')) {
    die('Access denied.');
} ?>

Page:
<?php for ($currentPageOffset = 0; $currentPageOffset < $this->getTotalPagesCount(); $currentPageOffset++) {
    ?>
    <?php
    // This have not be switched to BackendUtility::getModuleUrl as we want to refactor it anyway
    ?>
	<a onClick="window.location+='offset=<?php echo htmlspecialchars($currentPageOffset * $this->getPerPage()); ?>';" href="#">
		<?php echo	htmlspecialchars($this->getLabelForPageOffset($currentPageOffset)); ?>
	</a>
	<?php if ($currentPageOffset + 1 < $this->getTotalPagesCount()) {
        ?>
	|
	<?php
    } ?>

<?php
} ?>
<hr />