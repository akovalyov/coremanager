<div style="float:right; width:280px;">
	<table cellpadding="0" cellspacing="0" class="table width3" style="width:280px;">
		<tbody>
			<tr>
				<th colspan="4" class="center">
					<strong>{l s='Transplant' mod='coremanager'}</strong>
				</th>
			</tr>
		</tbody>
	</table>
	<table cellpadding="0" cellspacing="0" class="table width3" style="width:280px;"><tbody><tr style="height: 42px;">
		<td>
			<table cellpadding="10" cellspacing="5">
				<tbody>
					<tr>
						<td width="250">
							<form method="post">
								<select name="transplantTo" style="float: left;width: 160px">
									<option value="0" disabled="disabled">{l s='Select Transplant' mod= 'coremanager'}</option>
									{foreach from=$transplants item=hook}
										<option value="{$hook.id_hook}">{$hook.title}</option>
									{/foreach}
								</select>
								<input type="hidden" name="module" value="{$module}" />
								<input type="hidden" name="action" value="transplant" />
								<input type="submit" name="doTransplant" value="Add Transplant" class="button small" style="float: right" />
							</form>
						</td>
					</tr>
					<tr>
						<td>
							<ul id="transplantList">
								{if count($transplanted) > 0}
									{foreach from=$transplanted item=hook}
										<li style="padding:5px 0;">
											[<a href="{$selfURL}&amp;action=deleteTransplant&amp;module={$module}&amp;reference={$hook.id_hook}">{l s='remove' mod='coremanager'}</a>]{$hook.title}
										</li>
									{/foreach}
								{else}
									<li>{l s='No Transplants' mod='coremanager'}</li>
								{/if}
							</ul>
						</td>
					</tr>
				</tbody>
			</table>
		</td>
	</table>
</div>