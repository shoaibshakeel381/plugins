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

<div style="padding:1.5em;text-align:center">

</div>
<div id="GraphArea">
</div>

Select User:
    <select id="userid">
        <option value="0">All Users</option>
        {foreach from=$users item=user}
            <option value="{$user.id}">{$user.username}</option>
        {/foreach}        
    </select>
Select Page:
    <select id="pageid">
        <option value="0">All Pages</option>
        {foreach from=$pages item=page}
            <option value="{$page.pageid}">{$page.pageid}</option>
        {/foreach}        
    </select>
