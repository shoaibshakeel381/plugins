<script type="text/javascript" charset="utf-8">
    {literal}
        function requestGraph() 
        {
                var userid = $('#userid').find(":selected").val();
                var pageid = $('#pageid').find(":selected").val();
                var ajaxRequest = new ajaxHelper();
                ajaxRequest.setFormat('html');
                ajaxRequest.addParams({
                    module: 'PurplePlugin',
                    action: 'displayGraph',
                    userid: userid,
                    pageid: pageid
                }, 'GET');
                ajaxRequest.setCallback(function(r) {
                    $("#GraphArea").html(r);
                });
                ajaxRequest.send(false);
            }
        $(document).ready(function() {
            requestGraph();
            $('#userid').on("change", requestGraph);
            $('#pageid').on("change", requestGraph);
        });
    {/literal}
</script>

<div style="padding:1em;">
</div>

<div id="GraphArea">
</div>

Select User:
    <select id="userid" style="width:130px">
        <option value="All">All Users</option>
        {foreach from=$users item=user}
            <option value="{$user.user}">{$user.user}</option>
        {/foreach}        
    </select>
Select Page:
    <select id="pageid" style="width:95px">
        <option value="0">All Pages</option>
        {foreach from=$pages item=page}
            <option value="{$page.pageid}">{$page.pageid}</option>
        {/foreach}        
    </select>
