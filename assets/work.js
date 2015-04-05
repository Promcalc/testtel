jQuery(function($) {
    var oStartDay = $( "#Control_starttime" ),
        oFinishDay = $( "#Control_finishtime" ),
        oPlottype = $("#Control_plottype"),
        oInfo = $("#infoarea"),
        oForm = $("#control-form");

    $.datepicker.setDefaults($.datepicker.regional['ru']);

    oStartDay.datepicker({
        dateFormat: "dd.mm.yy",
        onClose: function( selectedDate ) {
            oFinishDay.datepicker( "option", "minDate", selectedDate );
            oForm.trigger("submit");
        }
    });

    oFinishDay.datepicker({
        dateFormat: "dd.mm.yy",
        onClose: function( selectedDate ) {
            oStartDay.datepicker( "option", "maxDate", selectedDate );
            oForm.trigger("submit");
        }
    });

    oStartDay.datepicker( "option", "maxDate", oFinishDay.datepicker("getDate") );
    oFinishDay.datepicker( "option", "minDate", oStartDay.datepicker("getDate") );

    oForm.on("submit", function(event){
        event.preventDefault();
        jQuery.ajax({
            type: "POST",
            url: "/",
            dataType: "json",
            data : oForm.serialize(),
            success: function(data, textStatus, jqXHR ){
                console.log("OK: " + textStatus, data, jqXHR);
                if( !'data' in data ) {
                    oInfo.text("ERROR: no return any data");
                    return;
                }
                if( 'error' in data ) {
                    oInfo.text("ERROR: " + data.error);
                    return;
                }
                oInfo.text("OK: data length " + data.data.length);
                plotData(data.data, ('titles' in data) ? data.titles : {});
            },
            errorfunction(jqXHR, textStatus, errorThrown) {
                console.log("ERROR:  " + textStatus, jqXHR, errorThrown);
                oInfo.text("ERROR: " + textStatus);
            }
        });
        return false;
    });

    oPlottype.on("change", function(event){
        oForm.trigger("submit");
    });

    oForm.trigger("submit");
});


/* *************************************************************** */
function posTimeLegend(dStartDate,dEndDate,nPoint){
    var aDates = [dStartDate];
    var dt = parseInt((dEndDate.getTime() - dStartDate.getTime()) / (nPoint - 1));
    while(dStartDate <= dEndDate){
        aDates.push(new Date(dStartDate.getTime()));
        dStartDate.setTime(dStartDate.getTime() + dt);
    }
    return aDates;
}

/* *************************************************************** */
function plotData(data, titles) {
var margin = {top: 20, right: 120, bottom: 30, left: 120},
    width = 960 - margin.left - margin.right,
    height = 500 - margin.top - margin.bottom;

var parseDate = d3.time.format("%Y-%m-%d").parse;

var x = d3.time.scale()
    .range([0, width]);

var y = d3.scale.linear()
    .range([height, 0]);

var color = d3.scale.category10();

var xAxis = d3.svg.axis()
    .scale(x)
    .orient("bottom")
    .ticks(posTimeLegend, 8)
    .tickFormat(d3.time.format('%d.%m.%y'));

var yAxis = d3.svg.axis()
    .scale(y)
    .orient("left");

var line = d3.svg.line()
    .interpolate("basis")
    .x(function(d) { return x(d.date); })
    .y(function(d) { return y(d.count); });

    d3.select("#plotdataarea svg").remove();

var svg = d3.select("#plotdataarea").append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
        .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

  color.domain(d3.keys(data[0]).filter(function(key) { return key !== "date"; }));
  console.log('color', data[0]);

  data.forEach(function(d) {
    d.date = parseDate(d.date);
  });

  var datagroups = color.domain().map(function(name) {
    return {
      name: name,
      values: data.map(function(d) {
        // console.log(d, d[name]);
        return {date: d.date, count: +d[name]};
      })
    };
  });

  x.domain(d3.extent(data, function(d) { return d.date; }));

  y.domain([
    d3.min(datagroups, function(c) { return d3.min(c.values, function(v) { return v.count; }); }),
    d3.max(datagroups, function(c) { return d3.max(c.values, function(v) { return v.count; }); })
  ]);

  svg.append("g")
      .attr("class", "x axis")
      .attr("transform", "translate(0," + height + ")")
      .call(xAxis);

  svg.append("g")
      .attr("class", "y axis")
      .call(yAxis)
    .append("text")
      .attr("transform", "rotate(-90)")
      .attr("y", 6)
      .attr("dy", ".71em")
      .style("text-anchor", "end")
      .text(("yaxis" in titles) ? titles.yaxis : "Values");

  var city = svg.selectAll(".city")
      .data(datagroups)
    .enter().append("g")
      .attr("class", "city");

  city.append("path")
      .attr("class", "line")
      .attr("d", function(d) { return line(d.values); })
      .style("stroke", function(d) { return color(d.name); });

  city.append("text")
      .datum(function(d) { return {name: d.name, value: d.values[d.values.length - 1]}; })
      .attr("transform", function(d) { return "translate(" + x(d.value.date) + "," + y(d.value.count) + ")"; })
      .attr("x", 3)
      .attr("dy", ".35em")
      .style("fill", function(d) { return color(d.name); })
      .text(function(d) { return (d.name in titles) ? titles[d.name] : d.name; });
}

