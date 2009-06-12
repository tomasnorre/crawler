<span style="padding-left: 5px;">
	<a href="index.php"><?php echo $this->getRefreshIcon(); ?></a>
	<?php echo $this->getEnableDisableLink(); ?>
	<?php echo $this->getAddLink(); ?>
</span>	
<?php if($this->getActionMessage() != ''){ ?>
	<div id="message">
		<?php echo $this->getActionMessage(); ?>
	</div>
<?php } ?>
<h2>General Informations:</h2>
<table>
	<tr>
		<td>Pending Entries:</td>
		<td><?php echo htmlspecialchars($this->getTotalItemCount()); ?>
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
		<td>Initial Assigned:</td>
		<td>Finally Assigned:</td>
		<td>Progress: </td>
	</tr>
	<?php foreach($this->getProcessCollection() as $process){
		$count++;
		$odd = ($count % 2 == 0) ? true : false;
			/** @var tx_crawler_domain_process $process */
	?>
		<tr class="<?php echo $odd ? 'odd': 'even' ?>">
			<td><?php echo $this->getIconForState(htmlspecialchars($process->getState())); ?></td>
			<td> <?php echo htmlspecialchars($process->getProcess_id()); ?></td>
			<td><?php echo htmlspecialchars($this->asDate($process->getTimeForFirstItem())); ?></td>
			<td><?php echo htmlspecialchars($this->asDate($process->getTimeForLastItem())); ?></td>
			<td><?php echo htmlspecialchars(floor($process->getRuntime()/ 60)); ?> min. <? echo htmlspecialchars($process->getRuntime()) % 60 ?> sec.</td>
			<td><?php echo htmlspecialchars($this->asDate($process->getTTL())); ?></td>
			<td><?php echo htmlspecialchars($process->countItemsProcessed()); ?></td>
			<td><?php echo htmlspecialchars($process->countItemsAssigned()); ?></td>
			<td><?php echo htmlspecialchars($process->countItemsToProcess()+$process->countItemsProcessed()); ?></td>
			<td><?php echo htmlspecialchars($process->getProgress()) ?> %</td>
		</tr>
	<?php } ?>
</table>

<br />