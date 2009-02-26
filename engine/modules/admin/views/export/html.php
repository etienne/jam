<table cellpadding="0" cellspacing="0">
	<tr>
		<? foreach($headers as $header): ?>
		<th><?= $header ?></th>
		<? endforeach; ?>
	</tr>
	<? foreach($data as $row): ?>
	<tr>
		<? foreach($row as $cell): ?>
		<td><?= $cell ?></td>
		<? endforeach; ?>
	</tr>
	<? endforeach; ?>
</table>
