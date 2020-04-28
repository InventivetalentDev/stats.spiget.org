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
        <div class="container" style="height: 100%;">
            <div class="row-full">
                <div id="resource_stats_chart" style="height: 90%;">
                    Loading Stats...
                </div>
<br/>
                <div style="margin: 0 auto; text-align: center; ">
                    <button class="btn" id="prev-page-resource" onclick="loadPrevPage('resource')" disabled>&lt;</button>
                    <span id="page-info-resource">Page #1</span>
                    <button class="btn" id="next-page-resource" onclick="loadNextPage('resource')">&gt;</button>
                </div>
            </div>

            <div class="divider"></div>

            <div class="row-full">
                <div id="author_total_stats_chart" style="height: 90%;">
                    Loading Stats...
                </div>
                <br/>
                <div style="margin: 0 auto; text-align: center; ">
                    <button class="btn" id="prev-page-author_total" onclick="loadPrevPage('author_total')" disabled>&lt;</button>
                    <span id="page-info-author_total">Page #1</span>
                    <button class="btn" id="next-page-author_total" onclick="loadNextPage('author_total')">&gt;</button>
                </div>
            </div>

            <div class="divider"></div>

            <div class="row-full">
                <div id="author_average_stats_chart" style="height: 90%;">
                    Loading Stats...
                </div>
                <br/>
                <div style="margin: 0 auto; text-align: center; ">
                    <button class="btn" id="prev-page-author_average" onclick="loadPrevPage('author_average')" disabled>&lt;</button>
                    <span id="page-info-author_average">Page #1</span>
                    <button class="btn" id="next-page-author_average" onclick="loadNextPage('author_average')">&gt;</button>
                </div>
            </div>
        </div>


        <script src="https://code.jquery.com/jquery-3.5.0.min.js" integrity="sha256-xNzN2a4ltkB44Mc/Jz3pT4iU1cmeR0FkXs4pru/JxaQ=" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
        <script src="https://code.highcharts.com/highcharts.js"></script>
        <script>
            let currentPage = {};

            function loadPage(type, p = 1) {
                p = Math.max(1, p);
                currentPage[type] = p;
                $("#prev-page-"+type).attr("disabled", p <= 1)
                $("#page-info-"+type).text("Page #" + p);
                fetch("get.php?type=" + type + "&page=" + p).then(res => res.json()).then(data => {
                    console.log(data)
                    $("#next-page-"+type).attr("disabled", data.length < 10);
                    if (type === "resource") {
                        makeResourceChart(data);
                    }
                    if (type === "author_total") {
                        makeAuthorTotalChart(data);
                    }
                    if (type === "author_average") {
                        makeAuthorAverageChart(data);
                    }
                })
            }

            Highcharts.setOptions({
                global: {
                    useUTC: false
                }
            });

            function makeResourceChart(data) {
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

            function makeAuthorTotalChart(data) {
                Highcharts.chart("author_total_stats_chart", {
                    chart: {
                        type: "spline"
                    },
                    title: {
                        text: "Author Download Stats"
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

            function makeAuthorAverageChart(data) {
                Highcharts.chart("author_average_stats_chart", {
                    chart: {
                        type: "spline"
                    },
                    title: {
                        text: "Author Download Stats"
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

            function loadNextPage(type) {
                loadPage(type, (currentPage[type]||1) + 1);
            }

            function loadPrevPage(type) {
                loadPage(type, (currentPage[type]||1) - 1);
            }

            function loadFirstPage(type) {
                loadPage(type, 1);
            }


            loadFirstPage('resource');
            loadFirstPage('author_total');
            loadFirstPage('author_average');
        </script>
    </body>
</html>
