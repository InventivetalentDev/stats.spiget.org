<!DOCTYPE html>
<html>
    <head>
        <title>Spiget Resource Stats</title>

        <!--Import Google Icon Font-->
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <!-- Compiled and minified CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

        <style>
            html, body {
                height: 100%;
                margin: 0;
            }

            .container {
                width: 95vw !important;
                max-width: 100% !important;
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
            <br/>

            <div class="row">
                <div class="input-field col s12 m6">
                    <input placeholder="Resource IDs" id="resource_filter" type="text">
                    <label for="resource_filter">Resource Filter (comma-separated IDs)</label>
                </div>
                <div class="input-field col s12 m6">
                    <input placeholder="Author IDs" id="author_filter" type="text">
                    <label for="author_filter">Author Filter (comma-separated IDs)</label>
                </div>
            </div>

            <div class="divider"></div>

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

            let resourceFilter = "";
            let authorFilter = "";

            $("#resource_filter").on("keyup",()=>{
                resourceFilter = $("#resource_filter").val();
            }).on("change",()=>{
                reloadCurrentPage("resource");
            });
            $("#author_filter").on("keyup",()=>{
                authorFilter = $("#author_filter").val();
            }).on("change",()=>{
                reloadCurrentPage("resource");
                reloadCurrentPage("author_total");
                reloadCurrentPage("author_average");
            });

            function loadPage(type, p = 1) {
                p = Math.max(1, p);
                currentPage[type] = p;
                $("#prev-page-" + type).attr("disabled", p <= 1)
                $("#page-info-" + type).text("Page #" + p);
                fetch("get.php?type=" + type + "&page=" + p + "&resource_filter=" + resourceFilter + "&author_filter=" + authorFilter).then(res => res.json()).then(data => {
                    console.log(data)
                    $("#next-page-" + type).attr("disabled", data.length < 10);
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
                    subtitle: {
                        text: "Total Download Counts over Time"
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
                resolveAuthorNamesIn(data).then(()=>{
                Highcharts.chart("author_total_stats_chart", {
                    chart: {
                        type: "spline"
                    },
                    title: {
                        text: "Author Download Stats"
                    },
                    subtitle: {
                        text: "Cumulative Download Counts per Author over Time"
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
                })})
            }

            function makeAuthorAverageChart(data) {
                resolveAuthorNamesIn(data).then(()=>{
                Highcharts.chart("author_average_stats_chart", {
                    chart: {
                        type: "spline"
                    },
                    title: {
                        text: "Author Download Stats"
                    },
                    subtitle: {
                        text: "Average Download Counts per Author per Resource (downloads divided by resource count) over Time"
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
                })
            }

            function loadNextPage(type) {
                loadPage(type, (currentPage[type] || 1) + 1);
            }

            function loadPrevPage(type) {
                loadPage(type, (currentPage[type] || 1) - 1);
            }

            function loadFirstPage(type) {
                loadPage(type, 1);
            }

            function reloadCurrentPage(type) {
                loadPage(type, currentPage[type]);
            }

            function resolveAuthorNamesIn(series) {
                return resolveAuthorNames(series).then(nameMap=>{
                    for (let ser of series) {
                        ser.authorName = ser.name = nameMap["" + ser.author];
                    }
                })
            }

            function resolveAuthorNames(series) {
                return new Promise((resolve, reject) => {
                    let promises = [];
                    let ids = [];
                    for (let ser of series) {
                        ids.push(ser.author);
                        promises.push(resolveAuthorName(ser.author));
                    }
                    Promise.all(promises).then(names => {
                        let map = {};
                        for (let i = 0; i < ids.length; i++) {
                            map["" + ids[i]] = names[i];
                        }
                        resolve(map);
                    }).catch(reject)
                })
            }

            function resolveAuthorName(id) {
                return new Promise((resolve, reject) => {
                    fetch("https://api.spiget.org/v2/authors/" + id + "?fields=name").then(res => res.json()).then(data => {
                        resolve(data.name);
                    }).catch(reject);
                })
            }


            loadFirstPage('resource');
            loadFirstPage('author_total');
            loadFirstPage('author_average');
        </script>
    </body>
</html>
