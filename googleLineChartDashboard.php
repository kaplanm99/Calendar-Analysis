<html>
  <head>
    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript" src="http://code.jquery.com/jquery-1.8.3.js"></script>
    <script type="text/javascript">
    
    var data_analysis_type = "data_analysis_type=relative_percentage_sums&";
    var data_analysis_type_text = "Relative Percentage Sums";
    
    var event_type_included = "nonrecurring_included=1&recurring_included=0&";
    var event_type_included_text = "Number of " + "Nonrecurring Events";
    
    // Load the Visualization API and the piechart package.
    google.load('visualization', '1.1', {packages: ['corechart', 'controls']});
    
    $(document).ready(function(){
        
      function drawVisualization() {
        var dashboard = new google.visualization.Dashboard(
             document.getElementById('dashboard'));
      
         var control = new google.visualization.ControlWrapper({
           'controlType': 'ChartRangeFilter',
           'containerId': 'control',
           'options': {
             // Filter by the date axis.
             'filterColumnIndex': 0,
             'ui': {
               'chartType': 'LineChart',
               'chartOptions': {
                 'chartArea': {'width': '80%'},
                 'hAxis': {'baselineColor': 'none'}
               },
               // Display a single series that shows the closing value of the stock.
               // Thus, this view has two columns: the date (axis) and the stock value (line series).
               /*
               'chartView': {
                 'columns': [1, 3]
               },
               */
               // 1 day in milliseconds = 24 * 60 * 60 * 1000 = 86,400,000
               //'minRangeSize': 86400000
             }
           }
         });
      
         var chart = new google.visualization.ChartWrapper({
           'chartType': 'LineChart',
           'containerId': 'chart',
           'options': {
             // Use the same chart area width as the control for axis alignment.
             'chartArea': {'height': '70%', 'width': '80%'},
             'hAxis': {'title': data_analysis_type_text},
             'vAxis': {'title': event_type_included_text},
           }
         });
         
         var jsonData = $.ajax({
              url: ("getChartDataJSON.php?count_or_length=0&"+data_analysis_type+event_type_included),
              dataType:"json",
              async: false
              }).responseText;
              
          // Create our data table out of JSON data loaded from server.
          var data = new google.visualization.DataTable(jsonData);
      
         
         dashboard.bind(control, chart);
         dashboard.draw(data);
      }
      

      google.setOnLoadCallback(drawVisualization);
        
        
        
        
        
        
        
        ////////////////////////////////////////////////////
        
        /*
        // Set a callback to run when the Google Visualization API is loaded.
        google.setOnLoadCallback(drawChart);
        
        function drawChart() {
          var jsonData = $.ajax({
              url: ("getChartDataJSON.php?"+data_analysis_type+event_type_included),
              dataType:"json",
              async: false
              }).responseText;
              
          // Create our data table out of JSON data loaded from server.
          var data = new google.visualization.DataTable(jsonData);

          // Instantiate and draw our chart, passing in some options.
          var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
          chart.draw(data, {width: 900, height: 400, vAxis: {title: event_type_included_text }, hAxis: {title: data_analysis_type_text} });
        }
        */
        
        $("#data_analysis_type").change(function() {
          data_analysis_type = $("#data_analysis_type").val();
          $("#event_type_included option:selected").each(function () {
            data_analysis_type_text = $(this).text();
          });
          
          drawVisualization();
        });
        
        $("#event_type_included").change(function() {
          event_type_included = $("#event_type_included").val();          
          $("#event_type_included option:selected").each(function () {
            event_type_included_text = "Number of " + $(this).text();
          });
          
          drawVisualization();
        });
    });
    </script>
  </head>

  <body>
    <form>
        <select id="data_analysis_type">
            <option value="data_analysis_type=relative_percentage_sums&">Relative Percentage Sums</option>
        </select>

        <select id="event_type_included">
            <option value="nonrecurring_included=1&recurring_included=0&">Nonrecurring Events</option>
            <option value="nonrecurring_included=0&recurring_included=1&">Recurring Events</option>
            <option value="nonrecurring_included=1&recurring_included=1&">Nonrecurring and Recurring Events</option>
        </select>
        <!--
        <select id="count_or_length">
            <option value="count_or_length=0&">Count</option>
            <option value="count_or_length=1&">Length</option>
        </select>
        -->
    </form>
    
    <!--Div that will hold the pie chart-->
    <div id="dashboard">
        <div id="chart" style='width: 915px; height: 300px;'></div>
        <div id="control" style='width: 915px; height: 50px;'></div>
    </div>
    
  </body>
</html>