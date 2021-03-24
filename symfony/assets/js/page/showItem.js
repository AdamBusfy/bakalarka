import '../bootstrap.js';

import Chart from 'chart.js';
import $ from "jquery";


var historyPrices = document.getElementById('price_history').getAttribute('data-price-history');

var parsedHistoryPrices = JSON.parse(historyPrices);

var ctx = document.getElementById('history_prices_line_chart').getContext('2d');

let chart = new Chart(ctx, {
    type: 'line',
    data: {
        datasets: [{
            label: 'Price history',
            data: parsedHistoryPrices,
            backgroundColor: [
                'rgba(153, 102, 255, 0.5)',
            ],
            borderColor: [
                'rgba(153, 102, 255, 1)',
            ],
            borderWidth : 1
        }],
        labels: parsedHistoryPrices.map((price, index) => index)
    },
    options: {
        scales: {
            yAxes: [{
                ticks: {
                    suggestedMin: 50,
                    suggestedMax: 100
                }
            }]
        }
    }
});