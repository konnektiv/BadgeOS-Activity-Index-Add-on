(function ($) {

var chart = new dhtmlXChart({
    view: "line",
    container: "actvity_chart_container",
    value: "#points#",
    tooltip: "#tooltip#",

    yAxis:{
        title:"Activity Index"
    },
    xAxis:{
        title:activity_index_data['header'],
        template:"#label#"
    }
});

chart.parse(activity_index_data['data'],"json");

$('.activity-index-select-table').click(function(e) {
	e.preventDefault();
	e.stopPropagation();
	if ($(this).hasClass('active'))
		return;
	$('.activity_index_chart').hide();
	$('.activity_index_table').show();
	$('.activity-index-select-chart').toggleClass('active');
	$('.activity-index-select-table').toggleClass('active');
});

$('.activity-index-select-chart').click(function(e) {
	e.preventDefault();
	e.stopPropagation();
	if ($(this).hasClass('active'))
		return;
	$('.activity_index_chart').show();
	$('.activity_index_table').hide();
	$('.activity-index-select-chart').toggleClass('active');
	$('.activity-index-select-table').toggleClass('active');
});

// add last class
$('.activity_index_chart .dhx_axis_item_x').last().addClass('last');

}(jQuery));
