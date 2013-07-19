<div id="content">
	<h2>{l s='ProCore Manager' mod='coremanager'}</h2>
	{if count($errors) > 0}
		<div class="warning error">
			<h3>{l s='Error' mod='coremanager'}</h3>
			<ul>
				{foreach from=$errors item=error}
					<li>{$error}</li>
				{/foreach}
			</ul>
		</div>
	{/if}
	{if count($success) > 0}
		<div class="confirm conf">
			<h3>{l s='Success' mod='coremanager'}</h3>
			<ul>
				{foreach from=$success item=item}
					<li>{$item}</li>
				{/foreach}
			</ul>
		</div>
	{/if}
	<div id="rightColumn">
		{$rightColumn}
	</div>
	<div id="leftColumn">
		<table cellpadding="0" cellspacing="0" class="table width3">
			<tbody>
				<tr>
					<th colspan="4" class="center" style="cursor: pointer" onclick="openCloseLayer('coreModules');">{l s='Available Modules' mod='coremanager'}</th>
				</tr>
			</tbody>
		</table>
		<div id="coreModules">
			{if $modules|count > 0}
				<table cellpadding="0" cellspacing="0" class="table width3">
					<tbody>
						{foreach from=$modules item=module key=mKey}
							<tr {if $mKey % 2 eq 1}class="alt_row"{/if} style="height: 42px;">
								<td style="padding-left: 10px;">
									<img src="{$module.icon}" /> {$module.name} {$module.version}
								</td>
								<td class="center" width="120">
									{if $module.installed eq 1}
										<form action="" method="post" style="float:left">
											<input type="hidden" name="action" value="transplant" />
											<input type="hidden" name="module" value="{$module.reference}" />
											<input type="submit" class="button small" id="moduleTransplant" rel="{$module.reference}" value="Transplant" />
										</form>
									{/if}
									<form action="" method="post" style="float:right">
										<input type="hidden" name="module" value="{$module.reference}" />
										<input type="hidden" name="action" value="{if $module.installed eq 1}uninstall_module{else}install_module{/if}" />
										<input type="submit" class="button small" id="moduleAction" rel="{$module.reference}" value="{if $module.installed eq 1}Uninstall{else}Install{/if}" />
									</form>
								</td>
							</tr>	
						{/foreach}
					</tbody>
				</table>
			{/if}
		</div>
	</div>
	<br class="clear" />
</div>