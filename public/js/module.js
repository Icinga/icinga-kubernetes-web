/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

(function (Icinga) {

    "use strict";

    // let Chart = require('./vendor/chart.umd.js');

    class IcingaKubernetes extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            // MutationObserver

            this.on('rendered', '#main > .container', this.onRendered, this);
        }

        onRendered(event, autorefresh, scripted, autosubmit) {
            let color = window.getComputedStyle(event.target).getPropertyValue('color');
            console.log(color);
            let charts = event.target.querySelectorAll('.chart');

            for (let lineChart of charts) {
                let labelTimestamps = lineChart.dataset.labels.split(', ');
                let labels = [];
                let datasets = [];

                for (let labelTimestamp of labelTimestamps) {
                    labels.push(new Date(Number(labelTimestamp)).toLocaleTimeString('it-IT', {hour: '2-digit', minute: '2-digit'}));

                }
                for (let i in lineChart.dataset.metrics.split('; ')) {
                    let dataStrings = lineChart.dataset.metrics.split('; ')[i].split(', ');
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
            console.log("Charts rendered");
        }
    }

    Icinga.Behaviors.IcingaKubernetes = IcingaKubernetes;

})(Icinga);