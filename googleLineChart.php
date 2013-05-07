<html>
  <head>
    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript" src="http://code.jquery.com/jquery-1.8.3.js"></script>
    <script type="text/javascript">
    
    var data_analysis_type = "data_analysis_type=day_of_the_week_created&";
    var data_analysis_type_text = "Day of the Week Created";
    
    var event_type_included = "nonrecurring_included=1&recurring_included=0&";
    var event_type_included_text = " of " + "Nonrecurring Events";
    
    var count_or_length = "count_or_length=0&";
    var count_or_length_text = "Count";
    
    
    // Load the Visualization API and the piechart package.
    google.load('visualization', '1', {'packages':['corechart']});
    
    $(document).ready(function(){
        // Set a callback to run when the Google Visualization API is loaded.
        google.setOnLoadCallback(drawChart);
        
        function drawChart() {
          var jsonData = $.ajax({
              url: ("getChartDataJSON.php?"+count_or_length+data_analysis_type+event_type_included),
              dataType:"json",
              async: false
              }).responseText;
              
          // Create our data table out of JSON data loaded from server.
          var data = new google.visualization.DataTable(jsonData);

          // Instantiate and draw our chart, passing in some options.
          var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
          chart.draw(data, {width: 900, height: 400, vAxis: {title: (count_or_length_text+event_type_included_text) }, hAxis: {title: data_analysis_type_text} });
        }
        
        $("#data_analysis_type").change(function() {
          data_analysis_type = $("#data_analysis_type").val();
          $("#event_type_included option:selected").each(function () {
            data_analysis_type_text = $(this).text();
          });
          
          drawChart();
        });
        
        $("#event_type_included").change(function() {
          event_type_included = $("#event_type_included").val();          
          $("#event_type_included option:selected").each(function () {
            event_type_included_text = " of " + $(this).text();
          });
          
          drawChart();
        });
        
        $("#count_or_length").change(function() {
          count_or_length = $("#count_or_length").val();          
          $("#count_or_length option:selected").each(function () {
            count_or_length_text = $(this).text();
          });
          
          drawChart();
        });
    });
    </script>
  </head>

  <body>
    <form>
        <select id="data_analysis_type">
            <option value="data_analysis_type=day_of_the_week_created&">Day of the Week Created</option>
            <option value="data_analysis_type=day_of_the_week_started&">Day of the Week Started</option>
            <option value="data_analysis_type=month_of_the_year_created&">Month of the Year Created</option>
            <option value="data_analysis_type=month_of_the_year_started&">Month of the Year Started</option>
            <option value="data_analysis_type=relative_percentage_sums&">Relative Percentage Sums</option>
        </select>

        <select id="event_type_included">
            <option value="nonrecurring_included=1&recurring_included=0&">Nonrecurring Events</option>
            <option value="nonrecurring_included=0&recurring_included=1&">Recurring Events</option>
            <option value="nonrecurring_included=1&recurring_included=1&">Nonrecurring and Recurring Events</option>
        </select>
        
        <select id="count_or_length">
            <option value="count_or_length=0&">Count</option>
            <option value="count_or_length=1&">Length</option>
        </select>
    </form>
    
    <!--Div that will hold the pie chart-->
    <div id="chart_div"></div>
  </body>
</html>