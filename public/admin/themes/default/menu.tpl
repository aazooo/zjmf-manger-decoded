<div class="vertical-menu">
  <div data-simplebar="init" class="h-100">
    <div class="simplebar-wrapper" style="margin: 0px;">
      <div class="simplebar-height-auto-observer-wrapper">
        <div class="simplebar-height-auto-observer"></div>
      </div>
      <div class="simplebar-mask">
        <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
          <div class="simplebar-content-wrapper mm-active" style="height: 100%; overflow: hidden;">
            <div class="simplebar-content mm-show" style="padding: 0px;">
              <!--- Sidemenu -->
              <div id="sidebar-menu">
                <!-- Left Menu Start -->
                <!-- <ul class="metismenu list-unstyled mm-show" id="side-menu"> 以前的js效果-->
                <ul class="metismenu list-unstyled mm-show" id="side-menu-diy">
                  {foreach $PluginsMenu as $nv}
                    <li class="menu">
                      <a href="{if $nv.menu}javascript: ;{else}{$nv.url}{/if}" aria-expanded="true" class="menu-title-a">
                        <span class="menu-title">{$nv.title}</span><i class="bi-chevron-down"></i>
                      </a>
                      {if $nv.menu}
                        <ul class="sub-menu mm-collapse mm-show" aria-expanded="false">
                          {foreach $nv.menu as $subnav}
                            <li class="{if $subnav.url == $PluginUrl}mm-active{/if} link">
                              <a href="{if $subnav.menu}javascript: ;{else}{$subnav.url}{/if}"
                                 class="{if $subnav.menu}has-arrow{/if} waves-effect">
                                <span>{$subnav.name}</span>
                              </a>
                            </li>
                          {/foreach}
                        </ul>
                      {/if}
                    </li>
                  {/foreach}


                  <!--<li class="menu active">
                    <a href="javascript: ;" aria-expanded="true">
                      <span>插件中心</span> <i class="bi-chevron-down"></i>
                    </a>
                    <ul class="sub-menu mm-collapse mm-show" aria-expanded="false">
                      {foreach $PluginsMenu as $v}
                        <li class="{if $v.name == $Addons}mm-active{/if} link"><a href="{$v.menu}">{$v.title}</a></li>
                      {/foreach}

                    </ul>
                  </li>-->


                    </ul>
              </div>
              <!-- Sidebar -->
            </div>
          </div>
        </div>
      </div>
      <div class="simplebar-placeholder" style="width: auto;"></div>
    </div>
    <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
      <div class="simplebar-scrollbar" style="transform: translate3d(0px, 0px, 0px); display: none;"></div>
    </div>
    <div class="simplebar-track simplebar-vertical" style="visibility: hidden;">
      <div class="simplebar-scrollbar" style="height: 613px; transform: translate3d(0px, 0px, 0px); display: none;">
      </div>
    </div>
  </div>
</div>
<style>
.menu-title {
  float: left;
  max-width: 120px;
  overflow: hidden;
  height: 26px;
  text-overflow: ellipsis;
  white-space: nowrap;
}
#side-menu-diy {
  padding: 0px;
}
.bi-chevron-up::before {
  content: "\f286";
}
</style>
<!-- 折叠效果js -->
<script>
$('#sidebar-menu').on('click','.menu-title-a',function(){
    $(this).siblings('ul').slideToggle()
    if ($(this).find('.bi-chevron-down').length > 0) {
      $(this).find('.bi-chevron-down').addClass('bi-chevron-up').removeClass('bi-chevron-down')
    } else if ($(this).find('.bi-chevron-up').length > 0) {
      $(this).find('.bi-chevron-up').addClass('bi-chevron-down').removeClass('bi-chevron-up')
    }
})
</script>