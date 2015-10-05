<html>
<body>
<script src="lib/require.js"></script>
<script>
    require.config({
        paths: { 'dashboard': 'lib/dashboard' ,'d3': 'lib/d3.min' }
    });

    var options = {
        width : 300,
        height: 400,
        showLabels:true,
        showLegend:true,
        data : [{"label":"Category A", "value":20},
		          {"label":"Category B", "value":50}, 
		          {"label":"Category C", "value":30}]
      };

    require(['dashboard'], function(d) {
        d.PieChart('dashboard',options);
    });
</script>

    <div id='dashboard'></div>
</body>
</html>