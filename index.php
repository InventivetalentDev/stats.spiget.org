<!DOCTYPE html>
<html>
    <head>
        <title>Spiget Resource Stats</title>

        <!--Import Google Icon Font-->
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <!-- Compiled and minified CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

        <style>
            html,body{
                height: 100%;
                margin: 0;
            }

            /* https://coderwall.com/p/hkgamw/creating-full-width-100-container-inside-fixed-width-container */
            .row-full {
                width: 90vw;
                height: 90vh;
                position: relative;
                margin-left: -45vw;
                margin-top: 50px;
                left: 50%;
            }
        </style>

        <!--Let browser know website is optimized for mobile-->
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    </head>
    <body>
        <div class="container" style="height: 95vh;">
            <div class="row-full">
                <div id="resource_stats_chart" style="height: 90%;">
                    Loading Stats...
                </div>
<br/>
                <div style="margin: 0 auto; text-align: center; ">
                    <button class="btn" id="prev-page" onclick="loadPrevPage()" disabled>&lt;</button>
                    <span id="page-info">Page #1</span>
                    <button class="btn" id="next-page" onclick="loadNextPage()">&gt;</button>
                </div>
            </div>
        </div>


        <script src="https://code.jquery.com/jquery-3.5.0.min.js" integrity="sha256-xNzN2a4ltkB44Mc/Jz3pT4iU1cmeR0FkXs4pru/JxaQ=" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
        <script src="https://code.highcharts.com/highcharts.js"></script>
        <script>
            let currentPage = 1;

            function loadPage(p = 1) {
                p = Math.max(1, p);
                currentPage = p;
                $("#prev-page").attr("disabled", currentPage <= 1)
                $("#page-info").text("Page #" + p);
                fetch("get.php?page=" + p).then(res => res.json()).then(data => {
                    console.log(data)
                    $("#next-page").attr("disabled", data.length < 10);
                    makeChart(data);
                })
            }

            function makeChart(data) {
                Highcharts.chart("resource_stats_chart", {
                    chart: {
                        type: "spline"
                    },
                    title: {
                        text: "Resource Download Stats"
                    },
                    xAxis: {
                        type: "datetime",
                        title: {
                            text: "Date"
                        }
                    },
                    yAxis: {
                        title: {
                            text: "Downloads"
                        }
                    },
                    series: data
                })
            }

            function loadNextPage() {
                loadPage(currentPage + 1);
            }

            function loadPrevPage() {
                loadPage(currentPage - 1);
            }

            function loadFirstPage() {
                loadPage(1);
            }


            loadFirstPage();
        </script>
    </body>
</html>
