<table class="table tablelist">
  <colgroup>
    <col>
    <col>
    <col>
    <col>
    <col>
  </colgroup>
  <thead class="bg-light">
    <tr>
      <th class="pointer" prop="id">
        <span>ID</span>
        <span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
          <i class="bx bx-caret-up"></i>
          <i class="bx bx-caret-down"></i>
        </span>
      </th>
      <th class="pointer" prop="invoice_id">
        <span>{$Lang.bill_no}</span>
        <span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
          <i class="bx bx-caret-up"></i>
          <i class="bx bx-caret-down"></i>
        </span>
      </th>
      <th class="pointer" prop="amount_out">
        <span>{$Lang.amount_money}</span>
        <span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
          <i class="bx bx-caret-up"></i>
          <i class="bx bx-caret-down"></i>
        </span>
      </th>
      <th>
        <span>{$Lang.describe}</span>
      </th>
      <th class="pointer" prop="pay_time">
        <span>{$Lang.refund_time}</span>
        <span class="text-black-50 d-inline-flex flex-column justify-content-center ml-1 offset-3">
          <i class="bx bx-caret-up"></i>
          <i class="bx bx-caret-down"></i>
        </span>
      </th>
    </tr>
  </thead>
  <tbody>
    {if $Transaction}
    {foreach $Transaction as $list}
    <tr>
      <td>{$list.id}</td>
      <td><a href="viewbilling?id={$list.invoice_id}"><span class="badge badge-light">#{$list.invoice_id}</span></a></td>
      <td>{$Currency.prefix}{$list.amount_out}</td>
      <td>{$list.description}</td>
      <td>{$list.pay_time|date="Y-m-d H:i:s"}</td>
    </tr>
    {/foreach}
    {else}
    <tr>
      <td colspan="8">
        <div class="no-data">{$Lang.nothing}</div>
      </td>
    </tr>
    {/if}
  </tbody>
</table>