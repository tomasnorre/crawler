<span style="padding-left: 5px;">
	<?php echo $this->getRefreshLink(); ?>
	<?php echo $this->getEnableDisableLink(); ?>
	<?php echo $this->getAddLink(); ?>
	<?php echo $this->getModeLink(); ?>
</span>
<?php if($this->getActionMessage() != ''){ ?>
	<div id="message">
		<?php echo $this->getActionMessage(); ?>
	</div>
<?php } ?>
<h2>General Informations:</h2>
<table>
	<tr>
		<td>Pending Entries (assigned / overall):</td>
		<td><?php echo htmlspecialchars($this->getAssignedUnprocessedItemCount()); ?> / <?php echo htmlspecialchars($this->getTotalUnprocessedItemCount()); ?>  </td>
	</tr>
	<tr>
		<td>Servertime:</td>
		<td><?php echo $this->asDate(time()); ?></td>
	</tr>
	<tr>
		<td>Processes count (running / max)</td>
		<td><?php echo htmlspecialchars($this->getActiveProcessCount()); ?> / <?php echo htmlspecialchars($this->getMaxActiveProcessCount()); ?></td>
	</tr>
	<tr>
		<td>CLI-Path:</td>
		<td><?php echo htmlspecialchars($this->getCliPath()); ?></td>
	</tr>
</table>

<h2>Process States: </h2>
<table class="processes">
	<tr>
		<td>State: </td>
		<td>Id: </td>
		<td>Time first item: </td>
		<td>Time last item: </td>
		<td>Runtime: </td>
		<td>TTL: </td>
		<td>Processed: </td>
		<td>Initially assigned:</td>
		<td>Finally assigned:</td>
		<td>Progress: </td>
	</tr>
	<?php foreach($this->getProcessCollection() as $process){ /* @var $process tx_crawler_domain_process */
		$count++;
		$odd = ($count % 2 == 0) ? true : false;
	?>
		<tr class="<?php echo $odd ? 'odd': 'even' ?>">
			<td><?php echo $this->getIconForState(htmlspecialchars($process->getState())); ?></td>
			<td> <?php echo htmlspecialchars($process->getProcess_id()); ?></td>
			<td><?php echo htmlspecialchars($this->asDate($process->getTimeForFirstItem())); ?></td>
			<td><?php echo htmlspecialchars($this->asDate($process->getTimeForLastItem())); ?></td>
			<td><?php echo htmlspecialchars(floor($process->getRuntime()/ 60)); ?> min. <?php echo htmlspecialchars($process->getRuntime()) % 60 ?> sec.</td>
			<td><?php echo htmlspecialchars($this->asDate($process->getTTL())); ?></td>
			<td><?php echo htmlspecialchars($process->countItemsProcessed()); ?></td>
			<td><?php echo htmlspecialchars($process->countItemsAssigned()); ?></td>
			<td><?php echo htmlspecialchars($process->countItemsToProcess()+$process->countItemsProcessed()); ?></td>
			<td><?php echo htmlspecialchars($process->getProgress()) ?> %</td>
		</tr>
	<?php } ?>
</table>

<br />