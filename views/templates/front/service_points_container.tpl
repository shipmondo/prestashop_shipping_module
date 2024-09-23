<div id="shipmondo-service-points-container">
    {* Replaced by the service point selector *}
    <h1>test</h1>
</div>

{* TODO delete before release
            $.ajax({
                url: service_points_endpoint,
                type: 'GET',
                data: {
                    action: 'get'
                },
                success: function (response) {
                    response = JSON.parse(response);
                    if (response['status'] == 'success') {
                        var html = response['service_point_html'];
                        $('#shipmondo-service-points-container').html(html);
                    }
                }
            });
*}