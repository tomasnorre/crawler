<span style="padding-left: 5px;">
	<?php echo $this->getRefreshLink(); ?>
	<?php echo $this->getEnableDisableLink(); ?>
	<?php echo $this->getAddLink(); ?>
	<?php echo $this->getModeLink(); ?>
</span>

<?php if($this->getActionMessage() != ''): ?>
	<div id="message">
		<?php echo $this->getActionMessage(); ?>
	</div>
<?php endif; ?>

<h2><?php echo $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.php:labels.generalinformation'); ?>:</h2>
<table>
	<tr>
		<td><?php echo $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.php:labels.pendingoverview'); ?>:</td>
		<td><?php echo htmlspecialchars($this->getAssignedUnprocessedItemCount()); ?> / <?php echo htmlspecialchars($this->getTotalUnprocessedItemCount()); ?>  </td>
	</tr>
	<tr>
		<td><?php echo $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.php:labels.curtime'); ?>:</td>
		<td><?php echo $this->asDate(time()); ?></td>
	</tr>
	<tr>
		<td><?php echo $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.php:labels.processcount'); ?></td>
		<td><?php echo htmlspecialchars($this->getActiveProcessCount()); ?> / <?php echo htmlspecialchars($this->getMaxActiveProcessCount()); ?></td>
	</tr>
	<tr>
		<td><?php echo $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.php:labels.clipath'); ?>:</td>
		<td><?php echo htmlspecialchars($this->getCliPath()); ?></td>
	</tr>
</table>

<h2><?php echo $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.php:labels.processstates'); ?>: </h2>
<table class="processes">
	<tr>
		<td><?php echo $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.php:labels.state'); ?>: </td>
		<td><?php echo $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.php:labels.processid'); ?>: </td>
		<td><?php echo $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.php:labels.time.first'); ?>: </td>
		<td><?php echo $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.php:labels.time.last'); ?>: </td>
		<td><?php echo $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.php:labels.time.duration'); ?>: </td>
		<td><?php echo $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.php:labels.ttl'); ?>: </td>
		<td><?php echo $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.php:labels.status.current'); ?>: </td>
		<td><?php echo $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.php:labels.status.initial'); ?>:</td>
		<td><?php echo $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.php:labels.status.finally'); ?>:</td>
		<td><?php echo $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.php:labels.status.progress'); ?>: </td>
	</tr>
	<?php foreach($this->getProcessCollection() as $process): /* @var $process tx_crawler_domain_process */ ?>
		<tr class="<?php echo (++$count % 2 == 0) ? 'odd': 'even' ?>">
			<td><?php echo $this->getIconForState(htmlspecialchars($process->getState())); ?></td>
			<td><?php echo htmlspecialchars($process->getProcess_id()); ?></td>
			<td><?php echo htmlspecialchars($this->asDate($process->getTimeForFirstItem())); ?></td>
			<td><?php echo htmlspecialchars($this->asDate($process->getTimeForLastItem())); ?></td>
			<td><?php echo htmlspecialchars(floor($process->getRuntime()/ 60)); ?> min. <?php echo htmlspecialchars($process->getRuntime()) % 60 ?> sec.</td>
			<td><?php echo htmlspecialchars($this->asDate($process->getTTL())); ?></td>
			<td><?php echo htmlspecialchars($process->countItemsProcessed()); ?></td>
			<td><?php echo htmlspecialchars($process->countItemsAssigned()); ?></td>
			<td><?php echo htmlspecialchars($process->countItemsToProcess()+$process->countItemsProcessed()); ?></td>
			<td><?php echo htmlspecialchars($process->getProgress()) ?> %</td>
		</tr>
	<?php endforeach; ?>
</table>

<br />