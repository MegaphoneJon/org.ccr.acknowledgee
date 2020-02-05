<div class="crm-group acknowledgee_block-group">
  <div class="header-dark">
    Acknowledgee Information
  </div>
  <div class="label-left crm-section acknowledgee_profile-section">
    {foreach from=$acknowledgeeProfileValues key=fieldname item=value}
    <div class="crm-section acknowledgeerow-{$fieldname}-section form-item" id="acknowledgeerow-{$fieldname}">
      <div class="label">
        <label for="acknowledgee_{$fieldname}">{$value.title}</label>
      </div>
      <div class="content">
        <span class="crm-frozen-field">&nbsp;{$value.value}</span>
      </div>
    </div>
    {/foreach}
  </div>
</div>


