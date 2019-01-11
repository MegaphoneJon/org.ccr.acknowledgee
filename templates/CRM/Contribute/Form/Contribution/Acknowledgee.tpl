<div class="acknowledgee-section">
  <fieldset class="crm-profile crm-profile-name-acknowledgee">
    <legend>Send an acknowledgment of this gift to</legend>
    {foreach from=$elements item=element}
      <div class="crm-section {$form.$element.name}-section">
        <div class="label">{$form.$element.label}</div>
        <div class="content">
          {$form.$element.html}
        </div>
        <div class="clear"></div>
      </div>
    {/foreach}
  </fieldset>
</div>

