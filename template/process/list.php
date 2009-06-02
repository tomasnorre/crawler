<span style="padding-left: 5px;">
	<a href="index.php"><?php echo $this->getRefreshIcon(); ?></a> <br/>
</span>

<h2>General Informations:</h2>
<table>
	<tr>
		<td>Pending Entries:</td>
		<td><?php echo $this->getTotalItemCount(); ?>
	</tr>
</table>

<h2>Process States: </h2>
<table>
	<tr>
		<td>State: </td>
		<td>Id: </td>
		<td>Time first item: </td>
		<td>Time last item: </td>
		<td>Runtime: </td>
		<td>TTL: </td>
		<td>Progress <td>
	</td>
	<?php foreach($this->getProcessCollection() as $process){
			/** @var tx_crawler_domain_process $process */
	?>
		<tr>
			<td><?php echo $this->getIconForState($process->getState()); ?></td>
			<td><?php echo $process->getProcess_id(); ?>
			<td><?php echo $this->asDate($process->getTimeForFirstItem()); ?></td>
			<td><?php echo $this->asDate($process->getTimeForLastItem()); ?></td>
			<td><?php echo floor($process->getRuntime()/ 60); ?> min. <? echo $process->getRuntime() % 60 ?> sec.</td>
			<td><?php echo $this->asDate($process->getTTL()); ?></td>
			<td><?php echo $process->getProgress() ?> %</td>
		</tr>
	<?php } ?>
</table>

<br />