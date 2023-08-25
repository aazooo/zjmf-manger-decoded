<style>
	.border-hover:hover {
		box-shadow: 0px 0px 0px 1px #007bfc;
	}
</style>
<div class="card">
	<div class="card-body">
		<div class="ticket-top">
			<p>{$Lang.ticket_top_one}</p>

			<p>{$Lang.ticket_top_two}</p>

			<p>{$Lang.ticket_top_three}</p>

			<p>{$Lang.ticket_top_four}</p>

			<p>{$Lang.ticket_top_five}</p>

			<p>{$Lang.ticket_top_six}:</p>

			<p>{$Lang.ticket_top_seven}</p>

			<p>{$Lang.ticket_top_eight}
		</div>
	</div>
</div>

<div class="card">
	<div class="card-body">
		<div class="row">
			{if $SubmitTicket.department}
			{foreach $SubmitTicket.department as $department}
			<div class="col-sm-4">
				<a href="submitticket?step=2&dptid={$department.id}">
					<div class="card border border-hover">
						<div class="card-header bg-transparent ">
							<h5 class="my-0 ">{$department.name}</h5>
						</div>
						<div class="card-body">
							<p class="card-text text-muted">{$department.description} </p>
						</div>
					</div>
				</a>
			</div>
			{/foreach}
			{else}
			{include file="error/alert" value="{$Lang.temporary_department}"}
			{/if}
		</div>
	</div>
</div>