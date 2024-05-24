/* Icinga Web 2 | (c) 2024 Icinga GmbH | GPLv2+ */

(function (Icinga) {

    "use strict";

    class IcingaKubernetes extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            // MutationObserver

            this.on('rendered', '#main > .container', this.onRendered, this);
        }

        onRendered(event, autorefresh, scripted, autosubmit) {
            let color = window.getComputedStyle(event.target).getPropertyValue('color');

            let lineCharts = event.target.querySelectorAll('.line-chart');
            let lineChartMinifieds = event.target.querySelectorAll('.line-chart-minified');
            let doughnutCharts = event.target.querySelectorAll('.doughnut-chart');
            let doughnutChartStates = event.target.querySelectorAll('.doughnut-chart-states');
            let doughnutChartRequestLimits = event.target.querySelectorAll('.doughnut-chart-request-limit');

            for (let lineChart of lineCharts) {
                let labelTimestamps = lineChart.dataset.labels.split(', ');
                let labels = [];
                let datasets = [];

                for (let labelTimestamp of labelTimestamps) {
                    labels.push(new Date(Number(labelTimestamp)).toLocaleTimeString('it-IT', {
                        hour: '2-digit',
                        minute: '2-digit'
                    }));

                }
                for (let i in lineChart.dataset.values.split('; ')) {
                    let dataStrings = lineChart.dataset.values.split('; ')[i].split(', ');
                    let data = [];

                    for (let dataString of dataStrings) {
                        if (dataString === '') {
                            data.push(NaN);
                        } else {
                            data.push(dataString);
                        }
                    }

                    datasets.push({
                        label: lineChart.dataset.label.split('; ')[i],

                        data: data,
                        borderWidth: 1,
                        borderColor: lineChart.dataset.color.split('; ')[i],
                        fill: true,
                        backgroundColor: lineChart.dataset.color.split('; ')[i] + 'bf',
                        tension: 0.1,
                    });
                }

                new Chart(lineChart, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: datasets
                    },
                    options: {
                        elements: {
                            point: {
                                radius: 0,
                            },
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: color,
                                }
                            },
                        },
                        animation: false,
                        scales: {
                            x: {
                                grid: {
                                    color: color,
                                },
                                ticks: {
                                    maxTicksLimit: 12,
                                    minRotation: 45,
                                    maxRotation: 45,
                                    color: color,
                                },
                            },
                            y: {
                                grid: {
                                    color: color,
                                },
                                ticks: {
                                    maxTicksLimit: 5,
                                    color: color,
                                },
                            },
                        },
                    },
                });
            }

            for (let lineChartMinified of lineChartMinifieds) {
                let labelTimestamps = lineChartMinified.dataset.labels.split(', ');
                let labels = [];

                for (let labelTimestamp of labelTimestamps) {
                    labels.push(new Date(Number(labelTimestamp)).toLocaleTimeString('it-IT', {hour: '2-digit', minute: '2-digit'}));
                }

                new Chart(lineChartMinified, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: lineChartMinified.dataset.values.split(', '),
                            borderWidth: 1,
                            borderColor: lineChartMinified.dataset.color,
                            fill: true,
                            backgroundColor: lineChartMinified.dataset.color + '9f',
                            tension: 0.1,
                        }],
                    },
                    options: {
                        plugins: {
                            legend: {
                                display: false,
                            },
                        },
                        elements: {
                            point: {
                                radius: 0,
                            },
                        },
                        animation: false,
                        scales: {
                            x: {
                                display: false,
                            },
                            y: {
                                ticks: {
                                    maxTicksLimit: 2,
                                },
                            },
                        },
                    },
                });
            }

            for (let doughnutChart of doughnutCharts) {
                new Chart(doughnutChart, {
                    type: 'doughnut',
                    data: {
                        labels: doughnutChart.dataset.labels.split(', '),
                        datasets: [
                            {
                                label: 'Test',
                                data: doughnutChart.dataset.values.split(', '),
                                backgroundColor: doughnutChart.dataset.colors.split(', '),
                                borderWidth: 0,
                            }
                        ],
                    },
                    options: {
                        animation: false,
                    },
                });
            }

            for (let doughnutChartState of doughnutChartStates) {
                let percentage = doughnutChartState.dataset.value;
                let color;

                if (percentage > 0.7) {
                    color = doughnutChartState.dataset.colors.split(', ')[2];
                } else if (percentage > 0.5) {
                    color = doughnutChartState.dataset.colors.split(', ')[1];
                } else {
                    color = doughnutChartState.dataset.colors.split(', ')[0];
                }

                new Chart(doughnutChartState, {
                    type: 'doughnut',
                    data: {
                        labels: ["Empty", doughnutChartState.dataset.label, "Fill"],
                        datasets: [
                            {
                                data: [2 / 3, 0.5, 0.2, 0.3],
                                borderWidth: 0,
                                backgroundColor: [
                                    '#0000',
                                    doughnutChartState.dataset.colors.split(', ')[0],
                                    doughnutChartState.dataset.colors.split(', ')[1],
                                    doughnutChartState.dataset.colors.split(', ')[2],
                                ],
                                weight: 1.5,
                            },
                            {
                                data: [1],
                                backgroundColor: [
                                    '#0000',
                                ],
                                borderWidth: 0,
                                weight: 0.5,
                            },
                            {
                                data: [2 / 3, percentage, 1 - percentage],
                                borderWidth: 0,
                                backgroundColor: [
                                    '#0000',
                                    color,
                                    '#aaaaaa7f'
                                ],
                                weight: 6,
                            }
                        ],
                    },
                    options: {
                        animation: false,
                        rotation: 360 * 0.3,
                        cutout: '50%',
                        plugins: {
                            legend: {
                                display: false,
                                labels: {
                                    generateLabels: (chart) => {
                                        const legends = [];
                                        const {data} = chart;
                                        data.labels.forEach((label, index) => {
                                            if (label !== 'Empty' && label !== 'Fill') {
                                                legends.push({
                                                    text: label,
                                                    fillStyle: data.datasets[0].backgroundColor[index],
                                                    hidden: false,
                                                });
                                            }
                                        });

                                        return legends;
                                    }
                                }
                            },
                            tooltip: {
                                enabled: false,
                            },
                        },
                        hover: {
                            mode: null,
                        },
                    },
                    // plugins: [noData2],
                });

            }

            for (let doughnutChartRequestLimit of doughnutChartRequestLimits) {

                let real = doughnutChartRequestLimit.dataset.real;
                let request = doughnutChartRequestLimit.dataset.request;
                let limit = doughnutChartRequestLimit.dataset.limit;

                new Chart(doughnutChartRequestLimit, {
                        type: "doughnut",
                        data: {
                            datasets: [


                                {
                                    borderWidth: 0,
                                    backgroundColor: ["#0000", "#44d982", "#aaaaaa7f"],
                                    data: [2 / 3 * limit, request, limit - request],
                                    thickness: [[80, 90]],
                                    weight: 5,
                                },
                                {
                                    borderWidth: 0,
                                    backgroundColor: ["#0000"],
                                    data: [1],
                                    weight: 0.5,
                                },
                                {
                                    borderWidth: 0,
                                    backgroundColor: ["#0000", "#593684", "#aaaaaa7f"],
                                    data: [2 / 3 * limit, real, limit - real],
                                    weight: 15,
                                },
                                {
                                    borderWidth: 0,
                                    backgroundColor: ["#0000"],
                                    data: [1],
                                    weight: 0.5,
                                },
                                {
                                    borderWidth: 0,
                                    backgroundColor: ["#0000", "#7eadff", "#aaaaaa7f"],
                                    data: [2 / 3 * limit, limit, 0],
                                    weight: 5,
                                },
                            ],
                        },
                        options: {
                            plugins: {
                                tooltip: {
                                    enabled: false,
                                },
                            },
                            cutout: "40%",
                            hover: {
                                mode: null,
                            },
                            rotation: 360 * 0.3,
                        },
                        // plugins: [noData2],
                    }
                );
            }

            console.log("Charts rendered");
        }
    }

    Icinga.Behaviors.IcingaKubernetes = IcingaKubernetes;

})(Icinga);
