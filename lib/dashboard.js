define(['d3'],function(d3) {
    return {
       
        //  pie chart
        PieChart: function(id,options) {
            var w = options.width;
            var h = options.height;
            var r = (h <= w ? h : w)/2;
            var color = d3.scale.category20c();
            var vis = d3.select('#'+id).append("svg:svg").data([options.data]).attr("width",w).attr("height",h).append("svg:g").attr("transform","translate("+r+","+r+")");
            var pie = d3.layout.pie().value(function(d){return d.value;});
            var arc = d3.svg.arc().outerRadius(r);
       
            // select paths, use arc generator to draw
            var arcs = vis.selectAll("g.slice").data(pie).enter().append("svg:g").attr("class", "slice");
            arcs.append("svg:path")
                .attr("fill", function(d, i){
                    return color(i);
                })
                .attr("d", function (d) {
                    // log the result of the arc generator to show how cool it is :)
                    console.log(arc(d));
                    return arc(d);
                }).on("click",function(d) { alert(d.value);});

            // add the text
            if(options.showLabels) {
                arcs.append("svg:text").attr("transform", function(d){
                        d.innerRadius = 0;
                        d.outerRadius = r;
                        return "translate(" + arc.centroid(d) + ")";
                    })
                    .attr("text-anchor", "middle").text( function(d, i) {
                        return options.data[i].label;}
                    );
            }
       
            //  legend?
            if(options.showLegend) { this.displayLegend(); }
       
       
        },
       
        displayLegend: function() {
       
        }
       
    }
    
});